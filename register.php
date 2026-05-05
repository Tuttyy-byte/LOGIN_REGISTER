<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($fullname) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already registered. Please use a different email or login.";
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $sql = "INSERT INTO users (fullname, email, password) VALUES ('$fullname', '$email', '$hashed_password')";
        
        if (mysqli_query($conn, $sql)) {
            // Registration successful - redirect to login page with success message
            header("Location: index.php?success=Registration successful! Please login with your credentials.&show=login");
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
    }
    
    // If there are errors, redirect back to register form with error message
    if (!empty($errors)) {
        $error_message = implode(" ", $errors);
        header("Location: index.php?error=" . urlencode($error_message) . "&show=register");
        exit();
    }
}

mysqli_close($conn);
?>