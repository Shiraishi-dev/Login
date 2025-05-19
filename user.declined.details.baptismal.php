<?php
include('config.php');

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    echo "No application selected.";
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
$data = null;
$message = '';

// Handle file upload function
function handleFileUpload($inputName, $oldPath) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; // Make sure this directory exists and is writable
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $tmpName = $_FILES[$inputName]['tmp_name'];
        $fileName = basename($_FILES[$inputName]['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx'];
        if (!in_array($ext, $allowed)) {
            return ''; // Invalid file type, ignore upload
        }
        $newName = uniqid() . '.' . $ext;
        $destination = $uploadDir . $newName;
        if (move_uploaded_file($tmpName, $destination)) {
            return $destination;
        }
    }
    // No new upload or error, keep old path
    return $oldPath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize POST data
    $child_first_name      = $_POST['child_first_name'] ?? '';
    $child_middle_name     = $_POST['child_middle_name'] ?? '';
    $child_last_name       = $_POST['child_last_name'] ?? '';
    $child_birth_date      = $_POST['child_birth_date'] ?? '';
    $date_of_baptism       = $_POST['Book_Date'] ?? '';
    $Start_time            = $_POST['Start_time'] ?? '';

    $father_first_name     = $_POST['father_first_name'] ?? '';
    $father_middle_name     = $_POST['father_middle_name'] ?? '';
    $father_last_name     = $_POST['father_last_name'] ?? '';
    $mother_first_name     = $_POST['mother_first_name'] ?? '';
    $mother_middle_name    = $_POST['mother_middle_name'] ?? '';
    $mother_last_name      = $_POST['mother_last_name'] ?? '';

    // Get current file paths from DB to preserve if no new upload
    $stmt = $conn->prepare("SELECT birth_certificate, marriage_certificate_of_parents, baptismal_seminar_certificate, sponsor_list, valid_ids, barangay_certificate, canonical_interview FROM baptismal_bookings WHERE baptismal_bookings_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_birth_certificate, $old_marriage_certificate_of_parents, $old_baptismal_seminar_certificate, $old_sponsor_list, $old_valid_ids, $old_barangay_certificate, $old_canonical_interview);
    $stmt->fetch();
    $stmt->close();

        // Process file uploads
    $birth_certificate = handleFileUpload('birth_certificate', $old_birth_certificate);
    $marriage_certificate_of_parents= handleFileUpload('marriage_certificate_of_parents', $old_marriage_certificate_of_parents);
    $baptismal_seminar_certificate = handleFileUpload('baptismal_seminar_certificate', $old_baptismal_seminar_certificate);
    $sponsor_list = handleFileUpload('sponsor_list', $old_sponsor_list);
    $valid_ids = handleFileUpload('valid_ids', $old_valid_ids);
    $barangay_certificate = handleFileUpload('barangay_certificate', $old_barangay_certificate);
    $canonical_interview = handleFileUpload('canonical_interview', $old_canonical_interview);


    // Update baptismal_bookings table
    $stmt = $conn->prepare("UPDATE baptismal_bookings SET child_first_name=?, child_middle_name=?, child_last_name=?, child_birth_date=?, father_first_name=?, father_middle_name=?, father_last_name=?, mother_first_name=?, mother_middle_name=?, mother_last_name=? WHERE baptismal_bookings_id=?");
    $stmt->bind_param("ssssssssssi", $child_first_name, $child_middle_name, $child_last_name, $child_birth_date, $father_first_name, $father_middle_name, $father_last_name, $mother_first_name,$mother_middle_name,$mother_last_name,$id);
    $stmt->execute();
    $stmt->close();

    // Update event table
    $stmt = $conn->prepare("UPDATE event SET Book_Date=?, Start_time=?, Status='Pending' WHERE baptismal_booking_id=?");
    $stmt->bind_param("ssi", $date_of_baptism, $Start_time, $id);
    $stmt->execute();
    $stmt->close();

    $message = "Application updated successfully.";
}

// Fetch updated data for display
$stmt = $conn->prepare("
    SELECT w.*, e.booking_type, e.Book_Date, e.Start_time, e.Status, w.decline_reason
    FROM baptismal_bookings w
    LEFT JOIN event e ON w.baptismal_bookings_id = e.baptismal_booking_id
    WHERE w.baptismal_bookings_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "Application not found.";
    exit;
}

// Fetch approved slots for date disabling
$bookedSlots = [];
$slotQuery = "SELECT Book_Date, Start_time FROM event WHERE status = 'approved'";
$slotResult = $conn->query($slotQuery);
while ($row = $slotResult->fetch_assoc()) {
    $date = $row['Book_Date'];
    $time = $row['Start_time'];
    if (!isset($bookedSlots[$date])) {
        $bookedSlots[$date] = [];
    }
    $bookedSlots[$date][] = $time;
}

