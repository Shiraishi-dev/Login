<?php
include('config.php');
session_start(); // Start the session

// Login Logic
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM user WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['user_id'] = $row['user_id'];

            if ($row['user_type'] === 'admin') {
                echo "<script>window.open('wedding.admin.php','_self')</script>";
            } else {
                echo "<script>window.open('user.php','_self')</script>";
            }
        } else {
            echo "<script>alert('Invalid username or password!')</script>";
        }
    } else {
        echo "<script>alert('Invalid username or password!')</script>";
    }
}

// Register Logic
if (isset($_POST['register'])) {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $mobile = $conn->real_escape_string($_POST['mobile_number']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = "user";

    // Check if email or mobile number already exists
    $check_sql = "SELECT * FROM user WHERE email='$email' OR mobile_number='$mobile'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        if ($existing['email'] == $email) {
            echo "<script>alert('Email is already registered!')</script>";
        } elseif ($existing['mobile_number'] == $mobile) {
            echo "<script>alert('Mobile number is already registered!')</script>";
        }
    } else {
        // Use prepared statement
        $sql = $conn->prepare("INSERT INTO user (fullname, email, username, password, mobile_number, user_type) VALUES (?, ?, ?, ?, ?, ?)");
        $sql->bind_param("ssssss", $fullname, $email, $username, $password, $mobile, $user_type);

        if ($sql->execute()) {
            echo "<script>alert('Successfully Registered!')</script>";
            echo "<script>window.open('index.php','_self')</script>";
        } else {
            echo "<script>alert('Error: " . $sql->error . "')</script>";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Corpus Christi Parish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/main-login.css">
    <link rel="stylesheet" href="styles/design-main.css">
</head>
<body>

<!-- Login Section -->
<div class="login-form" id="login-section">
    <h1>Login</h1>
    <div class="container">
        <div class="main">
            <div class="content active" id="login-form">
                <h2>Log In</h2>
                <form method="POST">
                    <input type="text" name="username" placeholder="Username">
                    <input type="password" name="password" placeholder="Password">
                    <button class="btn" type="submit" name="login">Login</button>
                </form>
                <p class="account">Don't have an account? <a href="#" onclick="showForm('register')">Register</a></p>
            </div>
            <div class="form-img">
                <img src="includes/logo.jpg" class="img">
            </div>
        </div>
    </div>
</div>

<!-- Register Section -->
<div class="login-form" id="register-section" style="display: none;">
    <h1>Create Account</h1>
    <div class="container">
        <div class="main">
            <div class="content">
                <h2>Create Account</h2>
                <form method="POST">
                    <input type="text" name="fullname" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="mobile_number" placeholder="Mobile Number" required>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button class="btn" type="submit" name="register">Create</button>
                </form>
                <p class="account">Already have an account? <a href="#" onclick="showForm('login')">Login</a></p>
            </div>
            <div class="form-img">
                <img src="includes/logo.jpg" class="img">
            </div>
        </div>
    </div>
</div>

<!-- JS Form Toggle -->
<script>
    function showForm(form) {
        const login = document.getElementById('login-section');
        const register = document.getElementById('register-section');

        if (form === 'register') {
            login.style.display = 'none';
            register.style.display = 'block';
        } else {
            login.style.display = 'block';
            register.style.display = 'none';
        }
    }
</script>

</body>
</html>
