<?php
// require_once __DIR__ . '/includes/session_check.php';
// require_once __DIR__ . '/db.php';

// header('Content-Type: application/json');
// header("Cache-Control: no-cache, no-store, must-revalidate");

// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }

// $user_id = $_SESSION['user_id'];

// // Get enrolled courses
// $course_sql = "
//     SELECT c.id, c.title, c.image, c.category, uc.progress 
//     FROM user_courses uc
//     JOIN courses c ON uc.course_id = c.id
//     WHERE uc.user_id = $user_id
// ";
// $course_result = $conn->query($course_sql);
// $courses = [];
// while ($row = $course_result->fetch_assoc()) {
//     $courses[] = $row;
// }

// // Get recommended courses
// $rec_sql = "
//     SELECT id, title, image, category, rating 
//     FROM courses 
//     WHERE category IN (
//         SELECT interest FROM user_interests WHERE user_id = $user_id
//     ) OR rating > 4.5
//     ORDER BY rating DESC 
//     LIMIT 5
// ";
// $rec_result = $conn->query($rec_sql);
// $recommendations = [];
// while ($row = $rec_result->fetch_assoc()) {
//     $recommendations[] = $row;
// }

// // Get profile picture and name
// $pic_sql = "SELECT profile_pic, name FROM users WHERE id = $user_id LIMIT 1";
// $pic_result = $conn->query($pic_sql);

// $profile_pic = null;
// $name = 'User';

// if ($pic_result && $pic_result->num_rows > 0) {
//     $user_data = $pic_result->fetch_assoc();
//     $name = $user_data['name'] ?? 'User';

//     if (!empty($user_data['profile_pic'])) {
//         $profile_pic = 'data:image/jpeg;base64,' . base64_encode($user_data['profile_pic']);
//     }
// }

// // Final response
// echo json_encode([
//     'success' => true,
//     'courses' => $courses,
//     'recommendations' => $recommendations,
//     'user' => [
//         'id' => $user_id,
//         'name' => $name,
//         'profile_pic' => $profile_pic
//     ]
// ]);
?>
