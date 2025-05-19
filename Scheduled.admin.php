<?php
include('config.php');
session_start();

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];

$weddings = [];
$baptisms = [];
$burials = [];

if ($conn) {
    // Wedding bookings
    $sql = "SELECT id, wife_first_name, wife_last_name, husband_first_name, husband_last_name, date_of_wedding
            FROM wedding_applications 
            WHERE event_type='wedding' AND status='Approved'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $weddings[] = $row;
        }
    }

    // Baptismal bookings
    $sql = "SELECT id, child_first_name, child_last_name, father_first_name, mother_first_name, date_of_baptism, time_of_baptism
            FROM baptismal_bookings 
            WHERE event_type='baptism' AND status='Approved'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $baptisms[] = $row;
        }
    }

    // Burial bookings
    $sql = "SELECT id, deceased_name, date_of_death, date_of_burial 
            FROM burial_requirements 
            WHERE event_type='burial' AND status='Approved'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $burials[] = $row;
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="styles/test-admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="side-header">
    <img src="includes/logo.jpg" alt="logo">
    <h2 class="title-a">Corpus Christi Parish</h2>
  </div>

  <ul class="sidebar-links">
    <h4><span>Book Request</span></h4>
    <li><a href="wedding.admin.php"><span class="material-symbols-outlined">concierge</span>Wedding</a></li>
    <li><a href="baptismal.admin.php"><span class="material-symbols-outlined">concierge</span>Baptismal</a></li>
    <li><a href="burial.admin.php"><span class="material-symbols-outlined">concierge</span>Burial</a></li>
    <h4><span>Menu</span></h4>
    <li><a href="Scheduled.admin.php"><span class="material-symbols-outlined">event</span>Events Schedule</a></li>
    <li><a href="Scheduled.admin.php"><span class="material-symbols-outlined">chronic</span>Ongoing</a></li>
    <li><a href="Scheduled.admin.php"><span class="material-symbols-outlined">folder_match</span>Archive Records</a></li>
    <li><a href="index.php"><span class="material-symbols-outlined">logout</span>Logout</a></li>
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

<!-- Top Bar -->
<div class="top1"></div>

<!-- Main Content -->
<div class="client-requests">
  <h2>Scheduled Bookings</h2>

  <!-- Weddings -->
  <h3>Weddings</h3>
  <?php if (!empty($weddings)): ?>
    <?php foreach ($weddings as $row): ?>
      <div class="request-card">
        <h4>Groom: <?= htmlspecialchars($row['husband_first_name'] . ' ' . $row['husband_last_name']) ?> <br> Bride: <?= htmlspecialchars($row['wife_first_name'] . ' ' . $row['wife_last_name']) ?>  <br> Date of the Wedding Ceremony: <?= htmlspecialchars($row['date_of_wedding']) ?></h4> <br>
        <a href="wedding.details.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No Wedding Bookings found.</p>
  <?php endif; ?>

  <!-- Baptisms -->
  <h3>Baptisms</h3>
  <?php if (!empty($baptisms)): ?>
    <?php foreach ($baptisms as $row): ?>
      <div class="request-card">
        <h4> Child Name: <?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_last_name']) ?></h4>
        <p>Parents: <?= htmlspecialchars($row['father_first_name']) ?> & <?= htmlspecialchars($row['mother_first_name']) ?></p>
        <h4> Date of Baptism: <?= htmlspecialchars($row['date_of_baptism']) ?></h4> <br>
        <h4> Time of Baptism: <?= htmlspecialchars($row['time_of_baptism']) ?></h4> <br>
        <a href="baptism.details.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No Baptismal Bookings found.</p>
  <?php endif; ?>

  <!-- Burials -->
  <h3>Burials</h3>
  <?php if (!empty($burials)): ?>
    <?php foreach ($burials as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['deceased_name']) ?></h4>
        <p>Date of Death: <?= htmlspecialchars($row['date_of_death']) ?></p>
        <p>Burial Date: <?= htmlspecialchars($row['date_of_burial']) ?></p> <br>
        <a href="burial.details.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No Burial Bookings found.</p>
  <?php endif; ?>

</div>

</body>
</html>
