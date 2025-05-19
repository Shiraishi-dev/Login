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

$username = $_SESSION['username'];

// Fetch user_id
$user_id = null;
$userQuery = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$result = $userQuery->get_result();
if ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];
}
$userQuery->close();

if (!$user_id) {
    die("User not found.");
}

// File upload function
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

// Get form inputs
$child_first_name      = $_POST['child_first_name'];
$child_middle_name     = $_POST['child_middle_name'];
$child_last_name       = $_POST['child_last_name'];
$child_birth_date      = $_POST['child_birth_date'];
$date_of_baptism       = $_POST['date_of_baptism'];
$Start_time            = $_POST['Start_time'];

$father_first_name     = $_POST['father_first_name'];
$father_middle_name     = $_POST['father_middle_name'];
$father_last_name     = $_POST['father_last_name'];
$mother_first_name     = $_POST['mother_first_name'];
$mother_middle_name    = $_POST['mother_middle_name'];
$mother_last_name      = $_POST['mother_last_name'];

// Upload files
$birth_certificate               = uploadFile('birth_certificate');
$marriage_certificate_of_parents = uploadFile('marriage_certificate_of_parents');
$baptismal_seminar_certificate   = uploadFile('baptismal_seminar_certificate');
$sponsor_list                    = uploadFile('sponsor_list');
$valid_ids                       = uploadFile('valid_ids');
$barangay_certificate            = uploadFile('barangay_certificate');
$canonical_interview             = uploadFile('canonical_interview');

// Insert into baptismal_bookings table
$sql = "INSERT INTO baptismal_bookings (
    user_id, child_first_name, child_middle_name, child_last_name, child_birth_date,
    father_first_name, father_middle_name, father_last_name, mother_first_name, mother_middle_name, mother_last_name,
    birth_certificate, marriage_certificate_of_parents, baptismal_seminar_certificate, sponsor_list,
    valid_ids, barangay_certificate, canonical_interview
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "isssssssssssssssss",
    $user_id, $child_first_name, $child_middle_name, $child_last_name, $child_birth_date,
    $father_first_name, $father_middle_name, $father_last_name ,$mother_first_name, $mother_middle_name, $mother_last_name,
    $birth_certificate, $marriage_certificate_of_parents, $baptismal_seminar_certificate, $sponsor_list,
    $valid_ids, $barangay_certificate, $canonical_interview
);

if ($stmt->execute()) {
    $baptismal_booking_id = $conn->insert_id; // Correct variable for later use

    // Create corresponding event
    $description = "Baptismal for $child_first_name $child_last_name on $date_of_baptism";
    $booking_type = "baptismal";
    $status = "Pending";

    $eventStmt = $conn->prepare("INSERT INTO event (
        description, Book_Date, Start_time, baptismal_booking_id, booking_type, status, user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $eventStmt->bind_param("sssissi", $description, $date_of_baptism, $Start_time, $baptismal_booking_id, $booking_type, $status, $user_id);

    if ($eventStmt->execute()) {
        $event_id = $conn->insert_id;

        // Update baptismal_bookings with event_id
        $updateStmt = $conn->prepare("UPDATE baptismal_bookings SET event_id = ? WHERE baptismal_bookings_id = ?");
        $updateStmt->bind_param("ii", $event_id, $baptismal_booking_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo "<script>
            alert('Baptismal request and event created successfully!');
            window.location.href = 'user.php';
        </script>";
    } else {
        echo "Event creation failed: " . $eventStmt->error;
    }

    $eventStmt->close();

} else {
    echo "Baptismal application failed: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
