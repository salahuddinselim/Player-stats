<?php
// Simulated database of usernames (in a real app, you'd query an actual database)
$existing_usernames = ['john_doe', 'jane_smith', 'admin123'];

// Get the username from the GET request
$username = $_GET['username'] ?? '';

// Check if username exists
$is_available = !in_array(strtolower($username), array_map('strtolower', $existing_usernames));

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['available' => $is_available]);
?><?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulated database of usernames (in a real app, you'd query an actual database)
$existing_usernames = ['john_doe', 'jane_smith', 'admin123'];

// Get the username from the GET request
$username = $_GET['username'] ?? '';

// Check if username exists (case-insensitive)
$is_available = !in_array(strtolower($username), array_map('strtolower', $existing_usernames));

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['available' => $is_available]);
?>