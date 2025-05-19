<?php
include('config.php');

if (!isset($_GET['id'])) {
    echo "No application selected.";
    exit;
}

$id = intval($_GET['id']);
$data = null;

if ($conn) {
    // Handle confirm action
    if (isset($_POST['confirm'])) {
        $stmt = $conn->prepare("UPDATE burial_requirements SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application confirmed successfully!'); window.location.href = 'burial.admin.php';</script>";
        exit;
    }

    // Handle delete action
    if (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM burial_requirements WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Application deleted successfully.'); window.location.href = 'burial.admin.php';</script>";
        exit;
    }

    // Fetch data again
    $stmt = $conn->prepare("SELECT * FROM burial_requirements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
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
  <title>Burial Application Details</title>
  <link rel="stylesheet" href="styles/test-admin.css">
  <link rel="stylesheet" href="test1.css">
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
    <li><strong>Date of Burial:</strong> <?= htmlspecialchars($data['date_of_burial']) ?></li>
    <li><strong>Funeral Home:</strong> <?= htmlspecialchars($data['funeral_home']) ?></li>
    <li><strong>Event Type:</strong> <?= htmlspecialchars($data['event_type']) ?></li>
    <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['created_at']) ?></li>

    <?php
      renderFileField('Death Certificate', $data['death_certificate']);
      renderFileField('Barangay Clearance', $data['barangay_clearance']);
      renderFileField('Valid ID', $data['valid_id']);
    ?>
  </ul>

  <div class="button-container">
    <form method="post" style="display:inline;">
      <button class="submit-button" type="submit" name="confirm">Confirm</button>
      <button class="delete-button" type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this application?');">Delete</button>
    </form>
  </div>

</body>
</html>
