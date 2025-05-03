<?php
require_once 'includes/session_check.php';
require_once 'db.php';

// Get POST data
$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
$new_rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0;

// Debug output
error_log("Received rating update - Course: $course_id, Rating: $new_rating");
header('Content-Type: application/json');

if ($new_rating >= 1 && $new_rating <= 5) {
    try {
        // Verify user is enrolled and has watched enough
        $enrollment_check = $conn->prepare("
            SELECT 1 FROM user_courses 
            WHERE user_id = ? AND course_id = ? AND progress > 20
        ");
        $enrollment_check->bind_param("ii", $_SESSION['user_id'], $course_id);
        $enrollment_check->execute();
        
        if (!$enrollment_check->get_result()->fetch_assoc()) {
            http_response_code(403);
            echo json_encode(['error' => 'Course completion requirement not met']);
            exit();
        }

        // Get current rating data
        $stmt = $conn->prepare("SELECT rating, rating_count FROM courses WHERE PrimaryID = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $current_rating = $result['rating'] ?? 0;
        $rating_count = $result['rating_count'] ?? 0;
        
        // Calculate new average
        $new_average = (($current_rating * $rating_count) + $new_rating) / ($rating_count + 1);
        
        // Update database
        $update = $conn->prepare("
            UPDATE courses 
            SET rating = ?, 
                rating_count = rating_count + 1 
            WHERE PrimaryID = ?
        ");
        $update->bind_param("di", $new_average, $course_id);
        $update->execute();
        
        // Debug success
        error_log("Rating updated successfully. New average: $new_average");
        
        // SINGLE RESPONSE - removed the duplicate echo
        echo json_encode([
            'status' => 'success',
            'new_rating' => $new_average,
            'rating_count' => $rating_count + 1
        ]);
        
    } catch (Exception $e) {
        error_log("Rating update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed']);
    }
} else {
    error_log("Invalid rating value: $new_rating");
    http_response_code(400);
    echo json_encode(['error' => 'Rating must be between 1-5']);
}
?>