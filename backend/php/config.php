<?php
/**
 * Database Configuration and CORS Setup
 * 
 * This file establishes the MySQL database connection and configures
 * CORS headers to allow communication with the Vue.js frontend.
 */

// Configure CORS headers for frontend communication
header('Access-Control-Allow-Origin: http://localhost:8080');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS requests from browsers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 204 No Content');
    exit();
}

// Database connection parameters
$host = 'localhost';
$db   = 'chess360';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Establish MySQL connection
$conn = new mysqli($host, $user, $pass, $db);

// Verify connection and return error if failed
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}