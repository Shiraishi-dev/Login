<?php
include('config.php'); 
session_start(); // Start the session

// Ensure the user is logged in and is an admin
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'admin'
) {
    echo "<script>window.open('index.php','_self')</script>";  // Redirect to login if not admin
    exit();
}

$username = $_SESSION['username']; 

$results = [];

if ($conn) {
    // Fetch all pending burial requests for admin
    $sql = "
        SELECT br.burial_requirements_id, br.deceased_name, br.date_of_death, br.place_of_death, 
               e.Book_Date, e.Start_time, br.funeral_home, br.death_certificate, br.barangay_clearance, 
               br.valid_id, br.created_at 
        FROM burial_requirements br
        JOIN event e ON br.event_id = e.event_id 
        WHERE e.booking_type = 'burial' AND e.status = 'Pending'
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }

    $stmt->close();
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
      <li><a href="burial.admin.php" class="active"><span class="material-symbols-outlined">concierge</span>Burial</a></li>
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
    <h2>Burial Book Request List</h2>

    <?php if (!empty($results)): ?>
      <?php foreach ($results as $row): ?>
        <div class="request-card">
          <h3><?= htmlspecialchars($row['deceased_name']) ?></h3>
          <p>Date of Death: <?= htmlspecialchars($row['date_of_death']) ?></p>
          <p>Place of Death: <?= htmlspecialchars($row['place_of_death']) ?></p>
          <p>Funeral Home: <?= htmlspecialchars($row['funeral_home']) ?></p>
          <p>Book Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
          <p>Start Time: <?= htmlspecialchars($row['Start_time']) ?></p> <br>
          <a href="burial.details.php?id=<?= $row['burial_requirements_id'] ?>" class="view-more-btn">View More</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No burial requests found.</p>
    <?php endif; ?>
  </div>

</body>
</html>
