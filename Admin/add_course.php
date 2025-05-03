<?php
require_once '../Backend/includes/session_check.php';

// session_start();
// if (!isset($_SESSION['user_id'])) {
//   http_response_code(403);
//   exit('Unauthorized');
// }

require '../Backend/db.php';

// Sanitize user input to prevent XSS and SQL injection
$courseName = htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($_POST['subject'] ?? '', ENT_QUOTES, 'UTF-8');$author = htmlspecialchars($_POST['author'] ?? '', ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
$price = floatval($_POST['price'] ?? 0);
$category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');$resourceLink = filter_var($_POST['resource_link'] ?? '', FILTER_SANITIZE_URL);
$videoLink = filter_var($_POST['video_link'] ?? '', FILTER_SANITIZE_URL);
$userId = $_SESSION['user_id'];

// Initialize image and resource file names
$imageName = '';
$resourceFileName = '';

// Handle course image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $targetDir = 'uploads/';
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);  // Create folder with appropriate permissions
    }

    // Validate image file type (only allow certain extensions)
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($_FILES['image']['tmp_name']);
    
    if (in_array($fileType, $allowedImageTypes)) {
        $imageName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            echo "Error uploading image.";
            exit;
        }
    } else {
        echo "Invalid image file type.";
        exit;
    }
}

// Handle resource file upload (PDF/Docs)
if (isset($_FILES['upload_resource']) && $_FILES['upload_resource']['error'] == 0) {
    $allowedResourceTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint'];
    $resourceFileType = mime_content_type($_FILES['upload_resource']['tmp_name']);
    
    if (in_array($resourceFileType, $allowedResourceTypes)) {
        $resourceFileName = basename($_FILES['upload_resource']['name']);
        $targetResourceFile = $targetDir . $resourceFileName;
        if (!move_uploaded_file($_FILES['upload_resource']['tmp_name'], $targetResourceFile)) {
            echo "Error uploading resource file.";
            exit;
        }
    } else {
        echo "Invalid resource file type.";
        exit;
    }
}

// Prepare and bind the SQL query to insert data into the database
$rating = 0;
$stmt = $conn->prepare("INSERT INTO courses (id, title, author, description, image, video_link, resource_link, upload_resource, category, rating, price, subject) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssdds", $userId, $courseName, $author, $description, $imageName, $videoLink, $resourceLink, $resourceFileName, $category, $rating, $price, $subject);

if ($stmt->execute()) {
    echo "Course Added successfully.";
    // header("Location: success_page.php");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
