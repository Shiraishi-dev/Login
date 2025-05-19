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

// Fetch approved burial/wedding dates and times, grouped by date
$bookedSlots = [];
$sql = "SELECT Book_Date, Start_time FROM event WHERE status = 'approved'";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $date = $row['Book_Date'];
    $time = $row['Start_time'];
    if (!isset($bookedSlots[$date])) {
        $bookedSlots[$date] = [];
    }
    $bookedSlots[$date][] = $time;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chosen_date = $_POST['Book_Date'];
    $chosen_time = $_POST['Start_time'];
    $dayOfWeek = date('w', strtotime($chosen_date));

    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $errors[] = "Weekends are not allowed.";
    }

    // Check if date and time are already approved
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
        include('upload_wedding_files.php');
        $submissionMessage = "Wedding request submitted successfully.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Wedding Form</title>
    <link rel="stylesheet" href="styles/Wedding.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        select option:disabled {
            color: red;
        }
        .unavailable {
            color: red;
            font-weight: bold;
        }
        .unavailable::after {
            content: " (Unavailable)";
        }
    </style>
</head>
<body>
    <a href="index1.php" class="go-back">GO BACK</a>
    <h1 class="title">Wedding</h1>

    <div class="container">
        <form method="POST" enctype="multipart/form-data">
            <div class="attachment-section">
                <h2>Attachment Requirements</h2>
                <div class="attachments">
                    <label>Marriage License<br /><input type="file" name="marriage_license" accept=".pdf,.jpg,.png" required /></label>
                    <label>Application Form (Marriage)<br /><input type="file" name="application_form" accept=".pdf,.jpg,.png" required /></label>
                    <label>Birth Certificates<br /><input type="file" name="birth_certificates" accept=".pdf,.jpg,.png" required /></label>
                    <label>Certificate of No Marriage<br /><input type="file" name="certificate_of_no_marriage" accept=".pdf,.jpg,.png" required /></label>
                    <label>Community Tax Certificate<br /><input type="file" name="community_tax_certificate" accept=".pdf,.jpg,.png" required /></label>
                    <label>Parental Consent/Advice (Groom) <br><input type="file" name="parental_consent_advice_groom" accept=".pdf,.jpg,.png" /></label>
                    <label>Parental Consent/Advice (Bride) <br><input type="file" name="parental_consent_advice_bride" accept=".pdf,.jpg,.png" /></label>
                    <label>Valid IDs (Groom)<br /><input type="file" name="valid_ids_groom" accept=".pdf,.jpg,.png" required /></label>
                    <label>Valid IDs (Bride)<br /><input type="file" name="valid_ids_bride" accept=".pdf,.jpg,.png" required /></label>
                    <label>Barangay Certificate<br /><input type="file" name="barangay_certificate" accept=".pdf,.jpg,.png" required /></label>
                    <label>Canonical Interview<br /><input type="file" name="canonical_interview" accept=".pdf,.jpg,.png" required /></label>
                </div>
            </div>

            <div class="form-section">
                <h2>Fill up this form</h2>
                <h3>Wife Information</h3>
                <div class="form-row">
                    <input type="text" name="wife_first_name" placeholder="Wife's First Name" required>
                    <input type="text" name="wife_middle_name" placeholder="Wife's Middle Name" required>
                    <input type="text" name="wife_last_name" placeholder="Wife's Last Name" required>
                    <input type="number" name="wife_age" placeholder="Wife Age" required>
                </div>
                <h3>Husband Information</h3>
                <div class="form-row">
                    <input type="text" name="husband_first_name" placeholder="Husband's First Name" required>
                    <input type="text" name="husband_middle_name" placeholder="Husband's Middle Name" required>
                    <input type="text" name="husband_last_name" placeholder="Husband's Last Name" required>
                    <input type="number" name="husband_age" placeholder="Husband Age" required>
                </div>
                <h3>Wedding Date</h3>
                <div class="form-row">
                    <input type="text" id="date_of_wedding" name="Book_Date" required readonly placeholder="Select Wedding Date">
                </div>
                <h4>Time of Wedding (Choose between 9:00 AM and 1:00 PM)</h4>
                <div class="form-row">
                    <select name="Start_time" id="time_of_wedding" required>
                        <option value="">Select Time</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="13:00:00">1:00 PM</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="submit-btn">SUBMIT</button>
        </form>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    const bookedSlots = <?php echo json_encode($bookedSlots); ?>;

flatpickr("#date_of_wedding", {
   dateFormat: "Y-m-d",
    disable: [
        function(date) {
            const d = flatpickr.formatDate(date, "Y-m-d");
            // Disable weekends
            if (date.getDay() === 0) return true;

            // Disable dates where both time slots are fully booked
            if (bookedSlots[d]) {
                const times = bookedSlots[d];
                if (times.includes("09:00:00") && times.includes("13:00:00")) {
                    return true;
                }
            }
            return false;
        }
    ],
    minDate: "today",
    onChange: function(selectedDates, dateStr) {
        const timeSelect = document.getElementById("time_of_wedding");

        // Enable all time options initially
        Array.from(timeSelect.options).forEach(opt => {
            opt.disabled = false;
            opt.style.color = '';
        });

        if (bookedSlots[dateStr]) {
            bookedSlots[dateStr].forEach(time => {
                const option = timeSelect.querySelector(`option[value="${time}"]`);
                if (option) {
                    option.disabled = true;
                    option.style.color = "red";
                }
            });
        }
    }
});


    </script>

    <?php if (!empty($submissionMessage)): ?>
        <script>alert("<?= addslashes($submissionMessage); ?>");</script>
    <?php endif; ?>
</body>
</html>
