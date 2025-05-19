<?php
include('config.php');
session_start();

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];
$weddingResults = $burialResults = $baptismalResults = [];

// Fetch user_id using username
$userQuery = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userRow = $userResult->fetch_assoc();
$userQuery->close();

if (!$userRow) {
    die("User not found.");
}

$user_id = $userRow['user_id'];

// Wedding Applications with status
$sql1 = "SELECT w.wedding_applications_id, w.husband_first_name, w.husband_last_name, w.wife_first_name, w.wife_last_name, e.status
         FROM wedding_applications w
         JOIN events e ON w.event_id = e.event_id
         WHERE w.user_id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$result1 = $stmt1->get_result();
while ($row = $result1->fetch_assoc()) {
    $weddingResults[] = $row;
}
$stmt1->close();

// Burial Requirements with status
$sql2 = "SELECT b.burial_requirements_id, b.deceased_name, e.status
         FROM burial_requirements b
         JOIN events e ON b.event_id = e.event_id
         WHERE b.user_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $burialResults[] = $row;
}
$stmt2->close();

// Baptismal Bookings with status
$sql3 = "SELECT b.baptismal_bookings_id, b.child_first_name, b.child_last_name, b.father_first_name, b.father_last_name, b.mother_first_name, b.mother_last_name, e.status
         FROM baptismal_bookings b
         JOIN events e ON b.event_id = e.event_id
         WHERE b.user_id = ?";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
while ($row = $result3->fetch_assoc()) {
    $baptismalResults[] = $row;
}
$stmt3->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Pending Requests</title>
  <link rel="stylesheet" href="styles/test-admin.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=pending_actions" />
  <style>
    .edit-btn {
      display: inline-block;
      margin-left: 10px;
      padding: 6px 12px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }
    .edit-btn:hover {
      background-color: #45a049;
    }
    .status-label {
      color: #888;
      font-weight: normal;
      font-style: italic;
      margin-left: 5px;
    }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="side-header">
    <a href="user.php"><img src="includes/logo.jpg" alt="logo"></a>
    <h2 class="title-a">Corpus Christi Parish</h2>
  </div>

  <ul class="sidebar-links"><span class="material-symbols-outlined">
    <h4><span>Book Request</span></h4>
    <li><a href="pending.book.user.php">Pending Bookings</a></li>
    <li><a href="approved.book.user.php">Approved Bookings</a></li>
    <li><a href="decline.book.user.php">Declined Bookings</a></li>
    <h4><span>Menu</span></h4>
    <li><a href="index.php">Logout</a></li>
  </ul>

  <div class="user-account">
    <div class="user-profile">
      <img src="includes/profile.jpg" alt="profile-img">
      <div class="user-detail">
        <h3><?php echo htmlspecialchars($username); ?></h3>
        <span>Admin</span>
      </div>
    </div>
  </div>
</aside>

<div class="top1"></div>

<div class="client-requests">
  <h2>Your Pending Requests</h2>

  <!-- Wedding Applications -->
  <h3>Wedding Applications</h3>
  <?php if (!empty($weddingResults)): ?>
    <?php foreach ($weddingResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['husband_first_name'] . ' ' . $row['husband_last_name']) ?> & <?= htmlspecialchars($row['wife_first_name'] . ' ' . $row['wife_last_name']) ?>
          <span class="status-label">(<?= htmlspecialchars($row['status']) ?>)</span>
        </h4>
        <a href="wedding.details.user.php?id=<?= $row['wedding_applications_id'] ?>" class="view-more-btn">View More</a>
        <a href="wedding.edit.php?id=<?= $row['wedding_applications_id'] ?>" class="edit-btn">Edit</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending wedding applications found.</p>
  <?php endif; ?>

  <!-- Burial Requests -->
  <h3>Burial Requests</h3>
  <?php if (!empty($burialResults)): ?>
    <?php foreach ($burialResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['deceased_name']) ?>
          <span class="status-label">(<?= htmlspecialchars($row['status']) ?>)</span>
        </h4>
        <a href="user.pending.details.burial.php?id=<?= $row['burial_requirements_id'] ?>" class="view-more-btn">View More</a>
        <a href="burial.edit.php?id=<?= $row['burial_requirements_id'] ?>" class="edit-btn">Edit</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending burial requests found.</p>
  <?php endif; ?>

  <!-- Baptismal Requests -->
  <h3>Baptismal Requests</h3>
  <?php if (!empty($baptismalResults)): ?>
    <?php foreach ($baptismalResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_last_name']) ?>
          <span class="status-label">(<?= htmlspecialchars($row['status']) ?>)</span>
        </h4>
        <a href="baptismal.details.php?id=<?= $row['baptismal_bookings_id'] ?>" class="view-more-btn">View More</a>
        <a href="baptismal.edit.php?id=<?= $row['baptismal_bookings_id'] ?>" class="edit-btn">Edit</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending baptismal requests found.</p>
  <?php endif; ?>
</div>

</body>
</html>
