<?php
function getDbConnection() {
    $host = 'localhost';
    $user = 'root'; // Change if needed
    $pass = '';
    $dbname = 'inventory_db'; // Change to your DB name
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    return $conn;
}
