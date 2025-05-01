<?php
// Database initialization script
// Run this script to create database tables and sample data

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully<br>";

// Read SQL file
$sql = file_get_contents(__DIR__ . '/database.sql');

// Execute SQL script
if ($conn->multi_query($sql)) {
    echo "Database created successfully<br>";
    echo "Tables created successfully<br>";
    echo "Sample data inserted successfully<br>";
    echo "<br>You can now go back to the <a href='../index.php'>main application</a>";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->close();
?> 