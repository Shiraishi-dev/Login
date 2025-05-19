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
    // Collect form data
    $wife_first = $_POST['wife_first_name'] ?? null;
    $wife_middle = $_POST['wife_middle_name'] ?? null;
    $wife_last = $_POST['wife_last_name'] ?? null;
    $wife_age = $_POST['wife_age'] ?? null;

    $husband_first = $_POST['husband_first_name'] ?? null;
    $husband_middle = $_POST['husband_middle_name'] ?? null;
    $husband_last = $_POST['husband_last_name'] ?? null;
    $husband_age = $_POST['husband_age'] ?? null;

    $Book_Date = $_POST['Book_Date'] ?? null;
    $Start_time = $_POST['Start_time'] ?? null;

    // Get current file paths from DB to preserve if no new upload
    $stmt = $conn->prepare("SELECT wife_first_name, wife_middle_name, wife_last_name, wife_age, husband_first_name, husband_middle_name, husband_last_name, husband_age, marriage_license, application_form, birth_certificates, certificate_of_no_marriage, community_tax_certificate, parental_consent_advice_groom, parental_consent_advice_bride, valid_ids_groom, valid_ids_bride, barangay_certificate, canonical_interview FROM wedding_applications WHERE wedding_applications_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result(
    $old_wife_first, $old_wife_middle, $old_wife_last, $old_wife_age,
    $old_husband_first, $old_husband_middle, $old_husband_last, $old_husband_age,
    $old_marriage_license, $old_application_form, $old_birth_certificates,
    $old_certificate_of_no_marriage, $old_community_tax_certificate,
    $old_parental_consent_advice_groom, $old_parental_consent_advice_bride,
    $old_valid_ids_groom, $old_valid_ids_bride,
    $old_barangay_certificate, $old_canonical_interview
    );

    $stmt->fetch();
    $stmt->close();

    
    $marriage_license = handleFileUpload('marriage_license', $old_marriage_license);
    $application_form = handleFileUpload('application_form', $old_application_form);
    $birth_certificates = handleFileUpload('birth_certificates', $old_birth_certificates);
    $certificate_of_no_marriage = handleFileUpload('certificate_of_no_marriage', $old_certificate_of_no_marriage);
    $community_tax_certificate = handleFileUpload('community_tax_certificate', $old_parental_consent_advice_groom );
    $parental_consent_advice_groom = handleFileUpload('parental_consent_advice_groom', $old_parental_consent_advice_groom);
    $parental_consent_advice_bride = handleFileUpload('parental_consent_advice_bride', $old_parental_consent_advice_bride);
    $valid_ids_groom = handleFileUpload('valid_ids_groom', $old_valid_ids_groom);
    $valid_ids_bride = handleFileUpload('valid_ids_bride', $old_valid_ids_bride);
    $barangay_certificate = handleFileUpload('barangay_certificate', $old_barangay_certificate);
    $canonical_interview = handleFileUpload('canonical_interview', $old_canonical_interview );
    
    
   
    // Update baptismal_bookings table
    $stmt = $conn->prepare("UPDATE wedding_applications SET 
    wife_first_name=?, wife_middle_name=?, wife_last_name=?, wife_age=?,
    husband_first_name=?, husband_middle_name=?, husband_last_name=?, husband_age=?,
    marriage_license=?, application_form=?, birth_certificates=?, certificate_of_no_marriage=?, 
    community_tax_certificate=?, parental_consent_advice_groom=?, parental_consent_advice_bride=?, 
    valid_ids_groom=?, valid_ids_bride=?, barangay_certificate=?, canonical_interview=?
    WHERE wedding_applications_id=?");

    $stmt->bind_param("sssssssssssssssssssi", 
    $wife_first, $wife_middle, $wife_last, $wife_age,
    $husband_first, $husband_middle, $husband_last, $husband_age,
    $marriage_license, $application_form, $birth_certificates, $certificate_of_no_marriage,
    $community_tax_certificate, $parental_consent_advice_groom, $parental_consent_advice_bride,
    $valid_ids_groom, $valid_ids_bride, $barangay_certificate, $canonical_interview,
    $id);





    // Update event table
    $stmt = $conn->prepare("UPDATE event SET Book_Date=?, Start_time=?, Status='Pending' WHERE wedding_application_id=?");
    $stmt->bind_param("ssi", $Book_Date, $Start_time, $id);
    $stmt->execute();
    $stmt->close();

    $message = "Application updated successfully.";
}