$disabledDates = [];
foreach ($bookedSlots as $date => $times) {
    if (in_array("09:00:00", $times) && in_array("13:00:00", $times)) {
        $disabledDates[] = $date;
    }
}

function renderFileField($label, $path) {
    if (!$path) return;

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    echo "<li><strong>$label:</strong><br>";

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        echo "<img src=\"$path\" alt=\"$label\" style=\"max-width: 400px; height: auto; border: 1px solid #ccc; margin-top: 10px; border-radius: 8px;\">";
    } else {
        echo "<a href=\"$path\" target=\"_blank\">View File</a>";
    }

    echo "</li>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Baptismal Application Details & Update</title>
  <link rel="stylesheet" href="styles/test-admin.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 30px;
      background: #f9f9f9;
    }
    a.button {
      display: inline-block;
      background-color: #ba5d5d;
      color: #fff;
      padding: 8px 16px;
      border-radius: 5px;
      text-decoration: none;
      margin-bottom: 20px;
    }
    a.button:hover {
      background-color: #a14646;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background: #fff;
      padding: 25px 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    h2, h3 {
      margin-bottom: 20px;
      color: #333;
    }
    ul.center {
      list-style: none;
      padding: 0;
      margin: 0 auto 30px;
      display: inline-block;
      text-align: left;
      width: 100%;
      max-width: 500px;
      background-color: #fff;
    }
    ul.center li {
      margin-bottom: 20px;
      font-size: 16px;
      color: #444;
    }
    ul.center li strong {
      display: inline-block;
      width: 140px;
      color: #222;
    }
    form label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #333;
      text-align: left;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }
    form input[type="text"],
    form input[type="date"],
    form select,
    form input[type="file"] {
      width: 100%;
      max-width: 500px;
      padding: 8px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }
    form button {
      background-color: #4CAF50;
      color: white;
      padding: 10px 25px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }
    form button:hover {
      background-color: #45a049;
    }
    small {
      display: block;
      margin-top: -10px;
      margin-bottom: 10px;
      font-size: 12px;
      color: #666;
      text-align: left;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }
    ul.center li img {
      border-radius: 8px;
    }
  </style>
