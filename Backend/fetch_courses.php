<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/db.php';

// Get the user ID from the session
$userId = $_SESSION['user_id'];

// Prepare the SQL query to fetch enrolled courses
$query = "
    SELECT uc.id, c.title, c.description, c.image, c.category 
    FROM user_courses uc
    INNER JOIN courses c ON uc.course_id = c.id
    WHERE uc.user_id = ? ORDER BY uc.enrolled_at DESC
";

// Prepare statement
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch the courses
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Return the courses as JSON
echo json_encode($courses);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
