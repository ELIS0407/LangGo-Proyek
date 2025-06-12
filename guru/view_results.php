<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'guru') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = null;

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
}

if (!isset($_GET['id'])) {
    header("location: quiz.php");
    exit;
}

$quiz_id = $_GET['id'];
$quiz_data = null;
$quiz_attempts = [];

$stmt = mysqli_prepare($conn, "SELECT * FROM quizzes WHERE id = ? AND created_by = ?");
mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $quiz_data = $row;
    
    $stmt = mysqli_prepare($conn, "
        SELECT qa.id, qa.user_id, qa.score, qa.completed_at, u.username, u.email
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        WHERE qa.quiz_id = ?
        ORDER BY qa.completed_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $quiz_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $quiz_attempts[] = $row;
    }
} else {
    header("location: quiz.php");
    exit;
}

$total_attempts = count($quiz_attempts);
$avg_score = 0;
$highest_score = 0;
$lowest_score = 100;

if ($total_attempts > 0) {
    $score_sum = 0;
    foreach ($quiz_attempts as $attempt) {
        $score_sum += $attempt['score'];
        $highest_score = max($highest_score, $attempt['score']);
        $lowest_score = min($lowest_score, $attempt['score']);
    }
    $avg_score = $score_sum / $total_attempts;
}

$score_ranges = [
    '0-20' => 0,
    '21-40' => 0,
    '41-60' => 0,
    '61-80' => 0,
    '81-100' => 0
];

