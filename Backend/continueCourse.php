<?php
// Define base paths
define('BASE_URL', '/LPD'); // Adjust to your project name
define('UPLOADS_PATH', BASE_URL . 'Backend/uploads/');

require_once  'includes/session_check.php';
require_once 'db.php';

// Get course ID from URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE PrimaryID = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// to fetch rating:
$current_rating = $course['rating'] ?? 0;
$rating_count = $course['rating_count'] ?? 0;

// Fetch user's enrolled courses for sidebar
$user_id = $_SESSION['user_id'];
$enrolled_stmt = $conn->prepare("
    SELECT c.PrimaryID, c.title 
    FROM user_courses uc
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE uc.user_id = ?
    ORDER BY c.title
");
$enrolled_stmt->bind_param("i", $user_id);
$enrolled_stmt->execute();
$enrolled_courses = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<?php
    // Set profile picture (corrected paths)
    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Set absolute and relative paths
    $relativePath = '/' . $user['profile_pic'];
    $absolutePath = '/' . $user['profile_pic'];

    $profilePicPath = '';
if (!empty($user['profile_pic'])) {
    $absolutePath = __DIR__ . '/uploads/' . basename($user['profile_pic']);
    if (file_exists($absolutePath)) {
        $profilePicUrl = '/LPD/Backend/uploads/' . basename($user['profile_pic']);
    } else {
        $profilePicUrl = '/LPD/Asset/user.png';
    }
} else {
    $profilePicUrl = '/LPD/Asset/user.png';
}

    // Correct profile picture path
    // $profilePicUrl = (!empty($user['profile_pic']) && file_exists(__DIR__ . '/uploads/' . $user['profile_pic']))
    // ? BASE_URL . 'Backend/uploads/' . $user['profile_pic']
    // : BASE_URL . 'Asset/user.png';
?>

<?php
// Add this helper function to convert YouTube URLs
function convertYoutubeUrlToEmbed($url) {
    if (str_contains($url, 'embed')) {
        return $url; // Already embed format
    }
    
    $pattern = '~
        (?:https?://)?              # Optional protocol
        (?:www\.)?                  # Optional www subdomain
        (?:youtube\.com|youtu\.be)  # Domain
        /watch\?v=([^&]+)           # Video ID capture
        ~x';
    
    preg_match($pattern, $url, $matches);
    if (!empty($matches[1])) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    return $url; // Return original if conversion fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($course['title'] ?? 'Course Player'); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../User Dashboard/styles.css">
  <style>
    :root {
      --primary-color: #6C5CE7;
      --primary-dark: #5a0b9d;
      --text-color: #2d3748;
      --text-light: #718096;
      --bg-color: #f8fafc;
      --sidebar-width: 20%;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      display: flex;
      flex-direction: column;
      height: 100vh;
      background-color: var(--bg-color);
      color: var(--text-color);
    }
    
    /* Main Content Layout */
    .learning-container {
      display: flex;
      flex: 1;
      overflow: hidden;
    }
    
    /* Sidebar Styles */
    .course-sidebar {
      width: var(--sidebar-width);
      background: white;
      border-right: 1px solid #e2e8f0;
      overflow-y: auto;
      padding: 1.5rem;
      transition: transform 0.3s;
    }
    
    .sidebar-header {
      margin-bottom: 1.5rem;
    }
    
    .sidebar-header h2 {
      font-size: 1.25rem;
      color: var(--primary-color);
    }
    
    .course-list {
      list-style: none;
    }
    
    .course-item {
      margin-bottom: 0.5rem;
    }
    
    .course-link {
      display: block;
      padding: 0.75rem 1rem;
      color: var(--text-color);
      text-decoration: none;
      border-radius: 6px;
      transition: all 0.3s;
    }
    
    .course-link:hover, .course-link.active {
      background: #f3f4f6;
      color: var(--primary-color);
    }
    
    .course-link.active {
      font-weight: 600;
      border-left: 3px solid var(--primary-color);
    }
    
    /* Main Content Area */
    .course-content {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
    }
    
    .course-video-container {
        width: 80%;
      height: 80%;
      background: #000;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 2rem;
      aspect-ratio: 16/9;
    }
    
    .course-video {
      width: 100%;
      height: 100%;
    }
    
    .course-title {
      font-size: 1.75rem;
      margin-bottom: 1rem;
    }
    
    .course-description {
      color: var(--text-light);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }
    .rating-display {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 1.1rem;
        margin: 15px 0;
    }

    .rating-display .fa-star, 
    .rating-display .fa-star-half-alt {
        color: #f6ad55;
        font-size: 1.2rem;
    }

    .rating-display span {
        font-weight: 500;
        color: #4a5568;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .learning-container {
        flex-direction: column;
      }
      
      .course-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
      }
    }
  </style>
</head>
<body>
  <header class="neopop-header">
    <div class="logo-container">
      <img src="../Asset/logo.png" alt="Learning Path Logo" class="logo" width="50px">
    </div>
    <nav class="main-nav">
      <a href="../User Dashboard/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
      <a href="../User Dashboard/dashboard.php#category-nav" class="nav-link"><i class="fas fa-book"></i> My Courses</a>
      <a href="#" class="nav-link"><i class="fas fa-chart-line"></i> Progress</a>
      <div class="profile-dropdown">
        <button class="profile-icon" id="profile-icon-button">
          <img id="user-profile-pic" class="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='../Asset/user.png'" alt="Profile Picture" height="50">
        </button>
        <div class="dropdown-menu">
          <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
          <a href="..FrontEnd/index.html" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
    </nav>
  </header>

  <div class="learning-container">
    <!-- Sidebar with course list -->
    <aside class="course-sidebar">
      <div class="sidebar-header">
        <h2><i class="fas fa-book-open"></i> My Courses</h2>
      </div>
      <ul class="course-list">
        <?php foreach ($enrolled_courses as $ecourse): ?>
          <li class="course-item">
            <a href="continueCourse.php?course_id=<?php echo $ecourse['PrimaryID']; ?>" 
               class="course-link <?php echo ($ecourse['PrimaryID'] == $course_id) ? 'active' : ''; ?>">
              <i class="fas fa-play-circle"></i> <?php echo htmlspecialchars($ecourse['title']); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <!-- Main Course Content -->
    <main class="course-content">
      <?php if ($course): ?>
        <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
        
        <div class="course-video-container">
            <?php 
            // Convert YouTube URL to embed format if needed
            $videoUrl = '';
            if (!empty($course['video_link'])) {
                if (str_contains($course['video_link'], 'youtube.com') || str_contains($course['video_link'], 'youtu.be')) {
                    // Convert regular YouTube URL to embed format
                    $videoUrl = convertYoutubeUrlToEmbed($course['video_link']);
                } else {
                    $videoUrl = $course['video_link'];
                }
            }
            
            if (!empty($videoUrl)): ?>
                <iframe class="course-video" 
                        src="<?php echo htmlspecialchars($videoUrl) ?>?rel=0&modestbranding=1" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
                        <!-- <video class="course-video" 
                            src="<?php echo htmlspecialchars($videoUrl); ?>" 
                            controls 
                            autoplay 
                            onerror="this.src='../Asset/cpp.mp4'">
                            Your browser does not support the video tag.
                        </video> -->

            <?php else: ?>
                <div class="video-placeholder">
                    <!-- <i class="fas fa-video-slash"></i>
                    <p>Video content not available</p> -->
                    <video class="course-video" 
                            src="<?php echo htmlspecialchars($videoUrl); ?>" 
                            controls 
                            autoplay 
                            onerror="this.src='../Asset/cpp.mp4'">
                            Your browser does not support the video tag.
                        </video>
                    <?php if (empty($course['video_link'])): ?>
                        <p class="debug-info">No video link provided in database</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="course-description">
            <h3>About This Course</h3>
            <div class="rating-display" style="margin: 10px 0;">
                <?php
                $full_stars = floor($current_rating);
                $has_half_star = ($current_rating - $full_stars) >= 0.5;
                $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                
                for ($i = 0; $i < $full_stars; $i++) {
                    echo '<i class="fas fa-star" style="color: #f6ad55;"></i>';
                }
                if ($has_half_star) {
                    echo '<i class="fas fa-star-half-alt" style="color: #f6ad55;"></i>';
                }
                for ($i = 0; $i < $empty_stars; $i++) {
                    echo '<i class="far fa-star" style="color: #f6ad55;"></i>';
                }
                ?>
                <span style="margin-left: 5px;"><?php echo number_format($current_rating, 1); ?> (<?php echo $rating_count; ?> ratings)</span>
            </div>
            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
        </div>
        
        <?php if ($course['resource_link']): ?>
          <div class="course-resources">
            <h3><i class="fas fa-file-alt"></i> Resources</h3>
            <a href="<?php echo htmlspecialchars($course['resource_link']); ?>" class="resource-link" target="_blank">
              Study Materials
            </a>
          </div>



<?php if (!empty($course['upload_resource'])): ?>
<div class="resource-download" style="margin-top: 2rem; background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
    <h3 style="font-size: 1.25rem; margin-bottom: 1rem; color: #2d3748;">
        <i class="fas fa-file-download"></i> Study Materials
    </h3>
    
    <?php
    // Handle multiple resources if stored as JSON
    if (json_decode($course['upload_resource'])) {
        $resources = json_decode($course['upload_resource'], true);
        foreach ($resources as $resource): ?>
            <a href="../Admin/uploads/<?php echo htmlspecialchars($resource); ?>" 
               class="download-btn" 
               download
               style="display: inline-flex; align-items: center; background: #6a0dad; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; margin-right: 1rem; margin-bottom: 1rem; transition: all 0.3s;">
                <i class="fas fa-download" style="margin-right: 8px;"></i>
                Download <?php echo pathinfo($resource, PATHINFO_EXTENSION); ?> File
            </a>
        <?php endforeach;
    } else {
        // Single resource
        $resource = $course['upload_resource'];
        ?>
        <a href="../Admin/uploads/<?php echo htmlspecialchars($resource); ?>" 
           class="download-btn" 
           download
           style="display: inline-flex; align-items: center; background: #6a0dad; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; transition: all 0.3s;">
            <i class="fas fa-download" style="margin-right: 8px;"></i>
            Download Study Materials (<?php echo strtoupper(pathinfo($resource, PATHINFO_EXTENSION)); ?>)
        </a>
    <?php } ?>
    
    <p style="font-size: 0.9rem; color: #718096; margin-top: 1rem;">
        <i class="fas fa-info-circle"></i> File will download automatically when clicked
    </p>
</div>
<?php endif; ?>

        <?php endif; ?>        
      <?php else: ?>

        <div class="no-course-selected">
          <h2>Course Not Found</h2>
          <p>Please select a course from the sidebar to begin learning.</p>
        </div>
      <?php endif; ?>

      
    </main>
  </div>

  <script>
    function updateUserProfileDisplay(user) {
    if (!user) return;
    const nameEl = document.querySelector(".profile-name");
    const avatarEl = document.querySelector(".profile-icon");
    if (nameEl) nameEl.textContent = user.name;
    if (user.profile_pic && avatarEl) {
      avatarEl.innerHTML = `<img src="data:image/png;base64,${user.profile_pic}" alt="Profile Picture" class="user-avatar">`;
    }
  }

    document.addEventListener("DOMContentLoaded", () => {
    const profileIcon = document.querySelector('.profile-icon');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (!profileIcon || !dropdownMenu) {
        console.warn("Profile icon or dropdown menu not found in the DOM.");
        return;
    }

    profileIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!dropdownMenu.contains(e.target) && !profileIcon.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    // Load user data if present
    const user = JSON.parse(localStorage.getItem("user"));
    if (user) updateUserProfileDisplay(user);
});
    // Make sidebar links load content without full page reload
    document.querySelectorAll('.course-link').forEach(link => {
      link.addEventListener('click', function(e) {
        if (!this.classList.contains('active')) {
          // Update active state
          document.querySelectorAll('.course-link').forEach(el => el.classList.remove('active'));
          this.classList.add('active');
          
          // In a real implementation, you might use AJAX to load just the new content
          // For this example, we'll let the normal link behavior work
        }
      });
    });

// Improved Progress Tracking System
let progressInterval;

function startProgressTracking() {
    const video = document.querySelector('video, iframe');
    if (!video) return;

    // Clear existing interval if any
    if (progressInterval) clearInterval(progressInterval);

    progressInterval = setInterval(() => {
        let progress = 0;
        
        // For HTML5 video elements
        if (video.tagName === 'VIDEO' && !isNaN(video.duration)) {
            progress = Math.round((video.currentTime / video.duration) * 100);
        }
        // For YouTube iframes (basic tracking)
        else if (video.src.includes('youtube.com/embed')) {
            progress = 10; // Default increment for YouTube
        }

        if (progress > 0) {
            updateProgress(progress);
        }
    }, 30000); // Every 30 seconds
}

function updateProgress(progress) {
    fetch('update_progress.php', {  // Changed to relative path
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            course_id: <?php echo $course_id; ?>,
            user_id: <?php echo $user_id; ?>,
            progress: progress
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Progress update response:', data);
        if (data.error) {
            console.error('Progress update failed:', data.error);
        }
    })
    .catch(error => console.error('Progress update error:', error));
}

// Rating System with Clean Prompt Handling
let ratingSubmitted = false;
let isHandlingNavigation = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeProgressTracking();
    setupRatingPrompts();
});

