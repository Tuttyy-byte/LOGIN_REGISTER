<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email) || empty($password)) {
        header("Location: index.php?error=Please enter both email and password.");
        exit();
    }
    
    // Query to get user
    $sql = "SELECT id, fullname, email, password FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['user_email'] = $user['email'];
            
            // Redirect to dashboard or home page
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid password
            header("Location: index.php?error=Invalid email or password.");
            exit();
        }
    } else {
        // User not found
        header("Location: index.php?error=Invalid email or password.");
        exit();
    }
}

mysqli_close($conn);
?>