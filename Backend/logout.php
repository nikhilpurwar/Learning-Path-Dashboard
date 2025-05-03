<?php
require_once __DIR__ . '/includes/session_check.php';

// Clear session data
$_SESSION = [];
session_destroy();

// Clear client-side storage
header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');

// Redirect to login
header("Location: ../index.html?logout=1");
exit();
?>