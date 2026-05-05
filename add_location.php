<?php
session_start();
require_once 'weather_db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first.");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_location'])) {
    $city_name = mysqli_real_escape_string($weather_conn, $_POST['city_name']);
    $country = mysqli_real_escape_string($weather_conn, $_POST['country']);
    $region = mysqli_real_escape_string($weather_conn, $_POST['region']);
    
    $sql = "INSERT INTO locations (city_name, country, region) VALUES ('$city_name', '$country', '$region')";
    
    if (mysqli_query($weather_conn, $sql)) {
        header("Location: weather_dashboard.php?success=Location added successfully!");
    } else {
        header("Location: weather_dashboard.php?error=Failed to add location.");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Location | Weather System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <div class="header">
                <h1>📍 Add New Location</h1>
                <