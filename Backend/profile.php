<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/db.php';

$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowedTypes = ['image/jpeg', 'image/png'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        if (!in_array($_FILES['profile_pic']['type'], $allowedTypes) || $_FILES['profile_pic']['size'] > $maxSize) {
            $response = ['success' => false, 'message' => 'Invalid file'];
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $filename);
            $_SESSION['profile_pic'] = 'uploads/' . $filename;
        }
    }

    $stmt = $conn->prepare("UPDATE users SET 
        name = ?, 
        email = ?, 
        profile_pic = IFNULL(?, profile_pic), 
        phone = ?, 
        address = ?, 
        education = ?, 
        skills = ? 
        WHERE id = ?");
    
    $profilePic = $_SESSION['profile_pic'] ?? null;

    $stmt->bind_param("sssssssi", 
        $_POST['name'],
        $_POST['email'],
        $profilePic,
        $_POST['mobile'],
        $_POST['address'],
        $_POST['education'],
        $_POST['skills'],
        $_SESSION['user_id']
    );

    $stmt->execute();

    // Success response (for frontend)
    $response = ['success' => true];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Default profile picture fallback
$defaultValues = [
    'name' => '',
    'email' => '',
    'profile_pic' => '../Asset/user.png',
    'phone' => '',
    'address' => '',
    'education' => '',
    'skills' => ''
];

$user = array_merge($defaultValues, $user ?? []);
$profilePicUrl = isset($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic']) ? $user['profile_pic'] : 'Asset/user.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile | Learning Path</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --electric-purple: #6C5CE7;
            --vibrant-pink: #FD79A8;
            --neon-blue: #00CEFF;
            --deep-space: #2D3436;
            --pure-white: #FFFFFF;
            --soft-cloud: #F5F6FA;
            --border-thickness: 3px;
            --border-radius: 16px;
            --shadow-offset: 5px;
            --shadow-blur: 0px;
            --shadow-color: rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--soft-cloud);
            color: var(--deep-space);
            margin: 0;
            padding: 0;
        }

        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--pure-white);
            border: var(--border-thickness) solid var(--deep-space);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-offset) var(--shadow-offset) var(--shadow-blur) var(--shadow-color);
            padding: 2rem;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--deep-space);
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            color: var(--vibrant-pink);
            transform: scale(1.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-right: 40px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: var(--border-thickness) solid var(--deep-space);
            object-fit: cover;
            box-shadow: var(--shadow-offset) var(--shadow-offset) var(--shadow-blur) var(--shadow-color);
        }

        .profile-title {
            font-size: 2rem;
            margin: 0;
            color: var(--deep-space);
        }

        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: var(--border-thickness) solid var(--deep-space);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            box-shadow: var(--shadow-offset) var(--shadow-offset) var(--shadow-blur) var(--shadow-color);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--electric-purple);
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.2),
                        var(--shadow-offset) var(--shadow-offset) var(--shadow-blur) var(--shadow-color);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--electric-purple);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-offset) var(--shadow-offset) var(--shadow-blur) var(--shadow-color);
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--vibrant-pink);
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px var(--shadow-color);
        }

        .btn-secondary {
            background: var(--pure-white);
            color: var(--deep-space);
            border: var(--border-thickness) solid var(--deep-space);
        }

        .btn-group {
            grid-column: span 2;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: var(--border-thickness) solid var(--deep-space);
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }
            .form-group.full-width, .btn-group {
                grid-column: span 1;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="close-btn" onclick="window.location.href='../User Dashboard/dashboard.php'">
            <i class="fas fa-times"></i>
        </div>

        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile" class="profile-avatar">
            <h1 class="profile-title">Edit Profile</h1>
        </div>

        <form method="post" enctype="multipart/form-data" class="profile-form" id="profileForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="form-group full-width">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="education">Education</label>
                <input type="text" id="education" name="education" value="<?php echo htmlspecialchars($user['education']); ?>">
            </div>

            <div class="form-group">
                <label for="skills">Skills</label>
                <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($user['skills']); ?>">
            </div>

            <div class="form-group full-width">
                <label>Profile Picture</label>
                <div class="avatar-upload">
                    <img id="avatarPreview" src="<?php echo htmlspecialchars($profilePicUrl); ?>" class="avatar-preview">
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                </div>
            </div>

            <div class="btn-group">
                <button type="button" onclick="window.location.href='dashboard.php'" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>
    </div>

    <script>
        // Preview selected image
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('avatarPreview').src = event.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Client-side file size validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const file = document.querySelector('[name="profile_pic"]').files[0];
            if (file && file.size > 2 * 1024 * 1024) {
                e.preventDefault();
                alert('File too large (max 2MB)');
                return false;
            }

            // Use AJAX to submit form and handle popup
            e.preventDefault();
            const formData = new FormData(this);
            fetch("", {
                method: "POST",
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    alert("Profile updated successfully!");
                    window.location.href = "../User Dashboard/dashboard.php";
                } else {
                    alert(data.message || "Something went wrong.");
                }
            }).catch(error => {
                console.error(error);
                alert("Error submitting form.");
            });
        });
    </script>
</body>
</html>


