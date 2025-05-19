<?php
session_start(); // Start the session

// Destroy the session to log the user out
session_destroy();

// Redirect the user to the login page
echo "<script>window.open('index.php','_self')</script>";
exit(); // Ensure the rest of the page doesn't load
?>
