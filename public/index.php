<?php
session_start();
require_once '../includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>