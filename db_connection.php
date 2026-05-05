<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "itfest_sql_competition";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
?>