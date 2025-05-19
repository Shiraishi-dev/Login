<?php
include('config.php');
session_start(); // Start the session

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

// Wedding Applications
$sql1 = "SELECT id, husband_first_name, husband_last_name, wife_first_name, wife_last_name 
         FROM wedding_applications 
         WHERE user_id = ? AND status = 'declined'";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$result1 = $stmt1->get_result();
while ($row = $result1->fetch_assoc()) {
    $weddingResults[] = $row;
}
$stmt1->close();

// Burial Requirements
$sql2 = "SELECT id, deceased_name, date_of_burial 
         FROM burial_requirements 
         WHERE user_id = ? AND status = 'declined'";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $burialResults[] = $row;
}
$stmt2->close();

// Baptismal Bookings (fixed: added date_of_baptism)
$sql3 = "SELECT id, child_first_name, child_last_name, father_first_name, father_last_name, mother_first_name, mother_last_name
         FROM baptismal_bookings 
         WHERE user_id = ? AND status = 'declined'";
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
  <h2>Your Approved Requests</h2>

  <!-- Wedding Applications -->
  <h3>Wedding Applications</h3>
  <?php if (!empty($weddingResults)): ?>
    <?php foreach ($weddingResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['husband_first_name'] . ' ' . $row['husband_last_name']) ?> & <?= htmlspecialchars($row['wife_first_name'] . ' ' . $row['wife_last_name']) ?></h4> <br>
        <a href="wedding.details.user.declined.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending wedding applications found.</p>
  <?php endif; ?>

  <!-- Burial Requirements -->
  <h3>Burial Requests</h3>
  <?php if (!empty($burialResults)): ?>
    <?php foreach ($burialResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['deceased_name']) ?> - <?= htmlspecialchars($row['date_of_burial']) ?></h4> <br>
        <a href="burial.details.user.declined.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending burial requests found.</p>
  <?php endif; ?>

  <!-- Baptismal Bookings -->
  <h3>Baptismal Requests</h3>
  <?php if (!empty($baptismalResults)): ?>
    <?php foreach ($baptismalResults as $row): ?>
      <div class="request-card">
        <h4><?= htmlspecialchars($row['child_first_name'] . ' ' . $row['child_last_name']) ?></h4> <br>
        <a href="baptismal.details.user.declined.php?id=<?= $row['id'] ?>" class="view-more-btn">View More</a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No pending baptismal requests found.</p>
  <?php endif; ?>
</div>

<!-- Optional debug output -->
<?php
// Uncomment to test data output
/*
echo "<pre>";
print_r($weddingResults);
print_r($burialResults);
print_r($baptismalResults);
echo "</pre>";
*/
?>

</body>
</html>
