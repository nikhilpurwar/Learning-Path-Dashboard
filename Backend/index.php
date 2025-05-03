<?php

// signup.php
// session_start();
// require 'db.php';

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $name = mysqli_real_escape_string($conn, $_POST['name']);
//     $email = mysqli_real_escape_string($conn, $_POST['email']);
//     $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
//     $query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
//     if (mysqli_query($conn, $query)) {
//         $_SESSION['user'] = ["name" => $name, "email" => $email];
//         $_SESSION['token'] = bin2hex(random_bytes(32));
//         header("Location: dashboard.php");
//         exit();
//     } else {
//         echo "Error: " . mysqli_error($conn);
//     }
// }

// login.php
// session_start();
// require 'db.php';

// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $email = mysqli_real_escape_string($conn, $_POST['email']);
//     $password = $_POST['password'];
    
//     $query = "SELECT * FROM users WHERE email = '$email'";
//     $result = mysqli_query($conn, $query);
    
//     if (mysqli_num_rows($result) > 0) {
//         $user = mysqli_fetch_assoc($result);
//         if (password_verify($password, $user['password'])) {
//             $_SESSION['user'] = ["name" => $user['name'], "email" => $user['email']];
//             $_SESSION['token'] = bin2hex(random_bytes(32));
//             header("Location: dashboard.php");
//             exit();
//         } else {
//             echo "Invalid credentials.";
//         }
//     } else {
//         echo "Invalid credentials.";
//     }
// }

// logout.php
// session_start();
// session_destroy();
// header("Location: ../index.html");
// exit();

// dashboard.php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
    header("Location: login.html");
    exit();
}
echo "Welcome, " . htmlspecialchars($_SESSION['user']['name']);

?>
