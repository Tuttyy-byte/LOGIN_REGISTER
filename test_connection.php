<?php
require_once 'db_connection.php';

echo "<h2>Database Connection Test</h2>";
echo "✅ Connected successfully to database: " . $database . "<br>";

// Test query to show tables
$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

echo "<h3>Tables in database:</h3>";
if (mysqli_num_rows($result) > 0) {
    echo "<ul>";
    while($row = mysqli_fetch_array($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "No tables found. Please run the SQL setup first.";
}

mysqli_close($conn);
?>