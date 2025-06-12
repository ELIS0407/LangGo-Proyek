<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['score']) || !isset($_GET['quiz'])) {
    header("location: quiz.php");
    exit;
}

$score = intval($_GET['score']);
$quiz_title = $_GET['quiz'];
$username = $_SESSION['username'];
$correct_count = isset($_GET['correct']) ? intval($_GET['correct']) : 0;
$incorrect_count = isset($_GET['incorrect']) ? intval($_GET['incorrect']) : 0;
$streak = isset($_GET['streak']) ? intval($_GET['streak']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - LangGo!</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #f0f7ff;
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 150px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .correct-icon {
            background-color: rgba(0, 200, 83, 0.1);
            color: #00c853;
        }
        
        .incorrect-icon {
            background-color: rgba(255, 23, 68, 0.1);
            color: #ff1744;
        }
        
        .streak-icon {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
        
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            width: 100%;
        }
        
        .action-btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-home {
            background-color: #3f6791;
            color: white;
            border: none;
        }
        
        .btn-home:hover {
            background-color: #2c4b6a;
        }
        
        .btn-new-quiz {
            background-color: white;
            color: #3f6791;
            border: 2px solid #3f6791;
        }
        
        .btn-new-quiz:hover {
            background-color: #f0f7ff;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-card {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
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
                <a href="class.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    CLASS
                </a>
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
                </a>
                <a href="quiz.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    PROFILE
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                    <div class="user-level">Advanced</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon correct-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $correct_count; ?></div>
                        <div class="stat-label">CORRECT</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon incorrect-icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $incorrect_count; ?></div>
                        <div class="stat-label">INCORRECT</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon streak-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $streak; ?></div>
                        <div class="stat-label">STREAK</div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="quiz.php" class="action-btn btn-new-quiz">
                    <i class="fas fa-clipboard-list"></i>
                    Another Quiz
                </a>
                <a href="dashboard.php" class="action-btn btn-home">
                    <i class="fas fa-home"></i>
                    Home
                </a>
            </div>
        </div>
    </div>
</body>
</html> 