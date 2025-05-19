<?php
include('config.php');
session_start();

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];
$submissionMessage = '';
$errors = [];

// Fetch approved burial dates and times, grouped by date
$approvedTimes = [];
$sql = "SELECT Book_Date, Start_time FROM event WHERE status = 'approved'";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $date = $row['Book_Date'];
    $time = $row['Start_time'];
    if (!isset($approvedTimes[$date])) {
        $approvedTimes[$date] = [];
    }
    $approvedTimes[$date][] = $time;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chosen_date = $_POST['Book_Date'];
    $chosen_time = $_POST['Start_time'];  // Added to validate selected time
    $dayOfWeek = date('w', strtotime($chosen_date));

    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $errors[] = "Weekends are not allowed.";
    }

    // Check if date and time are already booked
    $stmt = $conn->prepare("SELECT COUNT(*) FROM event WHERE Book_Date = ? AND Start_time = ? AND status = 'approved'");
    $stmt->bind_param("ss", $chosen_date, $chosen_time);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $errors[] = "This date and time is already booked.";
    }

    if (empty($errors)) {
        include('upload_burial_files.php');
        $submissionMessage = "Burial request submitted successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Burial Form</title>
    <link rel="stylesheet" href="styles/Wedding.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <a href="index1.php" class="go-back">GO BACK</a>
    <h1 class="title">Burial Request Form</h1>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($submissionMessage)): ?>
        <div class="submission-message" style="color: green;"><?= htmlspecialchars($submissionMessage) ?></div>
    <?php endif; ?>

    <div class="container">
        <form method="POST" enctype="multipart/form-data">
            <div class="attachment-section">
                <h2>Attachment Requirements</h2>
                <div class="attachments">
                    <label>Death Certificate<br /><input type="file" name="death_certificate" required /></label>
                    <label>Barangay Clearance<br /><input type="file" name="barangay_clearance" required /></label>
                    <label>Valid ID of Informant<br /><input type="file" name="valid_id" required /></label>
                </div>
            </div>

            <div class="form-section">
                <h2>Deceased Information</h2>
                <div class="form-row">
                    <span>Deceased Name: </span><br><input type="text" name="deceased_name" placeholder="Full Name of Deceased" required>
                    <span>Date of Death: </span><br><input type="text" id="death_date" name="date_of_death" required>
                    <span>Place of Death: </span><br><input type="text" name="place_of_death" placeholder="Place of Death" required>
                    <span>Funeral Home: </span><br><input type="text" name="funeral_home" placeholder="Funeral Home Name" required>
                    <span>Date of Funeral: </span><br><input type="text" id="burial_date" name="Book_Date" required>
                </div>
                <h4>Time of Burial</h4>
                <div class="form-row">
                    <select name="Start_time" id="time_of_burial" required>
                        <option value="">Select Time</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="13:00">1:00 PM</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">SUBMIT</button>
            </div>
        </form>
    </div>

<script>
    // Pass approved times to JS
    const approvedTimes = <?= json_encode($approvedTimes); ?>;

    flatpickr("#burial_date", {
        dateFormat: "Y-m-d",
        disable: [
            function(date) {
                const d = flatpickr.formatDate(date, "Y-m-d");
                // Disable weekends
                if (date.getDay() === 0 || date.getDay() === 6) return true;

                // Disable dates where both time slots are fully booked
                if (approvedTimes[d]) {
                    // If both times 09:00 and 13:00 booked, disable the date
                    const times = approvedTimes[d];
                    if (times.includes("09:00:00") && times.includes("13:00:00")) {
                        return true;
                    }
                }
                return false;
            }
        ],
        minDate: "today",
        onChange: function(selectedDates, dateStr) {
            const timeSelect = document.getElementById("time_of_burial");

            // Enable all time options initially
            Array.from(timeSelect.options).forEach(opt => {
                opt.disabled = false;
                opt.style.color = '';
            });

            if (approvedTimes[dateStr]) {
                approvedTimes[dateStr].forEach(time => {
                    // Normalize to HH:mm format for matching option value
                    let normalized = time;
                    if (time === "09:00:00") normalized = "09:00";
                    else if (time === "13:00:00") normalized = "13:00";

                    const option = timeSelect.querySelector(`option[value="${normalized}"]`);
                    if (option) {
                        option.disabled = true;
                        option.style.color = "red";
                    }
                });
            }
        }
    });

    flatpickr("#death_date", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });
</script>

</body>
</html>
