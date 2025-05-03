<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  exit('Unauthorized');
}

require 'db.php';
$userId = $_SESSION['user_id'];

$result = $conn->query("SELECT topic, progress_percent FROM progress WHERE user_id = $userId");
$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = [
    'topic' => $row['topic'],
    'progress' => $row['progress_percent']
  ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>
