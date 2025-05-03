<?php
require_once '../Backend/includes/session_check.php';
require_once '../Backend/db.php';

// Fetch user details
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set absolute and relative paths
$relativePath = '../Backend/' . $user['profile_pic'];
$absolutePath = '../Backend/' . $user['profile_pic'];

// Check if file exists on server, fallback if not
$profilePicUrl = (!empty($user['profile_pic']) && file_exists($absolutePath))
    ? $relativePath
    : '../Asset/user.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning Path Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="styles.css">
  <style>
    
    /* ===== Enrolled Courses Section ===== */
.current-courses {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-title {
    font-size: 1.5rem;
    color: #2d3748;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #6C5CE7;
}

.view-all {
    color: #6C5CE7;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-all:hover {
    color: #4b0082;
    text-decoration: underline;
}

/* Course Cards Grid */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 16px;
}

.enrolled-course-card {
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid #e2e8f0;
}

.enrolled-course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

/* Course Image */
.course-image-container {
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    align-items: center; /* Centers the image and progress bar horizontally */
    width: 100%; /* Ensures the container spans the full width of its parent */
}

.course-image-container img {
    width: 200px; /* Fixed width */
    height: 200px; /* Fixed height */
    object-fit: cover; /* Ensures the image fills the box while maintaining aspect ratio */
    border-radius: 8px; /* Adds rounded corners */
    transition: transform 0.5s ease; /* Smooth hover effect */
    margin-bottom: 12px; /* Adds spacing below the image */
    background-color: none; /* Optional: Adds a background color for images with transparency */
}

.enrolled-course-card:hover .course-image-container img {
    transform: scale(1.05);
}

/* Progress Bar */
.progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 6px;
    background: #edf2f7;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #6a0dad, #8a2be2);
    transition: width 0.5s ease;
}

/* Course Info */
.course-info {
    padding: 16px;
}

.course-info h4 {
    margin: 0 0 8px;
    font-size: 1.1rem;
    color: #2d3748;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.course-meta {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #4a5568;
    margin-bottom: 8px;
}

.author {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 60%;
}

.rating {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #f6ad55;
    font-weight: 500;
}

.rating i {
    font-size: 0.9rem;
}

.enrolled-days {
    font-size: 0.8rem;
    color: #718096;
    margin: 0;
}

/* Course Actions */
.course-actions {
    padding: 0 16px 16px;
}

