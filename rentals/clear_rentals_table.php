<?php
// Run this script ONCE to clear all records from the rentals table
require_once '../config/db.php';
$conn = getDbConnection();
$conn->query("DELETE FROM rentals");
echo "All records have been deleted from the rentals table.";
