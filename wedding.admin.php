<?php
include('config.php');
session_start(); // Start the session

// Ensure the user is logged in
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_type']) || 
    $_SESSION['user_type'] !== 'admin') {
    echo "<script>window.open('index.php','_self')</script>";  // Redirect to login page if not logged in as admin
    exit();
}

$username = $_SESSION['username']; 
$results = [];

// Fetch wedding applications and join with event table where booking_type is 'wedding' and status is 'Pending'
if ($conn) {
    // Corrected: assuming primary key is named `id` in wedding_applications
    $sql = "
        SELECT 
            wa.wedding_applications_id, 
            wa.wife_first_name, 
            wa.wife_middle_name, 
            wa.wife_last_name, 
            wa.husband_first_name, 
            wa.husband_middle_name,
            wa.husband_last_name,
            e.Book_Date,
            e.Start_time
        FROM wedding_applications wa
        JOIN event e ON wa.event_id = e.event_id
        WHERE e.booking_type = 'wedding' AND e.status = 'Pending'
    ";

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
      <li><a href="baptismal.admin.php"><span class="material-symbols-outlined">concierge</span>Baptismal</a></li>
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
    <h2>Wedding Book Request List</h2>

     <?php if (!empty($results)): ?>
      <?php foreach ($results as $row): ?>
        <div class="request-card">
          <p>Groom: <?= htmlspecialchars($row['husband_first_name']) . ' ' . htmlspecialchars($row['husband_middle_name']) . ' ' . htmlspecialchars($row['husband_last_name']) ?></p>
          <p>Bride: <?= htmlspecialchars($row['wife_first_name']) . ' ' . htmlspecialchars($row['wife_middle_name']) . ' ' . htmlspecialchars($row['wife_last_name']) ?></p>
          <p>Book Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
          <p>Start Time: <?= htmlspecialchars($row['Start_time']) ?></p> <br>
          <a href="wedding.details.php?id=<?= $row['wedding_applications_id'] ?>" class="view-more-btn">View More</a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No wedding applications found.</p>
    <?php endif; ?>
  </div>

</body>
</html>