.continue-btn {
    display: block;
    width: 100%;
    padding: 10px;
    text-align: center;
    background: linear-gradient(135deg, #6C5CE7,rgb(149, 72, 222));
    color: white;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.continue-btn:hover {
    background: linear-gradient(135deg, rgb(175, 121, 226), #6C5CE7);
    box-shadow: 0 4px 12px rgba(106, 13, 173, 0.3);
}

/* Cancel Button */
.cancel-course-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    z-index: 2;
    color: #718096;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.cancel-course-btn:hover {
    background: #e53e3e;
    color: white;
    transform: scale(1.1);
}

/* Empty/Loading/Error States */
.empty-state, .loading-state, .error-state {
    text-align: center;
    padding: 40px 20px;
    background: #f8fafc;
    border-radius: 10px;
    color: #4a5568;
    grid-column: 1 / -1;
}

.empty-state i, .loading-state i, .error-state i {
    font-size: 2.5rem;
    margin-bottom: 16px;
    color: #6a0dad;
}

.loading-state i.fa-spinner {
    animation: spin 1s linear infinite;
}

.error-state i {
    color: #e53e3e;
}

.empty-state p, .loading-state p, .error-state p {
    margin-bottom: 16px;
    font-size: 1.1rem;
}

.browse-btn, .retry-btn {
    display: inline-block;
    padding: 10px 24px;
    background: #6a0dad;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.browse-btn:hover, .retry-btn:hover {
    background: #5a0b9d;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .courses-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .section-title {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .current-courses {
        padding: 16px;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
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
      <a href="#" class="nav-link"><i class="fas fa-home"></i> Home</a>
      <a href="#category-nav" class="nav-link"><i class="fas fa-book"></i> My Courses</a>
      <a href="../Backend/progress.php" class="nav-link"><i class="fas fa-chart-line"></i> Progress</a>
      <div class="profile-dropdown">
        <button class="profile-icon" id="profile-icon-button">
          <img id="user-profile-pic" class="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='./Asset/user.png'" alt="Profile Picture" height="50">
        </button>

        <div class="dropdown-menu">
          <a href="../Backend/profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
          <a href="../index.html" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </div>
    </nav>
  </header>


  <main class="dashboard-main">
    <!-- Current Courses Section -->
    <section class="current-courses">
      <div class="section-header">
        <h2 class="section-title"><i class="fas fa-play-circle"></i> My Active Courses</h2>
        <a href="#" class="view-all">View All</a>
      </div>
      <div class="course-carousel">
        <div class="empty-state">
          <i class="fas fa-book-open"></i>
          <p>No active courses yet</p>
          <a class="browse-btn" href="#category-nav">Browse Courses</a>
        </div>
      </div>
    </section>

    <!-- Course Suggestions -->
    <section class="course-suggestions">
      <div class="section-header">
        <h2 class="section-title"><i class="fas fa-lightbulb"></i> Recommended For You</h2>
        <div class="suggestion-tabs">
          <button class="suggestion-tab active">Popular</button>
          <button class="suggestion-tab">New</button>
          <button class="suggestion-tab">Trending</button>
        </div>
      </div>
      <div class="suggestion-carousel">
        <!-- Populated by JS -->
      </div>
    </section>

    <!-- Browse Categories -->
    <section class="category-nav" id="category-nav">
      <div class="section-header">
        <h2 class="section-title"><i class="fas fa-layer-group"></i> Browse Categories</h2>
        <div class="category-search">
          <input type="text" placeholder="Search categories...">
          <i class="fas fa-search"></i>
        </div>
      </div>

      <div class="domain-tabs" id="domain-container">
        <!-- Loaded from browseCourses.php (action=domain) -->
      </div>

      <div class="subject-tabs" id="courses-container">
        <!-- Loaded from browseCourses.php (action=subject) -->
      </div>

      
    </section>

    <!-- Course Section -->
    <section class="course-section">
      <div class="course-grid-header">
        <h3 class="current-category"><i class="fas fa-tag"></i> Available Courses: </h3>
        <div class="sort-options">
          <span>Sort by:</span>
          <select>
            <option>Most Popular</option>
            <option>Newest</option>
            <option>Highest Rated</option>
            <option>Price: Low to High</option>
          </select>
        </div>
      </div>
      <div class="course-grid" id="course-cards-container">
        <!-- Loaded from browseCourses.php (action=course) -->
      </div>
    </section>
  </main>

  <footer class="neopop-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4>Learning Path</h4>
        <p>Your gateway to mastering new skills and advancing your career through structured learning paths.</p>
      </div>
      <div class="footer-section">
        <h4>Quick Links</h4>
        <div class="footer-links">
          <a href="#" class="footer-link">About Us</a>
          <a href="#" class="footer-link">Courses</a>
          <a href="#" class="footer-link">Pricing</a>
          <a href="#" class="footer-link">Blog</a>
        </div>
      </div>
      <div class="footer-section">
        <h4>Support</h4>
        <div class="footer-links">
          <a href="#" class="footer-link">Contact</a>
          <a href="#" class="footer-link">FAQs</a>
          <a href="#" class="footer-link">Help Center</a>
        </div>
      </div>
      <div class="footer-section">
        <h4>Connect</h4>
        <div class="social-icons">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </div>
    <div class="copyright">
      © 2023 Learning Path Dashboard. All rights reserved.
    </div>
  </footer>

<script>

function showToast(message) {
  const toast = document.createElement('div');
  toast.className = 'toast-message';
  toast.textContent = message;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.add('show');
  }, 100); // slight delay to trigger transition

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      toast.remove();
    }, 500); // wait for hide transition to complete
  }, 5000); // visible for 3 seconds
}

