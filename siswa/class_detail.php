<?php
session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$level = isset($_GET['level']) ? $_GET['level'] : 'basic';
$level_title = ucfirst($level);

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

$num_levels = 15;

$current_level = 0;
$progress_query = "SELECT MAX(level_number) as max_level FROM user_progress 
                   WHERE user_id = {$userId} AND class_level = '{$level}'";
$progress_result = mysqli_query($conn, $progress_query);

if ($progress_row = mysqli_fetch_assoc($progress_result)) {
    if ($progress_row['max_level'] !== NULL) {
        $current_level = $progress_row['max_level'];
    }
}

$basic_completed = false;
if ($level != 'basic') {
    $basic_levels_query = "SELECT COUNT(*) as completed_count FROM user_progress 
                          WHERE user_id = {$userId} AND class_level = 'basic' 
                          AND level_number <= 15 AND completed = TRUE";
    $basic_result = mysqli_query($conn, $basic_levels_query);
    $basic_row = mysqli_fetch_assoc($basic_result);
    
    if ($basic_row && $basic_row['completed_count'] == 15) {
        $basic_completed = true;
    } else {
        header("Location: class.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $level_title; ?> Class - LangGo!</title>
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
            padding: 20px;
        }
        
        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .class-title {
            font-size: 24px;
            color: #3f6791;
            font-weight: 500;
        }
        
        .back-button {
            background-color: #3f6791;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .levels-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .level-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #333;
        }
        
        .level-circle {
            width: 80px;
            height: 80px;
            background-color: #9c8306;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            position: relative;
        }
        
        .level-circle.locked {
            background-color: #999;
            cursor: not-allowed;
        }
        
        .level-circle.completed {
            background-color: #28a745;
        }
        
        .level-icon {
            width: 60px;
            height: 60px;
            background-color: #f8e8a0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .level-icon i {
            font-size: 30px;
            color: #666;
        }
        
        .level-icon.completed i {
            color: #218838;
        }
        
        .level-number {
            font-weight: 500;
            color: #9c8306;
        }
        
        .level-number.locked {
            color: #999;
        }
        
        .level-number.completed {
            color: #28a745;
        }
        
        .lock-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: rgba(0, 0, 0, 0.5);
        }
        
        .check-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #28a745;
            border: 2px solid #28a745;
        }
        
        @media (max-width: 768px) {
            .levels-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .levels-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <div>
                    <?php echo $username; ?>
                    <div class="user-level"><?php echo $level_title; ?></div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="class-header">
                <h1 class="class-title"><?php echo $level_title; ?> class</h1>
                <a href="class.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
            </div>
            
            <div class="levels-grid">
                <?php for ($i = 1; $i <= $num_levels; $i++): 
                    $is_completed = false;
                    $level_status_query = "SELECT completed FROM user_progress 
                                         WHERE user_id = {$userId} AND class_level = '{$level}' 
                                         AND level_number = {$i}";
                    $status_result = mysqli_query($conn, $level_status_query);
                    if ($status_row = mysqli_fetch_assoc($status_result)) {
                        $is_completed = (bool)$status_row['completed'];
                    }
                    
                    $is_locked = $i > $current_level + 1;
                    
                    $circle_class = $is_locked ? 'locked' : ($is_completed ? 'completed' : '');
                    $number_class = $is_locked ? 'locked' : ($is_completed ? 'completed' : '');
                    $icon_class = $is_completed ? 'completed' : '';
                ?>
                    <a href="<?php echo $is_locked ? '#' : "level_content.php?class=$level&level=$i"; ?>" class="level-item">
                        <div class="level-circle <?php echo $circle_class; ?>">
                            <div class="level-icon <?php echo $icon_class; ?>">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <?php if ($is_locked): ?>
                                <div class="lock-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                            <?php elseif ($is_completed): ?>
                                <div class="check-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="level-number <?php echo $number_class; ?>">Level <?php echo $i; ?></div>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</body>
</html> 