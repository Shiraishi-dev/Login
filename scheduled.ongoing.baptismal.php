<?php
include('config.php'); 
session_start(); // Start the session

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('index.php','_self')</script>";
    exit(); 
}

$username = $_SESSION['username']; 

$results = [];

if ($conn) {
    date_default_timezone_set('Asia/Manila'); // Set timezone
    $today = date('Y-m-d'); // Get today's date

    $sql = "SELECT id, child_first_name, child_last_name, father_first_name, mother_first_name 
            FROM baptismal_bookings 
            WHERE event_type = 'baptism' 
            AND DATE(date_of_baptism) = '$today'";

    $query = $conn->query($sql);

    if ($query && $query->num_rows > 0) {
        while ($row = $query->fetch_assoc()) {
            $results[] = $row;
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
      <li><a href="baptismal.admin.php" class="active"><span class="material-symbols-outlined">concierge</span>Baptismal</a></li>
      <li><a href="burial.admin.php"><span class="material-symbols-outlined">concierge</span>Burial</a></li>
      <h4><span>Menu</span></h4>
      <li><a href="Scheduled.admin.php"><span class="material-symbols-outlined">event</span>Events Schedule</a></li>
      <li><a href="scheduled.ongoing.baptismal.php"><span class="material-symbols-outlined">chronic</span>Ongoing</a></li>
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
    <h2>Today's Baptismal Events</h2>

    <?php if (!empty($results)): ?>
      <?php foreach ($results as $row): ?>
        <div class="request-card">
          <h3>
            <?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_last_name']) ?> — 
            Child of <?= htmlspecialchars($row['father_first_name']) ?> & <?= htmlspecialchars($row['mother_first_name']) ?>
          </h3>
          <a href="baptismal.details.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No baptismal events scheduled for today.</p>
    <?php endif; ?>
  </div>

</body>
</html>
