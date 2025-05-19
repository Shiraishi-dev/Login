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
    $deceased_name = $_POST['deceased_name'] ?? '';
    $date_of_death = $_POST['date_of_death'] ?? '';
    $place_of_death = $_POST['place_of_death'] ?? '';
    $Book_Date = $_POST['Book_Date'] ?? '';
    $Start_time = $_POST['Start_time'] ?? '';
    $funeral_home = $_POST['funeral_home'] ?? '';

    // Get current file paths from DB to preserve if no new upload
    $stmt = $conn->prepare("SELECT death_certificate, barangay_clearance, valid_id FROM burial_requirements WHERE burial_requirements_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_death_certificate, $old_barangay_clearance, $old_valid_id);
    $stmt->fetch();
    $stmt->close();

    // Process file uploads
    $death_certificate = handleFileUpload('death_certificate', $old_death_certificate);
    $barangay_clearance = handleFileUpload('barangay_clearance', $old_barangay_clearance);
    $valid_id = handleFileUpload('valid_id', $old_valid_id);

    // Update burial_requirements table
    $stmt = $conn->prepare("UPDATE burial_requirements SET deceased_name=?, date_of_death=?, place_of_death=?, funeral_home=?, death_certificate=?, barangay_clearance=?, valid_id=? WHERE burial_requirements_id=?");
    $stmt->bind_param("sssssssi", $deceased_name, $date_of_death, $place_of_death, $funeral_home, $death_certificate, $barangay_clearance, $valid_id, $id);
    $stmt->execute();
    $stmt->close();

    // Update event table
    $stmt = $conn->prepare("UPDATE event SET Book_Date=?, Start_time=?, Status='Pending' WHERE burial_requirement_id=?");
    $stmt->bind_param("ssi", $Book_Date, $Start_time, $id);
    $stmt->execute();
    $stmt->close();

    $message = "Application updated successfully.";
}

// Fetch updated data for display
$stmt = $conn->prepare("
    SELECT w.*, e.booking_type, e.Book_Date, e.Start_time, e.status, w.decline_reason
    FROM burial_requirements w
    LEFT JOIN event e ON w.burial_requirements_id = e.burial_requirement_id
    WHERE w.burial_requirements_id = ?");
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
  <title>Burial Application Details & Update</title>
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

    <h2>Burial Application Details</h2>
    <ul class="center">
      <li><strong>Deceased Name:</strong> <?= htmlspecialchars($data['deceased_name']) ?></li>
      <li><strong>Date of Death:</strong> <?= htmlspecialchars($data['date_of_death']) ?></li>
      <li><strong>Place of Death:</strong> <?= htmlspecialchars($data['place_of_death']) ?></li>
      <li><strong>Date of Burial:</strong> <?= htmlspecialchars($data['Book_Date']) ?></li>
      <li><strong>Funeral Home:</strong> <?= htmlspecialchars($data['funeral_home']) ?></li>
      <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
      <li><strong>Start Time:</strong> <?= htmlspecialchars($data['Start_time']) ?></li>
      <li><strong>Status:</strong> <?= htmlspecialchars(ucfirst($data['status'] ?? 'Pending')) ?></li>
      <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['created_at']) ?></li>
      <li><strong>Declined Reason:</strong> <?= htmlspecialchars($data['decline_reason']) ?></li>

      <?php
        renderFileField('Death Certificate', $data['death_certificate']);
        renderFileField('Barangay Clearance', $data['barangay_clearance']);
        renderFileField('Valid ID', $data['valid_id']);
      ?>
    </ul>

    <hr>

    <h3>Edit Burial Application</h3>
    <form action="" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="id" value="<?= $id ?>">

      <label for="deceased_name">Deceased Name:</label>
      <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

      <label for="date_of_death">Date of Death:</label>
      <input type="date" id="date_of_death" name="date_of_death" value="<?= htmlspecialchars($data['date_of_death']) ?>" required>

      <label for="place_of_death">Place of Death:</label>
      <input type="text" id="place_of_death" name="place_of_death" value="<?= htmlspecialchars($data['place_of_death']) ?>" required>

      <label for="burial_date">Date of Burial:</label>
      <input type="text" id="burial_date" name="Book_Date" value="<?= htmlspecialchars($data['Book_Date']) ?>" readonly required>

      <label for="Start_time">Start Time:</label>
      <select id="Start_time" name="Start_time" required>
        <option value="">-- Select Start Time --</option>
        <option value="09:00:00" <?= $data['Start_time'] == "09:00:00" ? 'selected' : '' ?>>09:00 AM</option>
        <option value="13:00:00" <?= $data['Start_time'] == "13:00:00" ? 'selected' : '' ?>>01:00 PM</option>
      </select>

      <label for="funeral_home">Funeral Home:</label>
      <input type="text" id="funeral_home" name="funeral_home" value="<?= htmlspecialchars($data['funeral_home']) ?>">

      <label for="death_certificate">Death Certificate:</label>
      <input type="file" id="death_certificate" name="death_certificate">
      <?php if (!empty($data['death_certificate'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['death_certificate']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="barangay_clearance">Barangay Clearance:</label>
      <input type="file" id="barangay_clearance" name="barangay_clearance">
      <?php if (!empty($data['barangay_clearance'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['barangay_clearance']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="valid_id">Valid ID:</label>
      <input type="file" id="valid_id" name="valid_id">
      <?php if (!empty($data['valid_id'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['valid_id']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <button type="submit">Update</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    const bookedSlots = <?= json_encode($bookedSlots); ?>;

    flatpickr("#burial_date", {
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
