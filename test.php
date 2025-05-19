<?php
include('config.php');

// Handle form submission
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chosen_date = $_POST['date_of_burial'];

    // Check if it's weekend
    $dayOfWeek = date('w', strtotime($chosen_date)); // 0 = Sunday, 6 = Saturday
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $errors[] = "Weekends are not allowed.";
    }

    // Check if date is already approved
    $stmt = $conn->prepare("SELECT COUNT(*) FROM burial_requirements WHERE date_of_burial = ? AND status = 'approved'");
    $stmt->bind_param("s", $chosen_date);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $errors[] = "This date is already booked.";
    }

    if (empty($errors)) {
        // Proceed to store the burial request
        echo "<p style='color: green;'>Date accepted! Proceed to save the data.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Burial Date Picker</title>
</head>
<body>
  <h2>Choose a Burial Date</h2>

  <?php
  if (!empty($errors)) {
      echo "<ul style='color:red;'>";
      foreach ($errors as $error) {
          echo "<li>$error</li>";
      }
      echo "</ul>";
  }
  ?>

  <form method="post">
    <label for="date_of_burial">Burial Date:</label><br>
    <input type="date" id="date_of_burial" name="date_of_burial" min="<?php echo date('Y-m-d'); ?>" required>
    <br><br>
    <button type="submit">Submit</button>
  </form>
</body>
</html>