// 1. Progress Tracking (keep your existing working version)
function initializeProgressTracking() {
    const video = document.querySelector('video, iframe');
    if (video) {
        startProgressTracking();
    } else {
        const observer = new MutationObserver(function() {
            if (document.querySelector('video, iframe')) {
                startProgressTracking();
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
}

// 2. Enhanced Rating Prompt System
function setupRatingPrompts() {
    // 1. Handle all link clicks (including sidebar)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link || !link.href) return;
        
        // Skip special cases
        if (shouldSkipPrompt(link)) return;
        
        if (shouldPromptForRatingSync()) {
            e.preventDefault();
            handleNavigationWithPrompt(link.href);
        }
    });

    // 2. Remove beforeunload handler completely
    // We'll handle page exits differently
}

// New navigation handler with guaranteed prompt
async function handleNavigationWithPrompt(destination) {
    const rating = await showRatingPrompt();
    
    if (rating >= 1 && rating <= 5) {
        await submitRating(rating);
    }
    
    // Proceed with navigation
    isHandlingNavigation = true;
    window.location.href = destination;
}

function shouldSkipPrompt(link) {
    // Skip if already submitted rating
    if (ratingSubmitted) return true;
    
    // Skip if clicking on active course link
    if (link.classList.contains('active')) return true;
    
    // Skip if going to the same course page
    if (link.href.includes('continueCourse.php') && 
        link.href.includes('course_id=<?php echo $course_id; ?>')) {
        return true;
    }
    
    return false;
}

function shouldPromptForRatingSync() {
    const video = document.querySelector('video, iframe');
    if (!video) return false;
    
    const watchedTime = video.currentTime || 0;
    const totalTime = video.duration || 1;
    return Math.round((watchedTime / totalTime) * 100) > 20;
}

async function submitRating(rating) {
    try {
        const response = await fetch('update_rating.php', {
            method: 'POST',
            body: new URLSearchParams({
                course_id: <?php echo $course_id; ?>,
                rating: rating
            }),
            keepalive: true
        });
        const result = await response.json();
        if (result.status === 'success') {
            ratingSubmitted = true;
            return true;
        }
    } catch (error) {
        console.error('Rating submission failed:', error);
    }
    return false;
}

// Special handling for page exit (optional)
// window.addEventListener('beforeunload', function(e) {
//     if (!isHandlingNavigation && shouldPromptForRatingSync() && !ratingSubmitted) {
//         // This will ONLY show when closing/refreshing the tab
//         // You can customize this message
//         e.returnValue = 'You have unsaved rating. Are you sure you want to leave?';
//     }
// });

function showRatingPrompt() {
    return new Promise((resolve) => {
        // Create modal container
        const modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.7)';
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '1000';
        modal.style.backdropFilter = 'blur(5px)';

        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.style.backgroundColor = '#2c3e50';
        modalContent.style.padding = '2rem';
        modalContent.style.borderRadius = '15px';
        modalContent.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
        modalContent.style.textAlign = 'center';
        modalContent.style.transform = 'perspective(1000px) rotateX(10deg)';
        modalContent.style.transition = 'all 0.3s ease';
        modalContent.style.border = '1px solid #3d566e';
        modalContent.style.maxWidth = '400px';
        modalContent.style.width = '90%';

        // Add hover effect
        modalContent.addEventListener('mouseenter', () => {
            modalContent.style.transform = 'perspective(1000px) rotateX(5deg) scale(1.02)';
            modalContent.style.boxShadow = '0 15px 30px rgba(0,0,0,0.4)';
        });
        modalContent.addEventListener('mouseleave', () => {
            modalContent.style.transform = 'perspective(1000px) rotateX(10deg) scale(1)';
            modalContent.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
        });

        // Add title
        const title = document.createElement('h3');
        title.textContent = 'Rate This Course';
        title.style.color = '#ecf0f1';
        title.style.marginBottom = '1.5rem';
        title.style.fontSize = '1.5rem';
        title.style.textShadow = '0 2px 4px rgba(0,0,0,0.3)';
        modalContent.appendChild(title);

        // Add current rating
        const currentRating = document.createElement('div');
        currentRating.textContent = `Current Rating: ${<?php echo number_format($current_rating, 1); ?>}/5`;
        currentRating.style.color = '#bdc3c7';
        currentRating.style.marginBottom = '1.5rem';
        currentRating.style.fontSize = '0.9rem';
        modalContent.appendChild(currentRating);

        // Create stars container
        const starsContainer = document.createElement('div');
        starsContainer.style.display = 'flex';
        starsContainer.style.justifyContent = 'center';
        starsContainer.style.gap = '0.5rem';
        starsContainer.style.marginBottom = '2rem';

        // Create 5 stars
        let selectedRating = 0;
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('div');
            star.innerHTML = '★';
            star.style.fontSize = '2.5rem';
            star.style.cursor = 'pointer';
            star.style.color = '#bdc3c7';
            star.style.transition = 'all 0.3s ease';
            star.style.textShadow = '0 2px 5px rgba(0,0,0,0.2)';
            star.style.transform = 'rotateY(0deg)';

            // Hover effects
            star.addEventListener('mouseenter', () => {
                star.style.transform = 'rotateY(20deg) scale(1.2)';
            });
            star.addEventListener('mouseleave', () => {
                star.style.transform = 'rotateY(0deg) scale(1)';
                if (selectedRating === 0 || i > selectedRating) {
                    star.style.color = '#bdc3c7';
                }
            });

            // Click handler
            star.addEventListener('click', () => {
                selectedRating = i;
                // Color all stars up to the selected one
                const allStars = starsContainer.querySelectorAll('div');
                allStars.forEach((s, index) => {
                    s.style.color = index < i ? '#f1c40f' : '#bdc3c7';
                    s.style.textShadow = index < i ? '0 0 15px rgba(241, 196, 15, 0.7)' : '0 2px 5px rgba(0,0,0,0.2)';
                });
            });

            starsContainer.appendChild(star);
        }

        modalContent.appendChild(starsContainer);

        // Add submit button
        const submitButton = document.createElement('button');
        submitButton.textContent = 'Submit Rating';
        submitButton.style.backgroundColor = '#3498db';
        submitButton.style.color = 'white';
        submitButton.style.border = 'none';
        submitButton.style.padding = '0.8rem 1.5rem';
        submitButton.style.borderRadius = '5px';
        submitButton.style.cursor = 'pointer';
        submitButton.style.fontSize = '1rem';
        submitButton.style.transition = 'all 0.3s ease';
        submitButton.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';

        // Button hover effect
        submitButton.addEventListener('mouseenter', () => {
            submitButton.style.backgroundColor = '#2980b9';
            submitButton.style.transform = 'translateY(-2px)';
            submitButton.style.boxShadow = '0 6px 8px rgba(0,0,0,0.15)';
        });
        submitButton.addEventListener('mouseleave', () => {
            submitButton.style.backgroundColor = '#3498db';
            submitButton.style.transform = 'translateY(0)';
            submitButton.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        });

        // Submit handler
        submitButton.addEventListener('click', () => {
            if (selectedRating > 0) {
                modal.remove();
                resolve(selectedRating);
            } else {
                alert('Please select a rating between 1-5 stars');
            }
        });

        modalContent.appendChild(submitButton);

        // Add close button
        const closeButton = document.createElement('button');
        closeButton.textContent = 'Close';
        closeButton.style.backgroundColor = 'transparent';
        closeButton.style.color = '#bdc3c7';
        closeButton.style.border = '1px solid #bdc3c7';
        closeButton.style.padding = '0.5rem 1rem';
        closeButton.style.borderRadius = '5px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.marginTop = '1rem';
        closeButton.style.fontSize = '0.8rem';
        closeButton.style.transition = 'all 0.3s ease';

        // Close button hover
        closeButton.addEventListener('mouseenter', () => {
            closeButton.style.backgroundColor = 'rgba(189, 195, 199, 0.1)';
        });
        closeButton.addEventListener('mouseleave', () => {
            closeButton.style.backgroundColor = 'transparent';
        });

        // Close handler
        closeButton.addEventListener('click', () => {
            modal.remove();
            resolve(null);
        });

        modalContent.appendChild(closeButton);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
    });
}

  </script>
</body>
</html>