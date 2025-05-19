<?php
include('config.php');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];

// Get user_id from username
$user_id = null;
$userQuery = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
if ($userRow = $userResult->fetch_assoc()) {
    $user_id = $userRow['user_id'];
}
$userQuery->close();

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
        } else {
            error_log("File upload failed for field: $field, filename: $filename");
            return null;
        }
    }
    return null;
}

// Collect form data
$wife_first = $_POST['wife_first_name'] ?? null;
$wife_middle = $_POST['wife_middle_name'] ?? null;
$wife_last = $_POST['wife_last_name'] ?? null;
$wife_age = $_POST['wife_age'] ?? null;

$husband_first = $_POST['husband_first_name'] ?? null;
$husband_middle = $_POST['husband_middle_name'] ?? null;
$husband_last = $_POST['husband_last_name'] ?? null;
$husband_age = $_POST['husband_age'] ?? null;

$Book_Date = $_POST['Book_Date'] ?? null;
$Start_time = $_POST['Start_time'] ?? null;

// Validate date and time
if (!$Book_Date || !$Start_time) {
    die("Wedding date and time are required.");
}

// Upload files
$marriage_license = uploadFile("marriage_license");
$application_form = uploadFile("application_form");
$birth_certificates = uploadFile("birth_certificates");
$certificate_of_no_marriage = uploadFile("certificate_of_no_marriage");
$community_tax_certificate = uploadFile("community_tax_certificate");
$parental_consent_advice_groom = uploadFile("parental_consent_advice_groom");
$parental_consent_advice_bride = uploadFile("parental_consent_advice_bride");
$valid_ids_groom = uploadFile("valid_ids_groom");
$valid_ids_bride = uploadFile("valid_ids_bride");
$barangay_certificate = uploadFile("barangay_certificate");
$canonical_interview = uploadFile("canonical_interview");

// Insert into wedding_applications (initial insert without event_id)
$insertWeddingApp = $conn->prepare("INSERT INTO wedding_applications (
    user_id, wife_first_name, wife_middle_name, wife_last_name, wife_age,
    husband_first_name, husband_middle_name, husband_last_name, husband_age,
    marriage_license, application_form, birth_certificates, certificate_of_no_marriage,
    community_tax_certificate, parental_consent_advice_groom, parental_consent_advice_bride,
    valid_ids_groom, valid_ids_bride, barangay_certificate, canonical_interview
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$insertWeddingApp) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$insertWeddingApp->bind_param("isssisssisssssssssss",
    $user_id, $wife_first, $wife_middle, $wife_last, $wife_age,
    $husband_first, $husband_middle, $husband_last, $husband_age,
    $marriage_license, $application_form, $birth_certificates, $certificate_of_no_marriage,
    $community_tax_certificate, $parental_consent_advice_groom, $parental_consent_advice_bride,
    $valid_ids_groom, $valid_ids_bride, $barangay_certificate, $canonical_interview
);

if ($insertWeddingApp->execute()) {
    $wedding_application_id = $conn->insert_id;

    // Insert into event table
    $description = "Wedding event for $husband_first and $wife_first";
    $booking_type = "Wedding";
    $status = "Pending";

    $insertEvent = $conn->prepare("INSERT INTO event (
        description, Book_Date, Start_time, wedding_application_id, booking_type, status
    ) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$insertEvent) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $insertEvent->bind_param("sssiss", $description, $Book_Date, $Start_time, $wedding_application_id, $booking_type, $status);

    if ($insertEvent->execute()) {
        $event_id = $conn->insert_id;

        // Update wedding_applications table with the event_id
        $updateWeddingApp = $conn->prepare("UPDATE wedding_applications SET event_id = ? WHERE wedding_applications_id = ?");
        $updateWeddingApp->bind_param("ii", $event_id, $wedding_application_id);
        $updateWeddingApp->execute();
        $updateWeddingApp->close();

        echo "<script>alert('Wedding application and event created successfully!'); window.location.href='user.php';</script>";
    } else {
        echo "Event insertion error: " . $insertEvent->error;
    }

    $insertEvent->close();
} else {
    echo "Wedding application insertion error: " . $insertWeddingApp->error;
}

$insertWeddingApp->close();
$conn->close();
?>
