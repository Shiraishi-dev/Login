<?php
include('config.php');
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // If not, redirect them to the login page
    echo "<script>window.open(index.php','_self')</script>";
    exit(); // Ensure the rest of the page doesn't load
}

// Get the logged-in username
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Corpus Christi Parish - User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/design-main.css"/>
</head>
<body>
    <div class="top">
        <header>
            <a href="user.php"><img src="includes/logo.jpg" alt="Corpus Christi Logo" class="logo"/></a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php" class="Home">Home</a></li>
                    <li><a href="index1.php" class="Book-Events">Book Events</a></li>
                    <li><a href="" class="About-Us">About Us</a></li>
                    <!-- Logout Button -->
                    <li>
                        <a href="pending.book.user.php" class="nav-log">My bookings</a>
                        <a href="logout.php" class="nav-log">Logout</a>
                    </li>
                </ul>
            </nav>
        </header>
            
        <main>
            <div class="left-col">
                <!-- Welcome Message with Username -->
                <h1 class="main-text">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p class="second-text">Corpus Christi Parish Event Booking System.</p>
                <p class="option-1">Available Events to book:</p>
                <p class="option-2">Wedding</p>
                <p class="option-3">Baptismal</p>
                <p class="option-4">Burial</p>
                <button class="nav-book"><a href="index1.php" class="nav-book">Book Now</a></button>
            </div>
        </main>
    </div>
</body>
</html>
