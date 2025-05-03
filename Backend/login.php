<?php
// require_once '/includes/session_check.php';
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $input['loginInput'], $input['loginInput']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    if (!password_verify($input['password'], $user['password'])) {
        http_response_code(401);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        exit;
    }

    session_start();
    $_SESSION['user_id'] = $user['id'];

    $token = bin2hex(random_bytes(32));

    ob_clean();
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error']);
}


// require_once 'db.php';
// header('Content-Type: application/json');

// $input = json_decode(file_get_contents('php://input'), true);

// try {
//     // Find user
//     $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ?");
//     $stmt->bind_param("ss", $input['loginInput'], $input['loginInput']);
//     $stmt->execute();
//     $user = $stmt->get_result()->fetch_assoc();

//     if (!$user || !password_verify($input['password'], $user['password'])) {
//         http_response_code(401);
//         ob_clean();
//         echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
//         exit;
//     }

//     // Start session
//     session_start();
//     $_SESSION['user_id'] = $user['id'];
    
//     // Generate token
//     $token = bin2hex(random_bytes(32));
    
//     ob_clean();
//     echo json_encode([
//         'success' => true,
//         'token' => $token,
//         'user' => [
//             'id' => $user['id'],
//             'username' => $user['username']
//         ]
//     ]);

// } catch (Exception $e) {
//     http_response_code(500);
//     ob_clean();
//     echo json_encode(['success' => false, 'message' => 'Database error']);
// }
?>