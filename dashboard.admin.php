<?php
include('config.php');
session_start();

if (!isset($_SESSION['username'])) {
    echo "<script>window.open('login.php','_self')</script>";
    exit();
}

$username = $_SESSION['username'];
$counts = [
    'pending' => ['wedding' => 0, 'burial' => 0, 'baptismal' => 0],
    'approved' => ['wedding' => 0, 'burial' => 0, 'baptismal' => 0],
    'ongoing' => ['wedding' => 0, 'burial' => 0, 'baptismal' => 0],
    'archived' => ['wedding' => 0, 'burial' => 0, 'baptismal' => 0]
];

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
$today = date('Y-m-d');

$types = [
    'wedding' => [
        'table' => 'wedding_applications',
        'event_column' => 'wedding_application_id',
        'id_column' => 'wedding_applications_id'
    ],
    'burial' => [
        'table' => 'burial_requirements',
        'event_column' => 'burial_requirement_id',
        'id_column' => 'burial_requirements_id'
    ],
    'baptismal' => [
        'table' => 'baptismal_bookings',
        'event_column' => 'baptismal_booking_id',
        'id_column' => 'baptismal_bookings_id'
    ]
];


foreach ($types as $type => $info) {
    $stmt = $conn->prepare("
        SELECT 
            e.Status,
            e.Book_Date
        FROM event e
        JOIN {$info['table']} t ON e.{$info['event_column']} = t.{$info['id_column']}
        WHERE t.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['Status']);
        $bookDate = $row['Book_Date'];

        if ($status === 'pending') {
            $counts['pending'][$type]++;
        } elseif ($status === 'approved') {
            if ($bookDate >= $today) {
                $counts['ongoing'][$type]++;
            } else {
                $counts['archived'][$type]++;
            }
        } elseif ($status === 'archived') {
            $counts['archived'][$type]++;
        }
    }

    $stmt->close();
}


$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Patrick Hand', cursive;
            background-color: #ece8dd;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            background-color: #955c5c;
            color: white;
            width: 220px;
            padding: 20px;
            font-size: 24px;
        }

        .main {
            flex-grow: 1;
            padding: 30px;
            background-color: #e4db9c;
        }

        h1 {
            font-size: 40px;
            margin-bottom: 30px;
        }

        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: nowrap;
            overflow-x: auto; /* Optional: enables horizontal scrolling if cards overflow */
        }


        .card {
            background-color: #955c5c;
            color: white;
            border-radius: 10px;
            padding: 20px;
            font-size: 20px;
            line-height: 1.6;
            min-height: 150px;
        }

        .card h3 {
            margin-bottom: 10px;
            font-size: 22px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    Dashboard
</div>

<div class="main">
    <h1>Dashboard</h1>
        <div class="cards">
            <div class="card">
        <h3>Pending Request</h3>
        <p>Wedding: <?= $counts['pending']['wedding'] ?></p>
        <p>Baptismal: <?= $counts['pending']['baptismal'] ?></p>
        <p>Burial: <?= $counts['pending']['burial'] ?></p>
    </div>

    <div class="card">
        <h3>Approved Request</h3>
        <p>Wedding: <?= $counts['approved']['wedding'] ?></p>
        <p>Baptismal: <?= $counts['approved']['baptismal'] ?></p>
        <p>Burial: <?= $counts['approved']['burial'] ?></p>
    </div>
    

    <div class="card">
        <h3>Ongoing Events</h3>
        <p>Wedding: <?= $counts['ongoing']['wedding'] ?></p>
        <p>Baptismal: <?= $counts['ongoing']['baptismal'] ?></p>
        <p>Burial: <?= $counts['ongoing']['burial'] ?></p>
    </div>

    <div class="card">
        <h3>Archives</h3>
        <p>Wedding: <?= $counts['archived']['wedding'] ?></p>
        <p>Baptismal: <?= $counts['archived']['baptismal'] ?></p>
        <p>Burial: <?= $counts['archived']['burial'] ?></p>
    </div>

    </div>
</div>

</body>
</html>
