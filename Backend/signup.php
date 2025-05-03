<?php

require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['username']) || empty($input['email']) || empty($input['phone']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Check if user already exists by email or username
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $input['email'], $input['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'User already exists']);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $input['username'], $input['email'], $input['phone'], $hashedPassword);
    $stmt->execute();
    
    // Generate mock token (you can later implement real JWT/session)
    $token = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'token' => $token,
        'user' => [
            'username' => $input['username'],
            'email' => $input['email'],
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}



// require_once 'db.php';
// header('Content-Type: application/json');

// $input = json_decode(file_get_contents('php://input'), true);

// // Validation
// if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
//     http_response_code(400);
//     echo json_encode(['success' => false, 'message' => 'All fields are required']);
//     exit;
// }

// try {
//     // Check existing user
//     $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
//     $stmt->bind_param("ss", $input['email'], $input['username']);
//     $stmt->execute();
    
//     if ($stmt->get_result()->num_rows > 0) {
//         http_response_code(409);
//         echo json_encode(['success' => false, 'message' => 'User already exists']);
//         exit;
//     }

//     // Hash password
//     $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);
    
//     // Insert user
//     $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
//     $stmt->bind_param("ssss", $input['username'], $input['email'], $input['phone'], $hashedPassword);
//     $stmt->execute();

//     echo json_encode([
//         'success' => true,
//         'message' => 'Registration successful'
//     ]);

// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
// }
?>