<?php
// Enable strict session security
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true, // Enable in production (HTTPS only)
    'use_strict_mode' => true
]);

// Auto-logout after 30 minutes of inactivity
$timeout = 300;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../index.html?session_expired=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Authentication check
if (!isset($_SESSION['user_id'])) {
    // Log request headers to debug AJAX detection
    error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
    error_log('Requested With: ' . $_SERVER['HTTP_X_REQUESTED_WITH']);  // This will show if the request is AJAX

    // Check if the request is an AJAX request or a backend API call
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        // Return JSON for API/backend calls
        header('Content-Type: application/json');
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
    } else {
        // Fallback for direct browser navigation
        header("Location: ../login.php?auth_required=1");
    }
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html?auth_required=1");
    exit();
}
if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'localhost') === false) {
    http_response_code(403);
    header("Location: ../index.html");
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit();
}


?>