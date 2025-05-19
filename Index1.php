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
    <title>Corpus Christi Parish</title>
    <link rel="stylesheet" href="styles/index1.css">
  </head>
  <body>
    <div class="header">
      <a href="user.php"><img class="logo" src="includes/logo.jpg" alt="Logo" /></a>
      <div class="title">Corpus Christi Parish: Event Booking</div>
      <h2>Choose an Event</h2>
    </div>

    <div class="event-container">
      <div class="event">
        <a href="Wedding.php">
          <img src="includes/Wedding(4).webp" alt="Wedding" />
          <div class="event-title">WEDDING</div>
        </a>
      </div>
      <div class="event">
        <a href="Baptismal.php">
          <img src="includes/Baptismal.jpg" alt="Baptismal" />
          <div class="event-title">BAPTISMAL</div>
        </a>
      </div>
      <div class="event">
        <a href="Burial.php">
          <img src="includes/Burial.jpg" alt="Burial" />
          <div class="event-title">BURIAL</div>
        </a>
      </div>
    </div>
  </body>
</html>