document.addEventListener("DOMContentLoaded", function () {
  fetch("../Backend/browseCourses.php?action=domain")
    .then(res => res.text())
    .then(data => {
      document.getElementById("domain-container").innerHTML = data;

      document.querySelectorAll(".domain-btn").forEach(btn => {
        btn.addEventListener("click", function () {
          let category = this.dataset.category;
          fetch("../Backend/browseCourses.php?action=subject", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "category=" + encodeURIComponent(category)
          })
          .then(res => res.text())
          .then(data => {
            document.getElementById("courses-container").innerHTML = data;
            document.getElementById("course-cards-container").innerHTML = ""; // Clear previous courses

            document.querySelectorAll(".subject-btn").forEach(subBtn => {
              subBtn.addEventListener("click", function () {
                let subject = this.dataset.subject;
                fetch("../Backend/browseCourses.php?action=course", {
                  method: "POST",
                  headers: { "Content-Type": "application/x-www-form-urlencoded" },
                  body: "subject=" + encodeURIComponent(subject)
                })
                .then(res => res.text())
                .then(html => {
                  document.getElementById("course-cards-container").innerHTML = html;
                });
              });
            });
          });
        });
      });
    });
});

// Function to handle enroll button click
function enrollCourse(courseId) {
    const userId = <?php echo $_SESSION['user_id']; ?>;  // Dynamically set user_id from PHP session

    // Prepare data to send
    const data = {
        user_id: userId,
        course_id: courseId,
        progress: 0, // Assuming new enrollment starts with 0% progress
        enrolled_at: new Date().toISOString().slice(0, 19).replace('T', ' ') // Get current timestamp
    };

    // Send AJAX request to enroll the user
    fetch('../Backend/enroll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`✅ You have successfully enrolled !`);//in ${courseTitle}
            loadEnrolledCourses();
            // loadMyCourses(userId);  // Reload active courses
        } else {
            // If the user is already enrolled, show an alert
            if (data.message === 'You are already enrolled in this course.') {
                alert('You are already enrolled in this course.');
            } else {
                showToast('Error enrolling in the course. Please try again.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error processing your request.');
    });
}




//function to load courses on active courses section

