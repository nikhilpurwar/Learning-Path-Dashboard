<?php
session_start();
require '../Backend/db.php';

$action = $_POST['action'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($action === 'signup') {
  if ($username && $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO ausers (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hash);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
      echo "Signup successful.";
    } else {
      echo "Signup failed. Username may already exist.";
    }
    $stmt->close();
  } else {
    echo "All fields are required.";
  }
}

if ($action === 'login') {
  if ($username && $password) {
    $stmt = $conn->prepare("SELECT id, password FROM ausers WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        echo "Login successful";
      } else {
        echo "Invalid password";
      }
    } else {
      echo "User not found";
    }
    $stmt->close();
  } else {
    echo "All fields are required.";
  }
}

$conn->close();
?>
