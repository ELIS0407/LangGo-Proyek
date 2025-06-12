<?php
session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

$check_table_query = "SHOW TABLES LIKE 'user_progress'";
$table_exists = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($table_exists) == 0) {
    $create_table_query = "CREATE TABLE user_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        class_level ENUM('basic', 'intermediate', 'advanced') NOT NULL,
        level_number INT NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        completed_at TIMESTAMP NULL,
        UNIQUE KEY user_class_level (user_id, class_level, level_number),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $create_table_query);
    $get_users_query = "SELECT id FROM users WHERE role = 'siswa'";
    $users_result = mysqli_query($conn, $get_users_query);
    while ($user = mysqli_fetch_assoc($users_result)) {
        mysqli_query($conn, "INSERT INTO user_progress (user_id, class_level, level_number, completed) VALUES ({$user['id']}, 'basic', 1, FALSE)");
    }
}

$basic_levels_completed = false;
$basic_levels_query = "SELECT COUNT(*) as completed_count FROM user_progress 
                      WHERE user_id = {$userId} AND class_level = 'basic' 
                      AND level_number <= 15 AND completed = TRUE";
$result = mysqli_query($conn, $basic_levels_query);
$row = mysqli_fetch_assoc($result);

if ($row && $row['completed_count'] == 15) {
    $basic_levels_completed = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Selection - LangGo!</title>
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
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .class-selection-title {
            font-size: 28px;
            color: #3f6791;
            margin-bottom: 40px;
            font-weight: 500;
        }
        
        .class-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            max-width: 400px;
        }
        
        .class-option {
            background-color: #3f6791;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .class-option:hover {
            background-color: #345a7e;
        }
        
        .class-option.locked {
            background-color: #999;
            cursor: not-allowed;
            position: relative;
            overflow: hidden;
        }
        
        .lock-icon {
            margin-left: 10px;
            font-size: 16px;
        }
        
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 250px;
            background-color: rgba(0,0,0,0.8);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 14px;
            font-weight: normal;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                gap: 10px;
            }
            
            .nav-item {
                padding: 8px 10px;
                font-size: 14px;
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
                <a href="class.php" class="nav-item active">
                    <i class="fas fa-graduation-cap"></i>
                    CLASS
                </a>
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
                </a>
                <a href="quiz.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    PROFILE
                </a>
            </div>
            <div class="user-info">
                <?php echo $username; ?>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="class-selection-title">Class Selection</h1>
            
            <div class="class-options">
                <a href="class_detail.php?level=basic" class="class-option">Basic</a>
                
                <?php if ($basic_levels_completed): ?>
                    <a href="class_detail.php?level=intermediate" class="class-option">Intermediate</a>
                <?php else: ?>
                    <div class="tooltip">
                        <div class="class-option locked">
                            Intermediate <i class="fas fa-lock lock-icon"></i>
                            <span class="tooltip-text">Complete all Basic levels (1-15) first to unlock Intermediate class</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($basic_levels_completed): ?>
                    <a href="class_detail.php?level=advanced" class="class-option">Advanced</a>
                <?php else: ?>
                    <div class="tooltip">
                        <div class="class-option locked">
                            Advanced <i class="fas fa-lock lock-icon"></i>
                            <span class="tooltip-text">Complete all Basic levels (1-15) first to unlock Advanced class</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 