foreach ($quiz_attempts as $attempt) {
    $score = $attempt['score'];
    if ($score <= 20) {
        $score_ranges['0-20']++;
    } elseif ($score <= 40) {
        $score_ranges['21-40']++;
    } elseif ($score <= 60) {
        $score_ranges['41-60']++;
    } elseif ($score <= 80) {
        $score_ranges['61-80']++;
    } else {
        $score_ranges['81-100']++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - LangGo!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header {
            background-color: #3f6791;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            margin-right: 10px;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            color: white;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .user-level {
            font-size: 12px;
            color: #ccc;
        }
        
        .main-content {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #3f6791;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: #666;
            text-align: center;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: #3f6791;
            text-decoration: none;
        }
        
        .breadcrumb .separator {
            color: #999;
        }
        
        .breadcrumb .current {
            color: #666;
        }
        
        .results-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            color: #3f6791;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quiz-info {
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 14px;
            color: #666;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 500;
            color: #3f6791;
        }
        
        .stats-section {
            margin-top: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 30px;
            color: #3f6791;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #3f6791;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .distribution-section {
            margin-top: 30px;
        }
        
        .distribution-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 200px;
            margin-top: 20px;
            padding: 0 10px;
        }
        
        .chart-bar {
            width: 60px;
            background-color: #3f6791;
            border-radius: 5px 5px 0 0;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .bar-value {
            position: absolute;
            top: -25px;
            font-size: 14px;
            font-weight: 500;
            color: #3f6791;
        }
        
        .bar-label {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .divider {
            width: 100%;
            height: 1px;
            background-color: #eee;
            margin: 30px 0;
        }
        
        .attempts-section {
            margin-top: 20px;
        }
        
        .attempts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .attempts-table th, .attempts-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .attempts-table th {
            color: #3f6791;
            font-weight: 500;
            background-color: #f9f9f9;
        }
        
        .score-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .score-high {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .score-medium {
            background-color: #fff8e1;
            color: #ff9800;
        }
        
        .score-low {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .back-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
        }
        
        .back-btn:hover {
            background-color: #2c4b6a;
        }
        
        @media (max-width: 768px) {
            .stats-grid, .info-grid {
                grid-template-columns: 1fr;
            }
            
            .distribution-chart {
                flex-direction: column;
                height: auto;
                align-items: flex-start;
                gap: 30px;
            }
            
            .chart-bar {
                width: 100%;
                height: 40px;
                border-radius: 0 5px 5px 0;
            }
            
            .bar-value {
                right: 10px;
                left: auto;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .bar-label {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                margin-top: 0;
            }
            
            .attempts-table thead {
                display: none;
            }
            
            .attempts-table, .attempts-table tbody, .attempts-table tr, .attempts-table td {
                display: block;
                width: 100%;
            }
            
            .attempts-table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
            }
            
            .attempts-table td {
                text-align: right;
                padding: 10px;
                position: relative;
                border-bottom: 1px solid #eee;
            }
            
            .attempts-table td:last-child {
                border-bottom: none;
            }
            
            .attempts-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 50%;
                font-weight: 500;
                text-align: left;
                color: #3f6791;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="../Logo-LangGo.png" alt="LangGo Logo">
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    DASHBOARD
                </a>
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
                </a>
                <a href="quiz.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                    <div class="user-level">Teacher</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="breadcrumb">
                <a href="quiz.php">Quiz Management</a>
                <span class="separator">></span>
                <span class="current">Quiz Results</span>
            </div>
            
            <h1 class="page-title">Quiz Results</h1>
            <p class="page-subtitle"><?php echo htmlspecialchars($quiz_data['title']); ?></p>
            
            <div class="results-container">
                <div class="quiz-info">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Quiz Information
                    </h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Title</div>
                            <div class="info-value"><?php echo htmlspecialchars($quiz_data['title']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Level</div>
                            <div class="info-value"><?php echo ucfirst(htmlspecialchars($quiz_data['class_level'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Code</div>
                            <div class="info-value"><?php echo htmlspecialchars($quiz_data['code']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Created</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($quiz_data['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($total_attempts > 0): ?>
                    <div class="stats-section">
                        <h2 class="section-title">
                            <i class="fas fa-chart-pie"></i>
                            Statistics
                        </h2>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-users stat-icon"></i>
                                <div class="stat-value"><?php echo $total_attempts; ?></div>
                                <div class="stat-label">Total Attempts</div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-calculator stat-icon"></i>
                                <div class="stat-value"><?php echo number_format($avg_score, 1); ?></div>
                                <div class="stat-label">Average Score</div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-arrow-up stat-icon"></i>
                                <div class="stat-value"><?php echo $highest_score; ?></div>
                                <div class="stat-label">Highest Score</div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-arrow-down stat-icon"></i>
                                <div class="stat-value"><?php echo $lowest_score; ?></div>
                                <div class="stat-label">Lowest Score</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="distribution-section">
                        <h2 class="section-title">
                            <i class="fas fa-chart-bar"></i>
                            Score Distribution
                        </h2>
                        
                        <div class="distribution-chart">
                            <?php foreach ($score_ranges as $range => $count): ?>
                                <?php 
                                    $height = $total_attempts > 0 ? ($count / $total_attempts) * 100 : 0;
                                    $height = max($height, 5);
                                ?>
                                <div class="chart-bar" style="height: <?php echo $height; ?>%">
                                    <span class="bar-value"><?php echo $count; ?></span>
                                    <span class="bar-label"><?php echo $range; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="attempts-section">
                        <h2 class="section-title">
                            <i class="fas fa-list-ul"></i>
                            Attempt Details
                        </h2>
                        
                        <table class="attempts-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Score</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_attempts as $attempt): ?>
                                    <tr>
                                        <td data-label="Student"><?php echo htmlspecialchars($attempt['username']); ?></td>
                                        <td data-label="Email"><?php echo htmlspecialchars($attempt['email']); ?></td>
                                        <td data-label="Score">
                                            <?php
                                                $score_class = '';
                                                if ($attempt['score'] >= 80) {
                                                    $score_class = 'score-high';
                                                } elseif ($attempt['score'] >= 50) {
                                                    $score_class = 'score-medium';
                                                } else {
                                                    $score_class = 'score-low';
                                                }
                                            ?>
                                            <span class="score-pill <?php echo $score_class; ?>"><?php echo $attempt['score']; ?></span>
                                        </td>
                                        <td data-label="Date"><?php echo date('d M Y H:i', strtotime($attempt['completed_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check empty-icon"></i>
                        <p>No attempts yet.</p>
                        <p>Share the quiz code with your students!</p>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="quiz.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Quiz Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 