// Fetch updated data for display
$stmt = $conn->prepare("
    SELECT w.*, e.booking_type, e.Book_Date, e.Start_time, e.Status, w.decline_reason
    FROM wedding_applications w
    LEFT JOIN event e ON w.wedding_applications_id = e.wedding_application_id
    WHERE w.wedding_applications_id = ?");
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

// Dates where both 9AM and 1PM are booked
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
  <title>Baptismal Application Details & Update</title>
  <link rel="stylesheet" href="styles/test-admin.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
      <li><strong>Bride's Name:</strong> <?= htmlspecialchars($data['wife_first_name'] . ' ' . $data['wife_middle_name'] . ' ' . $data['wife_last_name']) ?></li>
      <li><strong>Groom's Name:</strong> <?= htmlspecialchars($data['husband_first_name'] . ' ' . $data['husband_middle_name'] . ' ' . $data['husband_last_name']) ?></li>
      <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
      <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['submitted_at']) ?></li>
      <li><strong>Date of Baptism:</strong> <?= htmlspecialchars($data['Book_Date']) ?></li>
      <li><strong>Time of Baptism:</strong> <?= htmlspecialchars($data['Start_time']) ?></li>
      <li><strong>Event Status:</strong> <?= htmlspecialchars($data['Status']) ?></li>

      <?php
        renderFileField('Marriage License', $data['marriage_license'] ?? '');
        renderFileField('Application Form', $data['application_form'] ?? '');
        renderFileField('Birth Certificates', $data['birth_certificates'] ?? '');
        renderFileField('Certificate of No Marriage', $data['certificate_of_no_marriage'] ?? '');
        renderFileField('Community Tax Certificate', $data['community_tax_certificate'] ?? '');
        renderFileField('Parental Consent/Advice (Groom)', $data['parental_consent_advice_groom'] ?? '');
        renderFileField('Parental Consent/Advice (Bride)', $data['parental_consent_advice_bride'] ?? '');
        renderFileField('Valid IDs (Groom)', $data['valid_ids_groom'] ?? '');
        renderFileField('Valid IDs (Bride)', $data['valid_ids_bride'] ?? '');
        renderFileField('Barangay Certificate', $data['barangay_certificate'] ?? '');
        renderFileField('Canonical Interview', $data['canonical_interview'] ?? '');
      ?>

    </ul>

        <hr>
    <h3>Edit Wedding Application</h3>
    <form action="" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="id" value="<?= $id ?>">

      <label for="wife_first_name">Wife Firstname:</label>
      <input type="text" id="wife_first_name" name="wife_first_name" value="<?= htmlspecialchars($data['wife_first_name']) ?>" required>

      <label for="wife_middle_name">Wife Middlename:</label>
      <input type="text" id="wife_middle_name" name="wife_middle_name" value="<?= htmlspecialchars($data['wife_middle_name']) ?>" required>

      <label for="wife_last_name">Wife Lastname:</label>
      <input type="text" id="wife_last_name" name="wife_last_name" value="<?= htmlspecialchars($data['wife_last_name']) ?>" required>

      <label for="husband_first_name">Husband Firstname:</label>
      <input type="text" id="husband_first_name" name="husband_first_name" value="<?= htmlspecialchars($data['husband_first_name']) ?>" required>

      <label for="husband_middle_name">Husband Middlename:</label>
      <input type="text" id="husband_middle_name" name="husband_middle_name" value="<?= htmlspecialchars($data['husband_middle_name']) ?>" required>

      <label for="husband_last_name">Husband Lastname:</label>
      <input type="text" id="husband_last_name" name="husband_last_name" value="<?= htmlspecialchars($data['husband_last_name']) ?>" required>

      <label for="wife_age">Wife Age:</label>
      <input type="number" id="wife_age" name="wife_age" value="<?= htmlspecialchars($data['wife_age']) ?>" required>

      <label for="husband_age">Husband Age:</label>
      <input type="number" id="husband_age" name="husband_age" value="<?= htmlspecialchars($data['husband_age']) ?>" required>

      <label for="booking_type">Booking Type:</label>
      <input type="text" id="booking_type" name="booking_type" value="<?= htmlspecialchars($data['booking_type']) ?>" required>

     <label for="wedding_date">Date of Wedding:</label>
      <input type="text" id="wedding_date" name="Book_Date" value="<?= htmlspecialchars($data['Book_Date']) ?>" required>


      <label for="Start_time">Start Time:</label>
      <select id="Start_time" name="Start_time" required>
        <option value="">-- Select Start Time --</option>
        <option value="09:00:00" <?= $data['Start_time'] == "09:00:00" ? 'selected' : '' ?>>09:00 AM</option>
        <option value="13:00:00" <?= $data['Start_time'] == "13:00:00" ? 'selected' : '' ?>>01:00 PM</option>
      </select>

      <label for="marriage_license">Marriage License:</label>
      <input type="file" id="marriage_license" name="marriage_license">
      <?php if (!empty($data['marriage_license'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['marriage_license']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="application_form">Application Form:</label>
      <input type="file" id="application_form" name="application_form">
      <?php if (!empty($data['application_form'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['application_form']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="birth_certificates">Birth Certificates:</label>
      <input type="file" id="birth_certificates" name="birth_certificates">
      <?php if (!empty($data['birth_certificates'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['birth_certificates']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="certificate_of_no_marriage">Certificate of No Marriage:</label>
      <input type="file" id="certificate_of_no_marriage" name="certificate_of_no_marriage">
      <?php if (!empty($data['certificate_of_no_marriage'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['certificate_of_no_marriage']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="community_tax_certificate">Community Tax Certificate:</label>
      <input type="file" id="community_tax_certificate" name="community_tax_certificate">
      <?php if (!empty($data['community_tax_certificate'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['community_tax_certificate']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="parental_consent_advice_groom">Parental Consent (Groom):</label>
      <input type="file" id="parental_consent_advice_groom" name="parental_consent_advice_groom">
      <?php if (!empty($data['parental_consent_advice_groom'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['parental_consent_advice_groom']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="parental_consent_advice_bride">Parental Consent (Bride):</label>
      <input type="file" id="parental_consent_advice_bride" name="parental_consent_advice_bride">
      <?php if (!empty($data['parental_consent_advice_bride'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['parental_consent_advice_bride']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="valid_ids_groom">Valid ID (Groom):</label>
      <input type="file" id="valid_ids_groom" name="valid_ids_groom">
      <?php if (!empty($data['valid_ids_groom'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['valid_ids_groom']) ?>" target="_blank">View</a></small>
      <?php endif; ?>

      <label for="valid_ids_bride">Valid ID (Bride):</label>
      <input type="file" id="valid_ids_bride" name="valid_ids_bride">
      <?php if (!empty($data['valid_ids_bride'])): ?>
        <small>Current: <a href="<?= htmlspecialchars($data['valid_ids_bride']) ?>" target="_blank">View</a></small>
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


  <script>
  const bookedSlots = <?= json_encode($bookedSlots); ?>;

  function formatDate(date) {
    return date.toISOString().split('T')[0];
  }

  flatpickr("#wedding_date", {
    dateFormat: "Y-m-d",
    minDate: "today",
    disable: [
      function(date) {
        if (date.getDay() === 0 || date.getDay() === 6) {
          return true;
        }

        const formatted = formatDate(date);
        if (bookedSlots[formatted]) {
          const times = bookedSlots[formatted];
          if (times.includes("09:00:00") && times.includes("13:00:00")) {
            return true;
          }
        }
        return false;
      }
    ],
    onChange: function() {
      document.getElementById('Start_time').value = '';
    }
  });
</script>



</body>
</html>