// Function to load enrolled courses
function loadEnrolledCourses() {
    fetch('../browseCourses.php?action=mycourses') // Replace with actual path to PHP file
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }

            // If courses are returned, display them in the HTML
            const coursesSection = document.querySelector('.current-courses .course-carousel');
            coursesSection.innerHTML = ''; // Clear any previous courses

            if (data.length === 0) {
                // If no courses, show the empty state
                const emptyState = document.querySelector('.empty-state');
                emptyState.style.display = 'block';
            } else {
                // Display each course
                data.forEach(course => {
                    const courseHTML = `
                        <div class="course-card">
                            <img src="${course.image}" alt="${course.title}" class="course-image" />
                            <h3>${course.title}</h3>
                            <p>${course.description}</p>
                        </div>
                    `;
                    coursesSection.innerHTML += courseHTML;
                });
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

// Call the function to load the courses on page load
document.addEventListener('DOMContentLoaded', loadEnrolledCourses);


// function loadMyCourses(userId) {
//     fetch('../Backend/browseCourses.php?action=mycourses', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/x-www-form-urlencoded',
//         },
//         body: 'user_id=' + encodeURIComponent(userId),
//     })
//     .then(response => response.text())
//     .then(html => {
//         const carousel = document.querySelector('.course-carousel');

//         if (!carousel) return;

//         if (html.trim() === "empty") {
//             // No courses
//             carousel.innerHTML = `
//                 <div class="empty-state">
//                     <i class="fas fa-book-open"></i>
//                     <p>No active courses yet</p>
//                     <a class="browse-btn" href="#category-nav">Browse Courses</a>
//                 </div>
//             `;
//         } else {
//             // Courses found
//             carousel.innerHTML = `
//                 <div class="courses-wrapper" style="display: flex; flex-wrap: wrap; gap: 16px;">
//                     ${html}
//                 </div>
//             `;
//         }
//     })
//     .catch(error => {
//         console.error('Error loading active courses:', error);
//     });
// }

// // Call the function with the actual logged in user_id
// const userId = ; // Assuming session contains user id
// loadMyCourses(userId);

// function attachCancelCourseHandlers() {
//     const cancelButtons = document.querySelectorAll('.cancel-course-btn');

//     cancelButtons.forEach(btn => {
//         btn.addEventListener('mouseover', () => {
//             btn.style.color = 'red';
//             btn.title = 'Cancel course'; // tooltip
//         });
//         btn.addEventListener('mouseout', () => {
//             btn.style.color = '#888';
//         });

//         btn.addEventListener('click', () => {
//             const courseId = btn.getAttribute('data-course-id');
//             showCancelAlert(courseId);
//         });
//     });
// }

// function showCancelAlert(courseId) {
//     // Simple but stylish alert
//     const confirmation = document.createElement('div');
//     confirmation.innerHTML = `
//         <div style="
//             position: fixed; top: 0; left: 0; width: 100%; height: 100%;
//             background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000;">
//             <div style="background: #fff; padding: 30px; border-radius: 12px; text-align: center; width: 300px;">
//                 <h2 style="margin-bottom: 20px; color: #333;">Are you sure to cancel this course?</h2>
//                 <div style="display: flex; justify-content: center; gap: 20px;">
//                     <button id="confirm-yes" style="padding: 8px 16px; background: #e53935; color: white; border: none; border-radius: 6px; cursor: pointer;">Yes</button>
//                     <button id="confirm-no" style="padding: 8px 16px; background: #6a0dad; color: white; border: none; border-radius: 6px; cursor: pointer;">No</button>
//                 </div>
//             </div>
//         </div>
//     `;
//     document.body.appendChild(confirmation);

//     document.getElementById('confirm-yes').onclick = () => {
//         // Send request to cancel enrollment
//         fetch('../Backend/browseCourses.php?action=cancelCourse', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded',
//             },
//             body: 'course_id=' + encodeURIComponent(courseId) + '&user_id=' + encodeURIComponent(<?php echo $_SESSION['user_id']; ?>),
//         })
//         .then(response => response.text())
//         .then(data => {
//             if (data.trim() === "success") {
//                 showToast('Course cancelled successfully.');
//                 loadMyCourses(<?php echo $_SESSION['user_id']; ?>); // reload updated list
//             } else {
//                 alert('Error cancelling course.');
//             }
//         })
//         .catch(err => {
//             console.error(err);
//         });

//         document.body.removeChild(confirmation);
//     };

//     document.getElementById('confirm-no').onclick = () => {
//         document.body.removeChild(confirmation);
//     };
// }

// // IMPORTANT: After loading the courses, re-attach handlers
// function loadMyCourses(userId) {
//     fetch('../Backend/browseCourses.php?action=mycourses', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/x-www-form-urlencoded',
//         },
//         body: 'user_id=' + encodeURIComponent(userId),
//     })
//     .then(response => response.text())
//     .then(html => {
//         const carousel = document.querySelector('.course-carousel');

//         if (!carousel) return;

//         if (html.trim() === "empty") {
//             carousel.innerHTML = `
//                 <div class="empty-state">
//                     <i class="fas fa-book-open"></i>
//                     <p>No active courses yet</p>
//                     <a class="browse-btn" href="#category-nav">Browse Courses</a>
//                 </div>
//             `;
//         } else {
//             carousel.innerHTML = `
//                 <div class="courses-wrapper" style="display: flex; flex-wrap: wrap; gap: 16px;">
//                     ${html}
//                 </div>
//             `;
//             attachCancelCourseHandlers(); // re-attach after loading!
//         }
//     })
//     .catch(error => {
//         console.error('Error loading active courses:', error);
//     });
// }


// Function to load enrolled courses
// Function to load enrolled courses
function loadEnrolledCourses() {
    const userId = <?php echo $_SESSION['user_id']; ?>;
    const carousel = document.querySelector('.course-carousel');
    
    // Show loading state
    carousel.innerHTML = `
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading your courses...</p>
        </div>
    `;
    
    // Fetch enrolled courses
    fetch(`../Backend/browseCourses.php?action=mycourses&user_id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load courses');
            }
            
            if (data.data.length === 0) {
                // Show empty state if no courses
                carousel.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <p>No active courses yet</p>
                        <a class="browse-btn" href="#category-nav">Browse Courses</a>
                    </div>
                `;
                return;
            }
            
            // Render courses
            carousel.innerHTML = `
                <div class="courses-grid">
                    ${data.data.map(course => createCourseCard(course)).join('')}
                </div>
            `;
            
            // Attach event listeners to cancel buttons
            attachCancelHandlers();
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            carousel.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load courses</p>
                    <button onclick="loadEnrolledCourses()" class="retry-btn">
                        <i class="fas fa-sync-alt"></i> Try Again
                    </button>
                </div>
            `;
        });
}

// Helper function to create course card HTML with null checks
function createCourseCard(course) {
    // Handle null rating
    const rating = course.rating !== null ? course.rating.toFixed(1) : 'N/A';
    
    // Handle empty image
    const imageSrc = course.image && course.image.trim() !== '' ? 
        course.image : '../Asset/course-placeholder.png';
    
    return `
        <div class="enrolled-course-card" data-course-id="${course.course_id}">
            <button class="cancel-course-btn" data-course-id="${course.course_id}" 
                title="Unenroll from course">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="course-image-container">
                <img src="${imageSrc}" alt="${course.title}"  
                    onerror="this.src='../Asset/course-placeholder.png'">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${course.progress}%"></div>
                </div>
            </div>
            
            <div class="course-info">
                <h4>${course.title}</h4>
                <p class="course-meta">
                    <span class="author">${course.author}</span>
                    <span class="rating">
                        <i class="fas fa-star"></i> ${rating}
                    </span>
                </p>
                <p class="enrolled-days">
                    Enrolled ${course.days_enrolled === 0 ? 'today' : `${course.days_enrolled} days ago`}
                </p>
            </div>
            
            <div class="course-actions">
                <a href="../Backend/continueCourse.php?course_id=${course.course_id}" 
                    class="continue-btn">
                    ${course.progress > 0 ? 'Continue' : 'Start'} Learning
                </a>
            </div>
        </div>
    `;
}

// Function to attach cancel course handlers
function attachCancelHandlers() {
    document.querySelectorAll('.cancel-course-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const courseId = this.dataset.courseId;
            confirmCancelCourse(courseId);
        });
    });
}

// Function to confirm course cancellation
function confirmCancelCourse(courseId) {
    // You can use a nicer modal here
    if (confirm('Are you sure you want to unenroll from this course?')) {
        cancelCourse(courseId);
    }
}

// Function to cancel course enrollment
function cancelCourse(courseId) {
    const userId = <?php echo $_SESSION['user_id']; ?>;
    
    fetch('../Backend/browseCourses.php?action=cancelCourse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}&course_id=${courseId}`
    })
    .then(response => response.text())
    .then(result => {
        if (result === 'success') {
            showToast('Successfully unenrolled from course');
            loadEnrolledCourses(); // Refresh the list
        } else {
            showToast('Failed to unenroll. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.');
    });
}

// Call this when the page loads
document.addEventListener('DOMContentLoaded', loadEnrolledCourses);
</script>

<script src="dashboard2.js"></script>
</body>
</html>
