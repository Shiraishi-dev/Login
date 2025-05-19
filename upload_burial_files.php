<?php
include('config.php'); // DB connection

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

// Fetch the username from session
$username = $_SESSION['username'];

// Get the user_id from the user table
$user_id = null;
$userQuery = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
if ($userRow = $userResult->fetch_assoc()) {
    $user_id = $userRow['user_id'];
}
$userQuery->close();

// If user_id is still null, stop the script
if (!$user_id) {
    die("User not found.");
}

function uploadFile($field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES[$field]["name"]);
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES[$field]["tmp_name"], $targetFile)) {
            return $targetFile;
        }
    }
    return null;
}

// Collect form data
$deceased_first_name   = $_POST['deceased_first_name'];
$deceased_middle_name   = $_POST['deceased_middle_name'];
$deceased_last_name   = $_POST['deceased_last_name'];
$date_of_death   = $_POST['date_of_death'];
$place_of_death  = $_POST['place_of_death'];
$Book_Date       = $_POST['Book_Date'];
$funeral_home    = $_POST['funeral_home'];
$Start_time      = $_POST['Start_time'];

// Upload attachments
$death_certificate    = uploadFile("death_certificate");
$barangay_clearance   = uploadFile("barangay_clearance");
$valid_id             = uploadFile("valid_id");

// Insert into burial_requirements table
if ($conn) {
    $sql = "INSERT INTO burial_requirements (
        user_id, deceased_first_name, deceased_middle_name, deceased_last_name, date_of_death, place_of_death,
        funeral_home,
        death_certificate, barangay_clearance, valid_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param(
        "isssssssss",
        $user_id, $deceased_first_name, $deceased_middle_name ,$deceased_last_name , $date_of_death, $place_of_death,
        $funeral_home,
        $death_certificate, $barangay_clearance, $valid_id
    );

    if ($stmt->execute()) {
        // Get the burial_requirement_id from the last insert
        $burial_requirement_id = $conn->insert_id;

        $description = "Deceased $deceased_name and $Book_Date";
        $booking_type = "burial";
        $status = "Pending";

        // Insert into event table
        $insertEvent = $conn->prepare("INSERT INTO event (
            description, Book_Date, Start_time, burial_requirement_id, booking_type, status, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");

        $insertEvent->bind_param("sssissi", $description, $Book_Date, $Start_time, $burial_requirement_id, $booking_type, $status, $user_id);

        if ($insertEvent->execute()) {
            $event_id = $conn->insert_id;

            // Update burial_requirements with event_id
            $updateRequirement = $conn->prepare("UPDATE burial_requirements SET event_id = ? WHERE burial_requirements_id = ?");
            $updateRequirement->bind_param("ii", $event_id, $burial_requirement_id);

            if ($updateRequirement->execute()) {
                echo "<script>
                    alert('Burial application and event created successfully!');
                    window.location.href = 'user.php';
                </script>";
            } else {
                echo "Error updating burial_requirements with event_id: " . $updateRequirement->error;
            }

            $updateRequirement->close();
        } else {
            echo "Event insertion error: " . $insertEvent->error;
        }

        $insertEvent->close();
    } else {
        echo "Burial application insertion error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Database connection error.";
}

$conn->close();
?>
