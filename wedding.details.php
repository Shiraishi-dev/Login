<?php
include('config.php');

if (!isset($_GET['id'])) {
    echo "No application selected.";
    exit;
}

$id = intval($_GET['id']);
$data = null;

if ($conn) {
    // Confirm application logic
    if (isset($_POST['confirm'])) {
        // Fetch the related event's book_date and start_time
        $stmt = $conn->prepare("SELECT e.book_date, e.start_time FROM event e WHERE e.wedding_application_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $weddingDate = $event['book_date'];
        $selectedStartTime = $event['start_time'];
        $stmt->close();

        // Check if the selected date and time is already approved for another application
        $stmt = $conn->prepare("SELECT event_id FROM event WHERE book_date = ? AND start_time = ? AND status = 'approved' AND wedding_application_id != ?");
        $stmt->bind_param("ssi", $weddingDate, $selectedStartTime, $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "<script>alert('This date and time slot has already been approved for another application. Please Click Decline And Message The Client'); window.location.href = 'wedding.admin.php';</script>";
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Approve application
        $stmt = $conn->prepare("UPDATE event SET status = 'approved' WHERE wedding_application_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application confirmed successfully!'); window.location.href = 'wedding.admin.php';</script>";
        exit;
    }

    // Decline application logic
    if (isset($_POST['decline'])) {
        $remarks = $_POST['remarks'];

        $stmt = $conn->prepare("UPDATE event SET status = 'declined' WHERE wedding_application_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE wedding_applications SET decline_reason = ? WHERE wedding_applications_id = ?");
        $stmt->bind_param("si", $remarks, $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application declined successfully.'); window.location.href = 'wedding.admin.php';</script>";
        exit;
    }

    // Fetch application data
    $stmt = $conn->prepare("
        SELECT w.*, e.booking_type, e.book_date, e.start_time, e.status
        FROM wedding_applications w
        LEFT JOIN event e ON w.wedding_applications_id = e.wedding_application_id
        WHERE w.wedding_applications_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
}

if (!$data) {
    echo "Application not found.";
    exit;
}

function renderFileField($label, $path) {
    if (!$path) return;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    echo "<li><strong>$label:</strong><br>";
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        echo "<img src=\"$path\" alt=\"$label\" style=\"max-width: 400px; height: auto; border: 1px solid #ccc; margin-bottom: 10px;\">";
    } else {
        echo "<a href=\"$path\" target=\"_blank\">View File</a>";
    }
    echo "</li>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Wedding Application Details</title>
  <link rel="stylesheet" href="styles/test-admin.css">
  <link rel="stylesheet" href="test1.css">
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    h2 { margin-bottom: 20px; }
    ul { list-style: none; padding: 0; }
    ul li { margin-bottom: 20px; }
    ul li img { display: block; margin-top: 10px; border-radius: 8px; }
    a.button, button {
      display: inline-block;
      background-color: #ba5d5d;
      color: #fff;
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      margin-right: 10px;
      cursor: pointer;
    }
    .button-container { margin-bottom: 20px; }
  </style>
</head>
<body>

<a href="wedding.admin.php" class="button">← Back to List</a>

<h2>Wedding Application Details</h2>
<ul><br>
  <li><strong>Wife:</strong> <?= htmlspecialchars($data['wife_first_name'] . ' ' . $data['wife_middle_name'] . ' ' . $data['wife_last_name']) ?></li>
  <li><strong>Husband:</strong> <?= htmlspecialchars($data['husband_first_name'] . ' ' . $data['husband_middle_name'] . ' ' . $data['husband_last_name']) ?></li>
  <li><strong>Wife Age:</strong> <?= htmlspecialchars($data['wife_age']) ?></li>
  <li><strong>Husband Age:</strong> <?= htmlspecialchars($data['husband_age']) ?></li>
  <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
  <li><strong>Event Date:</strong> <?= htmlspecialchars($data['book_date']) ?></li>
  <li><strong>Event Time:</strong> <?= htmlspecialchars($data['start_time']) ?></li>
  <li><strong>Status:</strong> <?= htmlspecialchars(ucfirst($data['status'] ?? 'Pending')) ?></li>
  <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['submitted_at']) ?></li>

  <?php
  renderFileField('Marriage License', $data['marriage_license']);
  renderFileField('Application Form', $data['application_form']);
  renderFileField('Birth Certificates', $data['birth_certificates']);
  renderFileField('Certificate of No Marriage', $data['certificate_of_no_marriage']);
  renderFileField('Community Tax Certificate', $data['community_tax_certificate']);
  renderFileField('Parental Consent Advice Groom', $data['parental_consent_advice_groom']);
  renderFileField('Parental Consent Advice Bride', $data['parental_consent_advice_bride']);
  renderFileField('Valid IDs Groom', $data['valid_ids_groom']);
  renderFileField('Valid IDs Bride', $data['valid_ids_bride']);
  renderFileField('Barangay Certificate', $data['barangay_certificate']);
  renderFileField('Canonical Interview', $data['canonical_interview']);
  ?>
</ul>

<div class="button-container">
  <form method="post" style="display:inline;" id="confirmForm">
    <button class="submit-button" type="submit" name="confirm">Confirm</button>
  </form>

  <form method="post" style="display:inline;" id="declineForm">
    <input type="hidden" name="remarks" id="remarksInput">
    <input type="hidden" name="decline" value="1">
    <button class="delete-button" type="button" onclick="declineApplication()">Decline</button>
  </form>
</div>

<script>
function declineApplication() {
  var remarks = prompt("Please enter a reason for declining:");
  if (remarks !== null && remarks.trim() !== '') {
    document.getElementById('remarksInput').value = remarks.trim();
    document.getElementById('declineForm').submit();
  } else if (remarks !== null) {
    alert('Decline reason cannot be empty.');
  }
}
</script>

</body>
</html>
