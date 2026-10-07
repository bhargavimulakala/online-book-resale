<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "127.0.0.1";
$port = 3306;      // Your XAMPP MySQL port
$user = "root";
$pass = "";
$db   = "online_book_resale";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_errno) {
    die("
    <h2>Database Connection Failed</h2>
    <p>Error: " . $conn->connect_error . "</p>
    <p>Host: $host</p>
    <p>Port: $port</p>
    <p>Database: $db</p>
    ");
}

$conn->set_charset("utf8mb4");