<?php
require_once 'includes/session_check.php';
require_once 'db.php';

// Get POST data
$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$progress = isset($_POST['progress']) ? intval($_POST['progress']) : 0;

// Verify enrollment
$enrollment_check = $conn->prepare("SELECT progress FROM user_courses WHERE user_id = ? AND course_id = ?");
$enrollment_check->bind_param("ii", $user_id, $course_id);
$enrollment_check->execute();
$current = $enrollment_check->get_result()->fetch_assoc();

if (!$current) {
    http_response_code(403);
    die(json_encode(['error' => 'Not enrolled in this course']));
}

// Only update if new progress is higher
if ($progress > $current['progress']) {
    try {
        $stmt = $conn->prepare("UPDATE user_courses SET progress = ?, last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
        $stmt->bind_param("iii", $progress, $user_id, $course_id);
        $stmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'new_progress' => $progress,
            'previous_progress' => $current['progress']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([
        'status' => 'no_change',
        'current_progress' => $current['progress']
    ]);
}
?>