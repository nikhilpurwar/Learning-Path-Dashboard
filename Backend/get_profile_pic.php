<?php
// get_profile_pic.php

require_once 'db.php'; // Make sure this connects correctly

header('Content-Type: application/json');

// Get user ID from query
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId > 0) {
    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($profilePic);
    
    if ($stmt->fetch() && !empty($profilePic)) {
        echo json_encode(['path' => $profilePic]);
    } else {
        echo json_encode(['path' => null]);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid user ID']);
}

$conn->close();
?>
