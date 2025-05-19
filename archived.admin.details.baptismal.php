<?php
include('config.php');

if (!isset($_GET['id'])) {
    echo "No application selected.";
    exit;
}

$id = intval($_GET['id']);
$data = null;

if ($conn) {
    // Fetch application data only
    $stmt = $conn->prepare("
        SELECT w.*, e.booking_type, e.Book_Date, e.Start_time, e.Status
        FROM baptismal_bookings w
        LEFT JOIN event e ON w.baptismal_bookings_id = e.baptismal_booking_id
        WHERE w.baptismal_bookings_id = ?");
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
  <title>Baptismal Application Details</title>
  <link rel="stylesheet" href="styles/test-admin.css" />
  <link rel="stylesheet" href="test1.css" />
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    h2 { margin-bottom: 20px; }
    ul { list-style: none; padding: 0; }
    ul li { margin-bottom: 20px; }
    ul li img { display: block; margin-top: 10px; border-radius: 8px; }
    a.button {
      display: inline-block;
      background-color: #ba5d5d;
      color: #fff;
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

  <a href="archive.admin.php" class="button">← Back to List</a>
  <h2>Baptismal Application Details</h2>
  <ul>
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

</body>
</html>
