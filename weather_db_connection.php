<?php
// Separate database connection for weather system
$weather_servername = "localhost";
$weather_username = "root";
$weather_password = "";
$weather_database = "itfest_sql_competition";

$weather_conn = mysqli_connect($weather_servername, $weather_username, $weather_password, $weather_database);

if (!$weather_conn) {
    die("Weather system connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($weather_conn, "utf8");
?>