</head>
<body>

  <a href="declined.book.user.php" class="button">← Back to List</a>

  <div class="container">
    <?php if ($message): ?>
      <p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h2>Baptismal Application Details</h2>
    <ul class="center">
      <li><strong>Child Name:</strong> <?= htmlspecialchars($data['child_first_name'] . ' ' . $data['child_middle_name'] . ' ' . $data['child_last_name']) ?></li>
      <li><strong>Child Birth Date:</strong> <?= htmlspecialchars($data['child_birth_date']) ?></li>
      <li><strong>Father Name:</strong> <?= htmlspecialchars($data['father_first_name'] . ' ' . $data['father_middle_name'] . ' ' . $data['father_last_name']) ?></li>
      <li><strong>Mother Name:</strong> <?= htmlspecialchars($data['mother_first_name'] . ' ' . $data['mother_middle_name'] . ' ' . $data['mother_last_name']) ?></li>
      <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
      <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['submitted_at']) ?></li>
      <li><strong>Date of Baptism:</strong> <?= htmlspecialchars($data['Book_Date']) ?></li>
      <li><strong>Time of Baptism:</strong> <?= htmlspecialchars($data['Start_time']) ?></li>
      <li><strong>Event Status:</strong> <?= htmlspecialchars($data['Status']) ?></li>

      <?php
        renderFileField('Birth Certificate', $data['birth_certificate']);
        renderFileField('Marriage Certificate of Parents', $data['marriage_certificate_of_parents']);
        renderFileField('Baptismal Seminar Certificate', $data['baptismal_seminar_certificate']);
        renderFileField('Sponsor List', $data['sponsor_list']);
        renderFileField('Valid IDs', $data['valid_ids']);
        renderFileField('Barangay Certificate', $data['barangay_certificate']);
        renderFileField('Canonical Interview', $data['canonical_interview']);
      ?>
    </ul>

    <hr>

    <h3>Edit Baptismal Application</h3>
    <form action="" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="id" value="<?= $id ?>">

      <label for="child_first_name">Child Firstname:</label>
      <input type="text" id="child_first_name" name="child_first_name" value="<?= htmlspecialchars($data['child_first_name']) ?>" required>

      <label for="child_middle_name">Child Middlename:</label>
      <input type="text" id="child_middle_name" name="child_middle_name" value="<?= htmlspecialchars($data['child_middle_name']) ?>" required>

      <label for="child_last_name">Child Lastname:</label>
      <input type="text" id="child_last_name" name="child_last_name" value="<?= htmlspecialchars($data['child_last_name']) ?>" required>

      <label for="child_birth_date">Birthdate: </label>
      <input type="date" id="child_birth_date" name="child_birth_date" value="<?= htmlspecialchars($data['child_birth_date']) ?>" required>

      <label for="father_first_name">Father Firstname:</label>
      <input type="text" id="father_first_name" name="father_first_name" value="<?= htmlspecialchars($data['father_first_name']) ?>" required>

      <label for="father_middle_name">Father Middlename:</label>
      <input type="text" id="father_middle_name" name="father_middle_name" value="<?= htmlspecialchars($data['father_middle_name']) ?>" required>

      <label for="father_last_name">Father Lastname:</label>
      <input type="text" id="father_last_name" name="father_last_name" value="<?= htmlspecialchars($data['father_last_name']) ?>" required>

      <label for="mother_first_name">Mother Firstname:</label>
      <input type="text" id="mother_first_name" name="mother_first_name" value="<?= htmlspecialchars($data['mother_first_name']) ?>" required>

      <label for="mother_middle_name">Mother Middlename:</label>
      <input type="text" id="mother_middle_name" name="mother_middle_name" value="<?= htmlspecialchars($data['mother_middle_name']) ?>" required>

      <label for="mother_last_name">Mother Lastname:</label>
      <input type="text" id="mother_last_name" name="mother_last_name" value="<?= htmlspecialchars($data['mother_last_name']) ?>" required>


      <label for="date_of_baptism">Date of Baptism:</label>
      <input type="text" id="date_of_wedding" name="Book_Date" value="<?= htmlspecialchars($data['Book_Date']) ?>" required>

      <label for="time_of_wedding">Start Time:</label>
      <select id="time_of_wedding" name="Start_time" required>
        <option value="">-- Select Start Time --</option>
        <option value="09:00:00" <?= $data['Start_time'] == "09:00:00" ? 'selected' : '' ?>>09:00 AM</option>
        <option value="13:00:00" <?= $data['Start_time'] == "13:00:00" ? 'selected' : '' ?>>01:00 PM</option>
      </select>


      <label for="birth_certificate">Birth Certificate:</label>
      <input type="file" id="birth_certificate" name="birth_certificate">
      <?php if (!empty($data['birth_certificate'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['birth_certificate']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="marriage_certificate_of_parents">Marriage Certificate of Parents:</label>
      <input type="file" id="marriage_certificate_of_parents" name="marriage_certificate_of_parents">
      <?php if (!empty($data['marriage_certificate_of_parents'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['marriage_certificate_of_parents']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="Baptismal Seminar Certificate">Baptismal Seminar Certificate:</label>
      <input type="file" id="baptismal_seminar_certificate" name="baptismal_seminar_certificate">
      <?php if (!empty($data['baptismal_seminar_certificate'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['baptismal_seminar_certificate']) ?>" target="_blank">View</a></small>
      <?php endif; ?>


      <label for="Sponsor List">Sponsor List:</label>
      <input type="file" id="sponsor_list" name="sponsor_list">
      <?php if (!empty($data['sponsor_list'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['sponsor_list']) ?>" target="_blank">View</a></small>
      <?php endif; ?>
      

      <label for="Valid IDs">Valid ID:</label>
      <input type="file" id="valid_ids" name="valid_ids">
      <?php if (!empty($data['valid_ids'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['valid_ids']) ?>" target="_blank">View</a></small>
      <?php endif; ?>


      <label for="barangay_certificate">Barangay Certificate:</label>
      <input type="file" id="barangay_certificate" name="barangay_certificate">
      <?php if (!empty($data['barangay_certificate'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['barangay_certificate']) ?>" target="_blank">View</a></small>
      <?php endif; ?>


      <label for="canonical_interview">Canonical Interview:</label>
      <input type="file" id="canonical_interview" name="canonical_interview">
      <?php if (!empty($data['canonical_interview'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['canonical_interview']) ?>" target="_blank">View</a></small>
      <?php endif; ?>


      <button type="submit">Update</button>
    </form>
  </div>

 <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    const bookedSlots = <?= json_encode($bookedSlots); ?>;

    flatpickr("#date_of_wedding", {
      dateFormat: "Y-m-d",
      minDate: "today",
      disable: [
        function(date) {
          // Disable weekends (Saturday=6, Sunday=0)
          if (date.getDay() === 0 || date.getDay() === 6) {
            return true;
          }
          const formatted = flatpickr.formatDate(date, "Y-m-d");
          // If both 09:00:00 and 13:00:00 are booked, disable the day
          if (bookedSlots[formatted]) {
            const times = bookedSlots[formatted];
            if (times.includes("09:00:00") && times.includes("13:00:00")) {
              return true;
            }
          }
          return false;
        }
      ],
      onChange: function(selectedDates, dateStr, instance) {
        // Clear start time if burial date changes
        document.getElementById('Start_time').value = '';
      }
    });
  </script>



</body>
</html>
