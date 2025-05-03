<?php
require_once '../Backend/includes/session_check.php';
require_once '../Backend/db.php';

$instructor_username = $_SESSION['user_id']; // Assuming username is stored in session
echo "$instructor_username";

// 1. Student Registration Over Time (for students enrolled in instructor's courses)
$registration_stmt = $conn->prepare("
    SELECT 
        DATE(u.created_at) as date,
        COUNT(DISTINCT u.id) as count
    FROM users u
    JOIN user_courses uc ON u.id = uc.user_id
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE c.author = ? AND u.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE(u.created_at)
    ORDER BY date
");
$registration_stmt->bind_param("s", $instructor_username);
$registration_stmt->execute();
$registration_data = $registration_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Most Popular Courses (only for this instructor)
$popular_courses_stmt = $conn->prepare("
    SELECT 
        c.title,
        COUNT(uc.id) as enrollments,
        c.rating,
        c.price
    FROM user_courses uc
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE c.author = ?
    GROUP BY c.PrimaryID
    ORDER BY enrollments DESC
    LIMIT 10
");
$popular_courses_stmt->bind_param("s", $instructor_username);
$popular_courses_stmt->execute();
$popular_courses = $popular_courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Course Completion Rates (only for this instructor)
$completion_stmt = $conn->prepare("
    SELECT 
        c.title,
        COUNT(uc.id) as total_enrollments,
        SUM(CASE WHEN uc.progress >= 90 THEN 1 ELSE 0 END) as completed,
        ROUND(AVG(uc.progress), 1) as avg_progress
    FROM user_courses uc
    JOIN courses c ON uc.course_id = c.PrimaryID
    WHERE c.author = ?
    GROUP BY c.PrimaryID
");
$completion_stmt->bind_param("s", $instructor_username);
$completion_stmt->execute();
$completion_data = $completion_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Price vs Rating Analysis (only for this instructor)
$price_rating_stmt = $conn->prepare("
    SELECT 
        title,
        price,
        rating,
        rating_count
    FROM courses
    WHERE rating_count > 0 AND author = ?
    ORDER BY rating DESC
");
$price_rating_stmt->bind_param("s", $instructor_username);
$price_rating_stmt->execute();
$price_rating_data = $price_rating_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. Category Distribution (only for this instructor)
$category_stmt = $conn->prepare("
    SELECT 
        category,
        COUNT(*) as course_count,
        SUM(rating_count) as total_ratings,
        ROUND(AVG(rating), 2) as avg_rating
    FROM courses
    WHERE author = ?
    GROUP BY category
");
$category_stmt->bind_param("s", $instructor_username);
$category_stmt->execute();
$category_data = $category_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get profile picture
$profile_stmt = $conn->prepare("SELECT profile_pic FROM users WHERE username = ?");
$profile_stmt->bind_param("s", $instructor_username);
$profile_stmt->execute();
$user = $profile_stmt->get_result()->fetch_assoc();
$profilePicUrl = (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) 
    ? $user['profile_pic'] 
    : '../../Asset/user.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-gradient-colors"></script>
    <style>
        :root {
            --primary-color: #6C5CE7;
            --primary-dark: #5a0b9d;
            --secondary-color: #00cec9;
            --accent-color: #fd79a8;
            --text-color: #2d3748;
            --text-light: #718096;
            --bg-color: #f8fafc;
            --card-shadow: 0 8px 15px -5px rgba(108, 92, 231, 0.2);
            --neon-shadow: 0 0 10px rgba(108, 92, 231, 0.7);
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
        
        .neopop-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: white;
            box-shadow: var(--card-shadow);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 2rem;
        }
        
        .dashboard-layout {
            display: flex;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
        }
        
        .dashboard-sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: calc(100vh - 80px);
            padding: 1rem 0;
        }
        
        .sidebar-header {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
        }
        
        .sidebar-header h3 {
            font-size: 1.3rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
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
            margin: 0.5rem 1rem;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 0.8rem;
            border-radius: 8px;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(90deg, rgba(108, 92, 231, 0.1), transparent);
            color: var(--primary-dark);
            transform: translateX(5px);
            box-shadow: var(--card-shadow);
        }
        
        .sidebar-link.active {
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar-link i {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .user-profile span {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .dashboard-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-size: 2.2rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            text-shadow: var(--neon-shadow);
        }
        
        .dashboard-header p {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(108, 92, 231, 0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px -10px rgba(108, 92, 231, 0.3);
        }
        
        .stat-card h3 {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .stat-card .change {
            font-size: 0.85rem;
            color: #00b894;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(108, 92, 231, 0.1);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-header h2 {
            font-size: 1.4rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .chart-header h2 i {
            color: var(--primary-color);
        }
        
        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-btn {
            background: rgba(108, 92, 231, 0.1);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: var(--primary-dark);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .chart-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        th {
            background: rgba(108, 92, 231, 0.05);
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        tr:hover {
            background: rgba(108, 92, 231, 0.03);
        }
        
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-primary {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary-dark);
        }
        
        .badge-success {
            background: rgba(0, 184, 148, 0.1);
            color: #00b894;
        }
        
        .badge-warning {
            background: rgba(253, 203, 110, 0.1);
            color: #fdcb6e;
        }
        
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 4px;
        }
        
        @media (max-width: 1200px) {
            .dashboard-sidebar {
                width: 240px;
            }
            
            .dashboard-main {
                margin-left: 240px;
            }
        }
        
        @media (max-width: 992px) {
            .dashboard-sidebar {
                width: 220px;
            }
            
            .dashboard-main {
                margin-left: 220px;
                padding: 1.5rem;
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
                padding: 0;
            }
            
            .sidebar-menu {
                display: flex;
                overflow-x: auto;
                padding: 0;
            }
            
            .sidebar-link {
                white-space: nowrap;
                margin: 0.5rem;
            }
            
            .sidebar-link.active {
                border-left: none;
                border-bottom: 3px solid var(--primary-color);
            }
            
            .dashboard-main {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .neopop-header {
                flex-direction: column;
                padding: 1rem;
                position: relative;
            }

            .nav-links {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                margin-top: 10px;
            }

            .nav-link {
                padding: 5px 10px;
                font-size: 14px;
            }

            #logoutBtn {
                padding: 5px 10px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-main {
                padding: 1rem;
            }

            .nav-links {
                flex-direction: column;
                align-items: center;
                gap: 5px;
            }

            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .chart-actions {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 10px;
            }
        }
        .neopop-header1{
            display:none;
        }
       nav{
        gap:40px
       }
    </style>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <header class="neopop-header1">
        <div class="logo-container">
            <img src="../Asset/logo.png" alt="Learning Path Logo" class="logo" width="50px">
        </div>
        <nav class="main-nav">
            <a href="../../Backend/dashboard.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="../../Backend/courses.php" class="nav-link"><i class="fas fa-book"></i> Courses</a>
            <a href="../../Backend/progress.php" class="nav-link active"><i class="fas fa-chart-line"></i> Analytics</a>
            <div class="profile-dropdown">
                <button class="profile-icon" id="profile-icon-button">
                    <img id="user-profile-pic" class="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='../../Asset/user.png'" alt="Profile Picture" height="50">
                </button>
                <div class="dropdown-menu">
                    <a href="../../Backend/profile.php" class="dropdown-item"><i class="fas fa-user"></i> Profile</a>
                    <a href="../../Backend/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </nav>
    </header>
    <header class="neopop-header">
        <h1><i class="fas fa-graduation-cap"></i>Learning Dashboard</h1>
        <nav class="nav-links">
            <a href="dashboard.html" class="nav-link"><i class="fas fa-home"></i> Home</a>
            <a href="#" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>      
            <button id="logoutBtn" title="Logout from your account">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </nav>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-chart-pie"></i> Instructor Analytics</h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="#overview" class="sidebar-link active">
                        <i class="fas fa-tachometer-alt"></i> Overview
                    </a>
                </li>
                <li>
                    <a href="#student-growth" class="sidebar-link">
                        <i class="fas fa-users"></i> Student Growth
                    </a>
                </li>
                <li>
                    <a href="#popular-courses" class="sidebar-link">
                        <i class="fas fa-star"></i> Popular Courses
                    </a>
                </li>
                <li>
                    <a href="#completion-rates" class="sidebar-link">
                        <i class="fas fa-check-circle"></i> Completion Rates
                    </a>
                </li>
                <li>
                    <a href="#price-analysis" class="sidebar-link">
                        <i class="fas fa-tag"></i> Price Analysis
                    </a>
                </li>
                <li>
                    <a href="#category-performance" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="#student-activity" class="sidebar-link">
                        <i class="fas fa-calendar-alt"></i> Student Activity
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" onerror="this.src='../../Asset/user.png'" alt="Profile">
                    <span>Instructor Dashboard</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>Instructor Analytics Dashboard</h1>
                <p>Track and analyze your course performance and student engagement</p>
            </div>
            
            <!-- Overview Stats -->
            <div id="overview" class="section-container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Students</h3>
                <div class="value">
                    <?php 
                        $total_students = 0;
                        foreach ($completion_data as $course) {
                            $total_students += $course['total_enrollments'];
                        }
                        echo $total_students;
                    ?>
                </div>
                <div class="change">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
            </div>
            <div class="stat-card">
                <h3>Active Courses</h3>
                <div class="value"><?php echo count($popular_courses); ?></div>
                <div class="change">
                    <i class="fas fa-arrow-up"></i> 5% from last month
                </div>
            </div>
            <div class="stat-card">
                <h3>Avg Completion Rate</h3>
                <div class="value">
                    <?php 
                        $avg_completion = array_sum(array_column($completion_data, 'avg_progress')) / max(1, count($completion_data));
                        echo round($avg_completion); 
                    ?>%
                </div>
                <div class="change">
                    <i class="fas fa-arrow-up"></i> 3% from last month
                </div>
            </div>
            <div class="stat-card">
                <h3>Avg Course Rating</h3>
                <div class="value">
                    <?php 
                        $avg_rating = array_sum(array_column($price_rating_data, 'rating')) / max(1, count($price_rating_data));
                        echo round($avg_rating, 1); 
                    ?>
                </div>
                <div class="change">
                    <i class="fas fa-arrow-up"></i> 0.2 from last month
                </div>
            </div>
        </div>
    </div>
            
            <!-- Student Growth Chart -->
            <?php if (!empty($registration_data)): ?>
            <div id="student-growth" class="chart-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-users"></i> Student Registration Growth</h2>
                    <div class="chart-actions">
                        <button class="chart-btn">7 Days</button>
                        <button class="chart-btn active">30 Days</button>
                        <button class="chart-btn">6 Months</button>
                    </div>
                </div>
                <canvas id="studentGrowthChart" height="300"></canvas>
            </div>
            <?php endif; ?>
            
            <!-- Popular Courses Chart -->
            <?php if (!empty($popular_courses)): ?>
            <div id="popular-courses" class="chart-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-star"></i> Most Popular Courses</h2>
                    <div class="chart-actions">
                        <button class="chart-btn">By Enrollments</button>
                        <button class="chart-btn active">By Rating</button>
                    </div>
                </div>
                <canvas id="popularCoursesChart" height="400"></canvas>
            </div>
            <?php endif; ?>
            
            <!-- Completion Rates Table -->
            <?php if (!empty($completion_data)): ?>
            <div id="completion-rates" class="table-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-check-circle"></i> Course Completion Rates</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrollments</th>
                            <th>Completed</th>
                            <th>Completion Rate</th>
                            <th>Avg Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completion_data as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo $course['total_enrollments']; ?></td>
                                <td><?php echo $course['completed']; ?></td>
                                <td>
                                    <?php 
                                        $completion_rate = ($course['completed'] / max(1, $course['total_enrollments'])) * 100;
                                        echo round($completion_rate) . '%';
                                    ?>
                                </td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $course['avg_progress']; ?>%"></div>
                                    </div>
                                    <small><?php echo $course['avg_progress']; ?>%</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Price vs Rating Analysis -->
            <?php if (!empty($price_rating_data)): ?>
            <div id="price-analysis" class="chart-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-tag"></i> Price vs Rating Analysis</h2>
                </div>
                <canvas id="priceRatingChart" height="300"></canvas>
            </div>
            <?php endif; ?>
            
            <!-- Category Performance -->
            <?php if (!empty($category_data)): ?>
            <div id="category-performance" class="chart-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-layer-group"></i> Category Performance</h2>
                </div>
                <canvas id="categoryChart" height="300"></canvas>
            </div>
            <?php endif; ?>
            
            <!-- Student Activity Heatmap -->
            <div id="student-activity" class="chart-container section-container">
                <div class="chart-header">
                    <h2><i class="fas fa-calendar-alt"></i> Weekly Student Activity</h2>
                </div>
                <canvas id="activityHeatmap" height="300"></canvas>
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

        // Logout button functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
            window.location.href = '../../Backend/logout.php';
        });

        <?php if (!empty($registration_data)): ?>
        // Student Growth Chart (Line Chart)
        const studentGrowthCtx = document.getElementById('studentGrowthChart').getContext('2d');
        new Chart(studentGrowthCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($r) { 
                    return "'" . date('M j', strtotime($r['date'])) . "'"; 
                }, $registration_data)); ?>],
                datasets: [{
                    label: 'New Students',
                    data: [<?php echo implode(',', array_column($registration_data, 'count')); ?>],
                    backgroundColor: 'rgba(108, 92, 231, 0.1)',
                    borderColor: 'rgba(108, 92, 231, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: 'white',
                    pointBorderColor: 'rgba(108, 92, 231, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($popular_courses)): ?>
        // Popular Courses Chart (Bar Chart)
        const popularCoursesCtx = document.getElementById('popularCoursesChart').getContext('2d');
        new Chart(popularCoursesCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { 
                    return "'" . addslashes(substr($c['title'], 0, 20)) . (strlen($c['title']) > 20 ? '...' : '') . "'"; 
                }, $popular_courses)); ?>],
                datasets: [{
                    label: 'Enrollments',
                    data: [<?php echo implode(',', array_column($popular_courses, 'enrollments')); ?>],
                    backgroundColor: [
                        'rgba(108, 92, 231, 0.7)',
                        'rgba(108, 92, 231, 0.6)',
                        'rgba(108, 92, 231, 0.5)',
                        'rgba(108, 92, 231, 0.4)',
                        'rgba(108, 92, 231, 0.3)'
                    ],
                    borderColor: [
                        'rgba(108, 92, 231, 1)',
                        'rgba(108, 92, 231, 1)',
                        'rgba(108, 92, 231, 1)',
                        'rgba(108, 92, 231, 1)',
                        'rgba(108, 92, 231, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return <?php echo json_encode(array_column($popular_courses, 'title')) ?>[context[0].dataIndex];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($price_rating_data)): ?>
        // Price vs Rating Chart (Scatter Plot)
        const priceRatingCtx = document.getElementById('priceRatingChart').getContext('2d');
        new Chart(priceRatingCtx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Courses',
                    data: [<?php echo implode(',', array_map(function($c) { 
                        return "{x: " . $c['price'] . ", y: " . $c['rating'] . ", r: " . ($c['rating_count']/5) . "}"; 
                    }, $price_rating_data)); ?>],
                    backgroundColor: 'rgba(108, 92, 231, 0.7)',
                    borderColor: 'rgba(108, 92, 231, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const course = <?php echo json_encode($price_rating_data) ?>[context.dataIndex];
                                return [
                                    course.title,
                                    'Price: $' + course.price,
                                    'Rating: ' + course.rating + ' (' + course.rating_count + ' reviews)'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'Rating (1-5)'
                        },
                        min: 0,
                        max: 5
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Price ($)'
                        },
                        min: 0
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($category_data)): ?>
        // Category Performance Chart (Radar Chart)
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'radar',
            data: {
                labels: [<?php echo implode(',', array_map(function($c) { 
                    return "'" . addslashes($c['category']) . "'"; 
                }, $category_data)); ?>],
                datasets: [
                    {
                        label: 'Number of Courses',
                        data: [<?php echo implode(',', array_column($category_data, 'course_count')); ?>],
                        backgroundColor: 'rgba(108, 92, 231, 0.2)',
                        borderColor: 'rgba(108, 92, 231, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(108, 92, 231, 1)'
                    },
                    {
                        label: 'Average Rating',
                        data: [<?php echo implode(',', array_column($category_data, 'avg_rating')); ?>],
                        backgroundColor: 'rgba(0, 206, 201, 0.2)',
                        borderColor: 'rgba(0, 206, 201, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(0, 206, 201, 1)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 5
                    }
                }
            }
        });
        <?php endif; ?>

        // Student Activity Heatmap (Mock Data)
        const activityCtx = document.getElementById('activityHeatmap').getContext('2d');
        
        // Generate mock heatmap data (in a real app, you'd get this from your database)
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const hours = Array.from({length: 24}, (_, i) => i + ':00');
        const heatmapData = {
            labels: hours,
            datasets: days.map(day => ({
                label: day,
                data: hours.map(() => Math.floor(Math.random() * 100)),
                backgroundColor: hours.map(() => {
                    const val = Math.floor(Math.random() * 100);
                    const opacity = val / 100;
                    return `rgba(108, 92, 231, ${opacity})`;
                })
            }))
        };
        
        new Chart(activityCtx, {
            type: 'bar',
            data: {
                labels: hours,
                datasets: heatmapData.datasets.map(dataset => ({
                    label: dataset.label,
                    data: dataset.data,
                    backgroundColor: dataset.backgroundColor
                }))
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'right'
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