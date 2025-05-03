<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);

// Check if the data is valid
if (isset($data['user_id'], $data['course_id'], $data['progress'], $data['enrolled_at'])) {
    $userId = $data['user_id'];
    $courseId = $data['course_id'];
    $progress = $data['progress'];
    $enrolledAt = $data['enrolled_at'];

    // Prepare the SQL query to insert into user_courses table
    $stmt = $conn->prepare("INSERT INTO user_courses (user_id, course_id, progress, enrolled_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $userId, $courseId, $progress, $enrolledAt);

    // Try executing the query
    try {
        if ($stmt->execute()) {
            // Successfully enrolled
            echo json_encode(['success' => true]);
        }
    } catch (mysqli_sql_exception $e) {
        // Check if error is a duplicate entry (Violation of UNIQUE constraint)
        if ($e->getCode() === 1062) {
            // User is already enrolled in the course
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course.']);
        } else {
            // Some other database error
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    $stmt->close();
} else {
    // Invalid data
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}

$conn->close();


// header('Content-Type: application/json');

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405);
//     echo json_encode(['success' => false, 'message' => 'Method not allowed']);
//     exit;
// }

// $input = json_decode(file_get_contents('php://input'), true);
// $courseId = filter_var($input['courseId'] ?? null, FILTER_VALIDATE_INT);

// if (!$courseId) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
//     exit;
// }

// try {
//     // Check if user is already enrolled
//     $stmt = $conn->prepare("
//         SELECT progress FROM user_courses 
//         WHERE user_id = ? AND course_id = ?
//     ");
//     $stmt->execute([$_SESSION['user_id'], $courseId]);
//     $enrollment = $stmt->fetch();

//     if ($enrollment) {
//         // Update last accessed time if already enrolled
//         $stmt = $conn->prepare("
//             UPDATE user_courses SET last_accessed = NOW() 
//             WHERE user_id = ? AND course_id = ?
//         ");
//         $stmt->execute([$_SESSION['user_id'], $courseId]);
//     } else {
//         // New enrollment
//         $stmt = $conn->prepare("
//             INSERT INTO user_courses (user_id, course_id, progress, enrolled_at, last_accessed)
//             VALUES (?, ?, 0, NOW(), NOW())
//         ");
//         $stmt->execute([$_SESSION['user_id'], $courseId]);
//     }

//     echo json_encode([
//         'success' => true,
//         'message' => $enrollment ? 'Course resumed' : 'Enrollment successful'
//     ]);
// } catch (PDOException $e) {
//     error_log("Enrollment error: " . $e->getMessage());
//     http_response_code(500);
//     echo json_encode(['success' => false, 'message' => 'Database error']);
// }
?>