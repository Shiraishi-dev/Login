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
        // Step 1: Get Book_Date and Start_time for current application
        $stmt = $conn->prepare("SELECT Book_Date, Start_time FROM event WHERE burial_requirement_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();

        if (!$event) {
            echo "<script>alert('Event information not found.'); window.location.href = 'burial.admin.php';</script>";
            exit;
        }

        $book_date = $event['Book_Date'];
        $start_time = $event['Start_time'];

        // Step 2: Check if this slot is already approved for someone else
        $stmt = $conn->prepare("SELECT * FROM event WHERE Book_Date = ? AND Start_time = ? AND status = 'approved' AND burial_requirement_id != ?");
        $stmt->bind_param("ssi", $book_date, $start_time, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('This date and time slot has already been booked by another approved application. Please click Decline and message the client.'); window.location.href = 'burial.admin.php';</script>";
            exit;
        }

        $stmt->close();

        // Step 3: Approve this application
        $stmt = $conn->prepare("UPDATE event SET status = 'approved' WHERE burial_requirement_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application confirmed successfully!'); window.location.href = 'burial.admin.php';</script>";
        exit;
    }

    // Decline application logic
    if (isset($_POST['decline'])) {
        $remarks = $_POST['remarks'];

        $stmt = $conn->prepare("UPDATE event SET status = 'declined' WHERE burial_requirement_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE burial_requirements SET decline_reason = ? WHERE burial_requirements_id = ?");
        $stmt->bind_param("si", $remarks, $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application declined successfully.'); window.location.href = 'burial.admin.php';</script>";
        exit;
    }

    // Fetch application data
    $stmt = $conn->prepare("
        SELECT w.*, e.booking_type, e.Book_Date, e.Start_time, e.status
        FROM burial_requirements w
        LEFT JOIN event e ON w.burial_requirements_id = e.burial_requirement_id
        WHERE w.burial_requirements_id = ?");
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
  <meta charset="UTF-8" />
  <title>Burial Application Details</title>
  <link rel="stylesheet" href="styles/test-admin.css" />
  <link rel="stylesheet" href="test1.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 30px;
    }
    h2 {
      margin-bottom: 20px;
    }
    ul {
      list-style: none;
      padding: 0;
    }
    ul li {
      margin-bottom: 20px;
    }
    ul li img {
      display: block;
      margin-top: 10px;
      border-radius: 8px;
    }
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
    .button-container {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <a href="burial.admin.php" class="button">← Back to List</a>
  <h2>Burial Application Details</h2>
  <ul>
    <li><strong>Deceased Name:</strong> <?= htmlspecialchars($data['deceased_name']) ?></li>
    <li><strong>Date of Death:</strong> <?= htmlspecialchars($data['date_of_death']) ?></li>
    <li><strong>Place of Death:</strong> <?= htmlspecialchars($data['place_of_death']) ?></li>
    <li><strong>Date of Burial:</strong> <?= htmlspecialchars($data['Book_Date']) ?></li>
    <li><strong>Funeral Home:</strong> <?= htmlspecialchars($data['funeral_home']) ?></li>
    <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
    <li><strong>Start Time:</strong> <?= htmlspecialchars($data['Start_time']) ?></li>
    <li><strong>Status:</strong> <?= htmlspecialchars(ucfirst($data['status'] ?? 'Pending')) ?></li>
    <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['created_at']) ?></li>

    <?php
      renderFileField('Death Certificate', $data['death_certificate']);
      renderFileField('Barangay Clearance', $data['barangay_clearance']);
      renderFileField('Valid ID', $data['valid_id']);
    ?>
  </ul>

  <div class="button-container">
    <form method="post" style="display:inline;" id="confirmForm">
      <button class="submit-button" type="submit" name="confirm">Confirm</button>
    </form>

    <form method="post" style="display:inline;" id="declineForm">
      <input type="hidden" name="remarks" id="remarksInput" required>
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
