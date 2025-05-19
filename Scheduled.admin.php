<?php
include('config.php');
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];
$weddingResults = $burialResults = $baptismalResults = [];

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Wedding Applications (pending only)
$stmt1 = $conn->prepare("
    SELECT wa.wedding_applications_id, wa.husband_first_name, wa.husband_last_name, wa.wife_first_name, wa.wife_last_name, e.Book_Date, e.Start_time
    FROM wedding_applications wa
    JOIN event e ON e.wedding_application_id = wa.wedding_applications_id
    WHERE e.status = 'approved' AND e.booking_type = 'Wedding'
");
if ($stmt1) {
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    while ($row = $result1->fetch_assoc()) {
        $weddingResults[] = $row;
    }
    $stmt1->close();
}

// Burial Requests (pending only)
$stmt2 = $conn->prepare("
    SELECT br.burial_requirements_id, br.deceased_name , br.date_of_death, br.funeral_home, br.place_of_death, e.Book_Date, e.Start_time
    FROM burial_requirements br
    JOIN event e ON e.burial_requirement_id = br.burial_requirements_id
    WHERE e.status = 'approved' AND e.booking_type = 'Burial'
");

if ($stmt2) {
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $burialResults[] = $row;
    }
    $stmt2->close();
}


$stmt3 = $conn->prepare("
    SELECT bb.baptismal_bookings_id, bb.child_first_name, bb.child_middle_name, bb.child_last_name, bb.father_first_name, bb.father_middle_name, bb.father_last_name, bb.mother_first_name, bb.mother_middle_name, bb.mother_last_name, e.Book_Date, e.Start_time
    FROM baptismal_bookings bb
    JOIN event e ON e.baptismal_booking_id = bb.baptismal_bookings_id
    WHERE e.status = 'approved' AND e.booking_type = 'Baptismal'
");

if ($stmt3) {
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    while ($row = $result3->fetch_assoc()) {
        $baptismalResults[] = $row;
    }
    $stmt3->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Approved Requests</title>
  <link rel="stylesheet" href="styles/test-admin.css" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" />
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
    .request-card {
      background: #f5f5f5;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 8px;
    }
    .view-more-btn {
      background-color: #007BFF;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      text-decoration: none;
      margin-left: 10px;
    }
    .view-more-btn:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>

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

<div class="top1"></div>

<div class="client-requests">
  <h2> Scheduled Requests</h2>

  <h3>Wedding Applications</h3>
  <?php if (!empty($weddingResults)): ?>
    <?php foreach ($weddingResults as $row): ?>
      <div class="request-card">
        <h4>Betrothed: <?= htmlspecialchars($row['husband_first_name'] . ' ' . $row['husband_last_name']) ?> & <?= htmlspecialchars($row['wife_first_name'] . ' ' . $row['wife_last_name']) ?></h4> <br>
        <p>Book Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
        <p>Start time: <?= htmlspecialchars($row['Start_time']) ?></p> <br>
        <a href="scheduled.admin.wedding.php?id=<?= urlencode($row['wedding_applications_id']) ?>" class="view-more-btn">View More</a>
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
        <h4>Deceased Name: <?= htmlspecialchars($row['deceased_name']) ?></h4>
        <p>Date of Date: <?= htmlspecialchars($row['date_of_death']) ?></p>
        <p>Funeral Home: <?= htmlspecialchars($row['funeral_home']) ?></p>
        <p>Place of Death <?= htmlspecialchars($row['place_of_death']) ?></p>
        <p>Book Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
        <p>Start time: <?= htmlspecialchars($row['Start_time']) ?></p> <br>
        <a href="scheduled.admin.burial.php?id=<?= urlencode($row['burial_requirements_id']) ?>" class="view-more-btn">View More</a>
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
        <h4>Son/Daugther: <?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_middle_name']. ' '. $row['child_last_name']) ?></h4>
        <p>Father: <?= htmlspecialchars($row['father_first_name'] . ' ' . $row['father_last_name']. ' ' . $row['father_last_name']) ?></p>
        <p>Mother: <?= htmlspecialchars($row['mother_first_name'] . ' ' . $row['mother_last_name']. ' ' . $row['mother_last_name']) ?></p>
        <p>Book Date: <?= htmlspecialchars($row['Book_Date']) ?></p>
        <p>Start time: <?= htmlspecialchars($row['Start_time']) ?></p> <br>
        <a href="scheduled.admin.baptismal.php?id=<?= urlencode($row['baptismal_bookings_id']) ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending baptismal requests found.</p>
  <?php endif; ?>
</div>

</body>
</html>
