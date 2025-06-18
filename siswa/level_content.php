<?php
session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Check if user_progress table exists, if not create it and initialize user's progress
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
    
    // Initialize level 1 for all existing students
    $get_users_query = "SELECT id FROM users WHERE role = 'siswa'";
    $users_result = mysqli_query($conn, $get_users_query);
    while ($user = mysqli_fetch_assoc($users_result)) {
        mysqli_query($conn, "INSERT INTO user_progress (user_id, class_level, level_number, completed) VALUES ({$user['id']}, 'basic', 1, FALSE)");
    }
}

// Get the class level from URL parameter
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

// Check if intermediate level is accessible
$basic_level10_completed = false;
if ($class_level == 'intermediate') {
    $check_query = "SELECT completed FROM user_progress 
                   WHERE user_id = $userId AND class_level = 'basic' AND level_number = 10";
    $result = mysqli_query($conn, $check_query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $basic_level10_completed = $row['completed'];
    }
    
    if (!$basic_level10_completed) {
        // Redirect to basic level if intermediate is not accessible
        header("Location: level_content.php?level=basic");
        exit;
    }
}

// Check if advanced level is accessible
$intermediate_level10_completed = false;
if ($class_level == 'advanced') {
    $check_query = "SELECT completed FROM user_progress 
                   WHERE user_id = $userId AND class_level = 'intermediate' AND level_number = 10";
    $result = mysqli_query($conn, $check_query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $intermediate_level10_completed = $row['completed'];
    }
    
    if (!$intermediate_level10_completed) {
        // Redirect to intermediate level if advanced is not accessible
        header("Location: level_content.php?level=intermediate");
        exit;
    }
}

// Get user's progress for the selected class level
$progress_query = "SELECT level_number, completed FROM user_progress 
                  WHERE user_id = {$userId} AND class_level = '{$class_level}'";
$progress_result = mysqli_query($conn, $progress_query);

$user_progress = [];
while ($row = mysqli_fetch_assoc($progress_result)) {
    $user_progress[$row['level_number']] = $row['completed'];
}

// Initialize progress for levels that don't have entries yet
for ($i = 1; $i <= 10; $i++) {
    if (!isset($user_progress[$i])) {
        // Insert a record for this level if it doesn't exist
        $insert_query = "INSERT IGNORE INTO user_progress (user_id, class_level, level_number, completed) 
                        VALUES ($userId, '$class_level', $i, FALSE)";
        mysqli_query($conn, $insert_query);
        $user_progress[$i] = false;
    }
}

// Get completed levels count for this user and class level
$completed_count_query = "SELECT COUNT(*) as completed_count FROM user_progress 
                         WHERE user_id = $userId AND class_level = '$class_level' AND completed = 1";
$completed_result = mysqli_query($conn, $completed_count_query);
$completed_count = 0;

if ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_count = $row['completed_count'];
}

// For this implementation, only level 1 is unlocked by default
// Other levels are locked regardless of completion status
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($class_level); ?> Class - LangGo!</title>
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
            height: 65px;
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
            padding: 40px 20px;
        }
        
        .class-title {
            font-size: 28px;
            color: #3f6791;
            margin-bottom: 40px;
            font-weight: 500;
        }
        
        .level-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            width: 100%;
            max-width: 1000px;
        }
        
        .level-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #333;
        }
        
        .level-circle {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            background-color: #9b8b16;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .level-circle i {
            font-size: 32px;
            color: #f5f5f5;
        }
        
        .level-number {
            font-size: 18px;
            font-weight: 500;
        }
        
        .level-locked .level-circle {
            background-color: #999;
            position: relative;
        }
        
        .level-locked .level-number {
            color: #999;
        }
        
        .lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
        }
        
        .back-button {
            position: absolute;
            top: 100px;
            left: 20px;
            background-color: #3f6791;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .level-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .level-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="../assets/img/Logo-LangGo.png" alt="LangGo Logo">
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
        
        <a href="class.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="main-content">
            <h1 class="class-title"><?php echo ucfirst($class_level); ?> class</h1>
            
            <div style="margin-bottom: 20px;">
                <a href="leaderboard.php?level=<?php echo $class_level; ?>" class="leaderboard-button" style="background-color: #3f6791; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fas fa-trophy"></i> View Leaderboard
                </a>
            </div>
            
            <div class="level-grid">
                <?php 
                // Display 15 levels
                for ($i = 1; $i <= 15; $i++): 
                    // Check if this level should be unlocked based on previous level completion
                    $is_locked = true;
                    
                    if ($i == 1) {
                        // Level 1 is always unlocked
                        $is_locked = false;
                    } else if ($class_level == 'basic' && $i <= 10) {
                        // For basic class levels 2-10, check if previous level is completed
                        $is_locked = !isset($user_progress[$i-1]) || !$user_progress[$i-1];
                    } else if ($class_level == 'intermediate' && $i <= 10) {
                        // For intermediate class levels 2-10, check if previous level is completed
                        $is_locked = !isset($user_progress[$i-1]) || !$user_progress[$i-1];
                    } else if ($class_level == 'advanced' && $i <= 10) {
                        // For advanced class levels 2-10, check if previous level is completed
                        $is_locked = !isset($user_progress[$i-1]) || !$user_progress[$i-1];
                    } else if ($i > 10 && $class_level == 'basic') {
                        // Levels beyond 10 are always locked for basic
                        $is_locked = true;
                    } else if ($i > 10 && $class_level == 'intermediate') {
                        // Levels beyond 10 are always locked for intermediate
                        $is_locked = true;
                    } else if ($i > 10 && $class_level == 'advanced') {
                        // Levels beyond 10 are always locked for advanced
                        $is_locked = true;
                    } else {
                        // For other classes, only level 1 is unlocked
                        $is_locked = ($i > 1);
                    }
                    
                    // Set URL based on class level and if locked
                    if ($is_locked) {
                        $level_url = "javascript:void(0)";
                    } else {
                        // For basic levels 1-10, use the basic_levels.php file
                        if ($class_level == 'basic' && $i <= 10) {
                            $level_url = "basic_levels.php?level={$i}";
                        } 
                        // For intermediate levels 1-10, use the intermediate_levels.php file
                        else if ($class_level == 'intermediate' && $i <= 10) {
                            $level_url = "intermediate_levels.php?level={$i}";
                        }
                        // For advanced levels 1-10, use the advanced_levels.php file
                        else if ($class_level == 'advanced' && $i <= 10) {
                            $level_url = "advanced_levels.php?level={$i}";
                        }
                        else {
                            $level_url = "content.php?level={$class_level}&number={$i}";
                        }
                    }
                    
                    // Mark level as completed if it exists in user_progress
                    $is_completed = isset($user_progress[$i]) && $user_progress[$i];
                    
                    $level_class = $is_locked ? "level-item level-locked" : "level-item";
                ?>
                    <a href="<?php echo $level_url; ?>" class="<?php echo $level_class; ?>">
                        <div class="level-circle">
                            <?php if ($is_locked): ?>
                                <div class="lock-overlay">
                                    <i class="fas fa-lock"></i>
                                </div>
                            <?php elseif ($is_completed): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-graduation-cap"></i>
                            <?php endif; ?>
                        </div>
                        <div class="level-number">Level <?php echo $i; ?></div>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</body>
</html>
