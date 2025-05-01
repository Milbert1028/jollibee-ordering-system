<?php
// Script to check database tables and schema
include_once 'config/config.php';

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if tables exist and get table information
$tables_query = "SHOW TABLES FROM " . DB_NAME;
$tables_result = mysqli_query($conn, $tables_query);

echo "<h2>Database Tables in " . DB_NAME . ":</h2>";

if (mysqli_num_rows($tables_result) > 0) {
    echo "<ul>";
    while ($table = mysqli_fetch_row($tables_result)) {
        echo "<li>$table[0]</li>";
        
        // Get table structure
        $structure_query = "DESCRIBE $table[0]";
        $structure_result = mysqli_query($conn, $structure_query);
        
        if (mysqli_num_rows($structure_result) > 0) {
            echo "<ul>";
            while ($column = mysqli_fetch_assoc($structure_result)) {
                echo "<li>{$column['Field']} - {$column['Type']} - {$column['Key']}</li>";
            }
            echo "</ul>";
        }
    }
    echo "</ul>";
} else {
    echo "No tables found in database.";
}

// Check users table specifically
echo "<h2>Users Table Data:</h2>";
$users_query = "SELECT * FROM users LIMIT 10";
$users_result = mysqli_query($conn, $users_query);

if ($users_result) {
    if (mysqli_num_rows($users_result) > 0) {
        echo "<table border='1'>";
        echo "<tr>";
        
        // Get field names
        $fields = mysqli_fetch_fields($users_result);
        foreach ($fields as $field) {
            echo "<th>{$field->name}</th>";
        }
        echo "</tr>";
        
        // Get data
        while ($row = mysqli_fetch_assoc($users_result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "No users found in the database.";
    }
} else {
    echo "Error querying users table: " . mysqli_error($conn);
}

// Check checkout process
echo "<h2>Orders Table Structure:</h2>";
$orders_query = "DESCRIBE orders";
$orders_result = mysqli_query($conn, $orders_query);

if ($orders_result) {
    echo "<ul>";
    while ($column = mysqli_fetch_assoc($orders_result)) {
        echo "<li>{$column['Field']} - {$column['Type']} - {$column['Key']}</li>";
    }
    echo "</ul>";
} else {
    echo "Error querying orders table structure: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 