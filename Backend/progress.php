<?php
require_once 'includes/session_check.php';
require_once 'db.php';

// Get user data
$user_id = $_SESSION['user_id'];

// 1. Get enrolled courses with progress
$courses_stmt = $conn->prepare("
    SELECT c.PrimaryID, c.title, c.image as thumbnail, c.category, uc.progress 
    FROM user_courses uc
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE uc.user_id = ?
    ORDER BY uc.last_accessed DESC
");
$courses_stmt->bind_param("i", $user_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Get progress by category
$category_stmt = $conn->prepare("
    SELECT c.category, AVG(uc.progress) as avg_progress
    FROM user_courses uc
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE uc.user_id = ?
    GROUP BY c.category
");
$category_stmt->bind_param("i", $user_id);
$category_stmt->execute();
$category_progress = $category_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Get learning activity (last 30 days)
$activity_stmt = $conn->prepare("
    SELECT 
        DATE(last_accessed) as access_date,
        COUNT(*) as course_count,
        AVG(progress) as avg_progress
    FROM user_courses
    WHERE user_id = ? 
    AND last_accessed >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(last_accessed)
    ORDER BY access_date
");
$activity_stmt->bind_param("i", $user_id);
$activity_stmt->execute();
$learning_activity = $activity_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_courses = count($courses);
$avg_completion = $total_courses > 0 ? array_sum(array_column($courses, 'progress')) / $total_courses : 0;
$total_categories = count($category_progress);

// Get profile picture
$profile_stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$profile_stmt->bind_param("i", $_SESSION['user_id']);
$profile_stmt->execute();
$user = $profile_stmt->get_result()->fetch_assoc();
$profilePicUrl = (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) 
    ? $user['profile_pic'] 
    : '../Asset/user.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #6C5CE7;
            --primary-dark: #5a0b9d;
            --text-color: #2d3748;
            --text-light: #718096;
            --bg-color: #f8fafc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        
        .dashboard-layout {
            display: flex;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
        }
        
        .dashboard-sidebar {
            width: 20%;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 70vh;
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .sidebar-header h3 {
            font-size: 1.1rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem 0;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 0.8rem;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary-dark);
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-link i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
           
            padding: 1rem;
            border-top: 1px solid #f1f1f1;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .user-profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-profile span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .dashboard-main {
            flex: 1;
            margin-left: 20%;
            padding: 2rem;
        }
        
        .section-container {
            scroll-margin-top: 100px;
            margin-bottom: 2rem;
            
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }
        
        .chart-container {
            width: 100%;
            height: 78vh;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .chart-container h2 {
            margin-bottom: 1rem;
            font-size: 1.3rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .chart-container h2 i {
            color: var(--primary-color);
        }
        
        .course-progress {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }
        
        .course-progress:hover {
            transform: translateX(5px);
        }
        
        .course-thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 1.5rem;
        }
        
        .course-info {
            flex: 1;
        }
        
        .course-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .course-category {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 6px;
            position: relative;
            transition: width 0.5s ease;
        }
        
        .progress-percent {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .no-courses {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 992px) {
            .dashboard-sidebar {
                width: 220px;
            }
            
            .dashboard-main {
                margin-left: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-layout {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                position: static;
                width: 100%;
                height: auto;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .dashboard-main {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                padding: 0;
            }
            
            .sidebar-link {
                padding: 1rem;
                border-bottom: 3px solid transparent;
                border-left: none;
                white-space: nowrap;
            }
            
            .sidebar-link:hover, .sidebar-link.active {
                border-left: none;
                border-bottom: 3px solid var(--primary-color);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .course-progress {
                flex-direction: column;
                text-align: center;
            }
            
            .course-thumbnail {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="../User Dashboard/styles.css">
</head>
<body>
    <header class="neopop-header">
        <div class="logo-container">
            <img src="../Asset/logo.png" alt="Learning Path Logo" class="logo" width="50px">
        </div>
        <nav class="main-nav">
            <a href="../User Dashboard/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="../User Dashboard/dashboard.php#category-nav" class="nav-link"><i class="fas fa-book"></i> My Courses</a>
            <a href="#" class="nav-link active"><i class="fas fa-chart-line"></i> Progress</a>
            <div class="profile-dropdown">
                <button class="profile-icon" id="profile-icon-button">
                    <img id="user-profile-pic" class="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='../Asset/user.png'" alt="Profile Picture" height="50">
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-chart-line"></i> Progress Dashboard</h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#stats-cards" class="sidebar-link active">
                        <i class="fas fa-tachometer-alt"></i> Overview
                    </a>
                </li>
                <li>
                    <a href="#course-progress-chart" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i> Course Progress
                    </a>
                </li>
                <li>
                    <a href="#category-progress-chart" class="sidebar-link">
                        <i class="fas fa-chart-pie"></i> By Category
                    </a>
                </li>
                <li>
                    <a href="#activity-chart" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Activity
                    </a>
                </li>
                <li>
                    <a href="#course-list" class="sidebar-link">
                        <i class="fas fa-book"></i> My Courses
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='../Asset/user.png'" alt="Profile">
                    <!-- <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> -->
                    <span>Sonal Kumari</span> 
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>My Learning Dashboard</h1>
                <p>Track your progress and learning statistics</p>
            </div>
            
            <!-- Statistics Cards -->
            <div id="stats-cards" class="section-container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Enrolled Courses</h3>
                        <div class="value"><?php echo $total_courses; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Average Completion</h3>
                        <div class="value"><?php echo round($avg_completion); ?>%</div>
                    </div>
                    <div class="stat-card">
                        <h3>Categories</h3>
                        <div class="value"><?php echo $total_categories; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Course Progress Chart -->
            <div id="course-progress-chart" class="chart-container section-container">
                <h2><i class="fas fa-chart-bar"></i> Course Progress</h2>
                <canvas id="courseProgressChart" ></canvas>
            </div>
            
            <!-- Category Progress Chart -->
            <div id="category-progress-chart" class="chart-container section-container">
                <h2><i class="fas fa-chart-pie"></i> Progress by Category</h2>
                <canvas id="categoryProgressChart" ></canvas>
            </div>
            
            <!-- Learning Activity Chart -->
            <div id="activity-chart" class="chart-container section-container">
                <h2><i class="fas fa-calendar-alt"></i> Recent Learning Activity</h2>
                <canvas id="activityChart" ></canvas>
            </div>
            
            <!-- Course List -->
            <div id="course-list" class="section-container">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary-dark);">
                    <i class="fas fa-book"></i> My Courses
                </h2>
                
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-progress">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="../Admin/uploads/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                     class="course-thumbnail">
                            <?php else: ?>
                                <div class="course-thumbnail" style="background: #eee; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-book" style="font-size: 1.5rem; color: #999;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="course-info">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <div class="course-category"><?php echo htmlspecialchars($course['category']); ?></div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo min(100, max(0, $course['progress'])); ?>%;">
                                        <span class="progress-percent"><?php echo min(100, max(0, $course['progress'])); ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-courses">
                        <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 1rem; color: #ddd;"></i>
                        <h3>No courses enrolled yet</h3>
                        <p>Start learning by enrolling in courses from the dashboard</p>
                        <a href="../User Dashboard/dashboard.php#category-nav" 
                           style="display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; 
                                  background: var(--primary-color); color: white; border-radius: 5px; 
                                  text-decoration: none;">
                            Browse Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Profile dropdown functionality
        document.getElementById('profile-icon-button').addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.dropdown-menu').classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            const dropdown = document.querySelector('.dropdown-menu');
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        // Course Progress Chart
        const courseProgressCtx = document.getElementById('courseProgressChart').getContext('2d');
        new Chart(courseProgressCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { return "'" . addslashes(substr($c['title'], 0, 20)) . (strlen($c['title']) > 20 ? '...' : '') . "'"; }, $courses)); ?>],
                datasets: [{
                    label: 'Completion %',
                    data: [<?php echo implode(',', array_column($courses, 'progress')); ?>],
                    backgroundColor: '#6C5CE7',
                    borderColor: '#5a0b9d',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Completion %'
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return context[0].label + '...'; // Show full title in tooltip
                            }
                        }
                    }
                }
            }
        });

        // Category Progress Chart
        const categoryCtx = document.getElementById('categoryProgressChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { return "'" . addslashes($c['category']) . "'"; }, $category_progress)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($category_progress, 'avg_progress')); ?>],
                    backgroundColor: [
                        '#6C5CE7', '#00b894', '#0984e3', '#fd79a8', '#fdcb6e', '#e17055'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + Math.round(context.raw) + '%';
                            }
                        }
                    }
                }
            }
        });

        // Learning Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    if (count($learning_activity) > 0) {
                        echo implode(',', array_map(function($a) { 
                            return "'" . date('M j', strtotime($a['access_date'])) . "'"; 
                        }, $learning_activity));
                    }
                ?>],
                datasets: [
                    {
                        label: 'Courses Accessed',
                        data: [<?php 
                            if (count($learning_activity) > 0) {
                                echo implode(',', array_column($learning_activity, 'course_count')); 
                            }
                        ?>],
                        backgroundColor: '#6C5CE7',
                        borderColor: '#6C5CE7',
                        tension: 0.3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg Progress %',
                        data: [<?php 
                            if (count($learning_activity) > 0) {
                                echo implode(',', array_column($learning_activity, 'avg_progress')); 
                            }
                        ?>],
                        backgroundColor: '#00b894',
                        borderColor: '#00b894',
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Courses Accessed'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        max: 100,
                        title: {
                            display: true,
                            text: 'Progress %'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // Smooth scrolling and active link highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            const sections = document.querySelectorAll('.section-container');
            
            // Smooth scrolling for sidebar links
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    
                    // Remove active class from all links
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    link.classList.add('active');
                    
                    // Scroll to section
                    targetSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });
            
            // Highlight active section while scrolling
            window.addEventListener('scroll', function() {
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    
                    if (pageYOffset >= (sectionTop - 150)) {
                        current = '#' + section.getAttribute('id');
                    }
                });
                
                sidebarLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === current) {
                        link.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>