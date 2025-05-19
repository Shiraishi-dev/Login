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

  // Fetch baptismal requests and join with event table where event_type is 'baptism' and status is 'Pending'
  if ($conn) {
      $sql = "
          SELECT bb.baptismal_bookings_id, bb.child_first_name, bb.child_last_name, bb.father_first_name, bb.mother_first_name, 
                bb.child_middle_name, e.Book_Date, e.Start_time
          FROM baptismal_bookings bb
          JOIN event e ON bb.event_id = e.event_id
          WHERE e.booking_type = 'baptismal' AND e.status = 'Pending'
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
        <li><a href="baptismal.admin.php" class="active"><span class="material-symbols-outlined">concierge</span>Baptismal</a></li>
        <li><a href="burial.admin.php"><span class="material-symbols-outlined">concierge</span>Burial</a></li>
        <h4><span>Menu</span></h4>
        <li><a href="Scheduled.admin.php"><span class="material-symbols-outlined">event</span>Events Schedule</a></li>
        <li><a href="scheduled.ongoing.admin.php"><span class="material-symbols-outlined">chronic</span>Ongoing</a></li>
        <li><a href="archive.admin.php"><span class="material-symbols-outlined">folder_match</span>Archive Records</a></li>
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
      <h2>Baptismal Book Request List</h2>

      <?php if (!empty($results)): ?>
        <?php foreach ($results as $row): ?>
          <div class="request-card">
            <h3>
              <p>Son/Daughter Name: <?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_middle_name']. ' ' . $row['child_last_name']) ?> <br></p>
              <p>Child of: <?= htmlspecialchars($row['father_first_name']) ?> and <?= htmlspecialchars($row['mother_first_name']) ?> <br></p>
              <p>Baptismal Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
              <p>Start TIme: <?= htmlspecialchars($row['Start_time']) ?></p>
              <br>
            </h3>
            <a href="baptismal.details.php?id=<?= $row['baptismal_bookings_id'] ?>" class="view-more-btn">View More</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No baptismal applications found.</p>
      <?php endif; ?>
    </div>

  </body>
  </html>
