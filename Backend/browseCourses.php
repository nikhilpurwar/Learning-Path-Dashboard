<?php
require_once 'db.php';


if (!isset($_GET['action'])) {
    exit('No action specified');
}

$action = $_GET['action'];

//fetching domain buttons
if ($action == 'domain') {
    $result = $conn->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL");
    while ($row = $result->fetch_assoc()) {
        echo "<button class='domain-btn' data-category='" . htmlspecialchars($row['category']) . "'>" . htmlspecialchars($row['category']) . "</button>";
    }
}

//fetching subject button
if ($action == 'subject' && isset($_POST['category'])) {
    $category = $_POST['category'];
    $stmt = $conn->prepare("SELECT DISTINCT subject FROM courses WHERE category = ? AND subject IS NOT NULL");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p>No subjects found for <strong>$category</strong>.</p>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<button class='subject-btn' data-subject='" . htmlspecialchars($row['subject']) . "'>" . htmlspecialchars($row['subject']) . "</button>";
        }
    }
}

//fetching courses
if ($action == 'course' && isset($_POST['subject'])) {
    $subject = $_POST['subject'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE subject = ?");
    $stmt->bind_param("s", $subject);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "<p>No courses found under <strong>$subject</strong>.</p>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='course-card' style='
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    width: 280px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    background: white;
    transition: all 0.3s ease;
    font-family: \"Poppins\", sans-serif;
    position: relative;
    overflow: hidden;
'>
    <div style='
        background-color: #6C5CE7;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        display: inline-block;
        position: absolute;
        top: 16px;
        left: 16px;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    '>".htmlspecialchars($row['category'] ?? 'Web Development')."</div>
    
    <img class='course-image' src='../Admin/uploads/".htmlspecialchars($row['image'])."' alt='".htmlspecialchars($row['title'])."' 
    style='
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: transform 0.5s ease;
        display: block; 
        margin-left: auto; 
        margin-right: auto;
    ' 
    onerror=\"this.src='../Asset/course-placeholder.png'\" />
    
    <h3 class='course-title' style='
        font-size: 18px;
        font-weight: 600;
        margin: 8px 0;
        color: #2d3748;
        line-height: 1.3;
    '>".htmlspecialchars($row['title'])."</h3>
    
    <p class='course-author' style='
        color: #718096;
        font-size: 14px;
        margin: 4px 0;
        font-weight: 500;
    '>By ".htmlspecialchars($row['author'] ?? "N/A")."</p>
    
    <div style='
        display: flex;
        align-items: center;
        margin: 12px 0;
        gap: 8px;
    '>
        <span style='
            color: #f6ad55;
            font-size: 16px;
            letter-spacing: 2px;
        '>★★★★☆</span>
        <span style='
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        '>".(is_numeric($row['rating']) ? number_format($row['rating'], 1) : 'N/A')."</span>
    </div>
    
    <div style='
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
    '>
        <p class='course-price' style='
            color: #6C5CE7;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        '>₹".htmlspecialchars($row['price'])."</p>
        
        <button class='enroll-btn' style='
            background-color: #6C5CE7;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(106, 13, 173, 0.3);
        ' onclick='enrollCourse(".$row['PrimaryID'].")'>Enroll Now</button>
    </div>
</div>";
        }
       
        
    }
}

//to show active courses
// if ($action == 'mycourses' && isset($_POST['user_id'])) {
//     $user_id = intval($_POST['user_id']);

//     $stmt = $conn->prepare("
//         SELECT uc.id, c.title, c.description, c.image, c.category 
//     FROM user_courses uc
//     INNER JOIN courses c ON uc.course_id = c.id
//     WHERE uc.user_id = ? ORDER BY uc.enrolled_at DESC
//     ");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows === 0) {
//         echo "empty";
//     } else {
//         while ($row = $result->fetch_assoc()) {
//             echo "
//             <div class='course-card' style='position: relative; width: 220px; background: #fff; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 16px; text-align: center; margin: 10px;'>
                
//                 <button class='cancel-course-btn' data-course-id='" . $row['course_id'] . "' 
//                     style='position: absolute; top: 8px; right: 8px; background: transparent; border: none; font-size: 20px; color: #888; cursor: pointer; transition: 0.3s;'>×</button>

//                 <img src='" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['title']) . "' style='width: 100%; height: 120px; object-fit: cover; border-radius: 8px;'>
                
//                 <h4 style='font-size: 16px; font-weight: bold; margin: 8px 0; color: #333;'>" . htmlspecialchars($row['title']) . "</h4>
//                 <p style='font-size: 14px; color: #666;'>By " . htmlspecialchars($row['author']) . "</p>
                
//                 <a href='continueCourse.php?course_id=" . $row['course_id'] . "' class='continue-btn' 
//                     style='display: inline-block; margin-top: 10px; padding: 8px 16px; background: #6a0dad; color: #fff; border-radius: 6px; text-decoration: none; font-size: 14px; transition: 0.3s;'>Continue Course</a>
//             </div>
//             ";
//         }
//     }
// }

//to show active courses
if ($action == 'mycourses') {
    // Accept both GET and POST requests for flexibility
    $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    
    if ($user_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            uc.id as enrollment_id,
            uc.course_id,
            uc.progress,
            c.PrimaryID,
            c.title, 
            c.description, 
            c.image, 
            c.category,
            c.author,
            c.rating,
            TIMESTAMPDIFF(DAY, uc.enrolled_at, NOW()) as days_enrolled
        FROM user_courses uc
        INNER JOIN courses c ON uc.course_id = c.PrimaryID
        WHERE uc.user_id = ? 
        ORDER BY uc.enrolled_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: application/json');
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => true, 'data' => []]);
    } else {
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = [
                'enrollment_id' => $row['enrollment_id'],
                'course_id' => $row['course_id'],
                'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'),
                'image' => '../Admin/uploads/' . htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'),
                'category' => htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'),
                'author' => htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8'),
                'progress' => $row['progress'],
                'rating' => $row['rating'],
                'days_enrolled' => $row['days_enrolled']
            ];
        }
        echo json_encode(['success' => true, 'data' => $courses]);
    }
    exit;
}

//cancel course
if ($action =='cancelCourse' && isset($_POST['user_id'], $_POST['course_id'])) {
    $user_id = intval($_POST['user_id']);
    $course_id = intval($_POST['course_id']);

    $stmt = $conn->prepare("DELETE FROM user_courses WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $user_id, $course_id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
} 


$conn->close();
?>
