<?php
session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$class = isset($_GET['class']) ? $_GET['class'] : 'basic';
$level = isset($_GET['level']) ? intval($_GET['level']) : 1;

$class_title = ucfirst($class);

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

$check_level_query = "SELECT * FROM user_progress WHERE user_id = {$userId} AND class_level = '{$class}' AND level_number = {$level}";
$level_exists = mysqli_query($conn, $check_level_query);
if (mysqli_num_rows($level_exists) == 0) {
    mysqli_query($conn, "INSERT INTO user_progress (user_id, class_level, level_number, completed) VALUES ({$userId}, '{$class}', {$level}, FALSE)");
}

$level_completed = false;
$level_status_query = "SELECT completed FROM user_progress WHERE user_id = {$userId} AND class_level = '{$class}' AND level_number = {$level}";
$status_result = mysqli_query($conn, $level_status_query);
if ($status_row = mysqli_fetch_assoc($status_result)) {
    $level_completed = (bool)$status_row['completed'];
}

if (isset($_POST['mark_completed']) && !$level_completed) {
    mysqli_query($conn, "UPDATE user_progress SET completed = TRUE, completed_at = NOW() WHERE user_id = {$userId} AND class_level = '{$class}' AND level_number = {$level}");
    
    $next_level = $level + 1;
    if ($next_level <= 15) { // 15 is the max level per class
        $check_next_query = "SELECT * FROM user_progress WHERE user_id = {$userId} AND class_level = '{$class}' AND level_number = {$next_level}";
        $next_exists = mysqli_query($conn, $check_next_query);
        if (mysqli_num_rows($next_exists) == 0) {
            mysqli_query($conn, "INSERT INTO user_progress (user_id, class_level, level_number, completed) VALUES ({$userId}, '{$class}', {$next_level}, FALSE)");
        }
    }
    
    header("Location: level_content.php?class={$class}&level={$level}&completed=1");
    exit;
}

$difficulty = "Easy";
if ($class == 'intermediate') {
    $difficulty = $level <= 5 ? "Medium" : "Challenging";
} elseif ($class == 'advanced') {
    $difficulty = $level <= 5 ? "Hard" : "Very Hard";
} elseif ($level > 10) {
    $difficulty = "Medium";
}

$lesson_title = "Lesson $level: " . ($class == 'basic' ? 'Basic ' : ($class == 'intermediate' ? 'Intermediate ' : 'Advanced ')) . "Concepts";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level <?php echo $level; ?> - <?php echo $class_title; ?> Class - LangGo!</title>
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
        
        .level-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .level-title {
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
        
        .level-content {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .level-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 500;
            color: #3f6791;
        }
        
        .lesson-section {
            margin-bottom: 30px;
        }
        
        .lesson-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .exercise-section {
            margin-top: 30px;
        }
        
        .exercise-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .exercise-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .exercise-question {
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .option:hover {
            background-color: #f0f7ff;
        }
        
        .option input {
            margin-right: 10px;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .nav-button {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .prev-button {
            background-color: #6c757d;
            color: white;
        }
        
        .next-button {
            background-color: #3f6791;
            color: white;
        }
        
        .complete-button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .complete-button:hover {
            background-color: #218838;
        }
        
        .complete-button.completed {
            background-color: #6c757d;
            cursor: default;
        }
        
        .completion-status {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .level-info {
                flex-direction: column;
                gap: 15px;
                align-items: center;
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
                    <div class="user-level"><?php echo $class_title; ?></div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="level-header">
                <h1 class="level-title"><?php echo $class_title; ?> Class - Level <?php echo $level; ?></h1>
                <a href="class_detail.php?level=<?php echo $class; ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Levels
                </a>
            </div>
            
            <div class="level-content">
                <div class="level-info">
                    <div class="info-item">
                        <div class="info-label">Class</div>
                        <div class="info-value"><?php echo $class_title; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Level</div>
                        <div class="info-value"><?php echo $level; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Difficulty</div>
                        <div class="info-value"><?php echo $difficulty; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Estimated Time</div>
                        <div class="info-value"><?php echo ($level * 5); ?> mins</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value"><?php echo $level_completed ? "Completed" : "Not Completed"; ?></div>
                    </div>
                </div>
                
                <?php if (isset($_GET['completed']) && $_GET['completed'] == 1): ?>
                <div class="completion-status">
                    <i class="fas fa-check-circle"></i>
                    Level marked as completed successfully!
                </div>
                <?php endif; ?>
                
                <div class="lesson-section">
                    <h2 class="lesson-title"><?php echo $lesson_title; ?></h2>
                    <p>This is the content for <?php echo $class_title; ?> class, level <?php echo $level; ?>. In a real application, this would contain the actual lesson material with text, images, and interactive elements.</p>
                    <p style="margin-top: 15px;">The difficulty increases as you progress through levels and move to more advanced classes.</p>
                </div>
                
                <div class="exercise-section">
                    <h2 class="exercise-title">Practice Exercises</h2>
                    
                    <div class="exercise-item">
                        <div class="exercise-question">Exercise 1: Sample question related to this level's content?</div>
                        <div class="options">
                            <label class="option">
                                <input type="radio" name="exercise1" value="option1">
                                Option 1
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise1" value="option2">
                                Option 2
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise1" value="option3">
                                Option 3
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise1" value="option4">
                                Option 4
                            </label>
                        </div>
                    </div>
                    
                    <div class="exercise-item">
                        <div class="exercise-question">Exercise 2: Another sample question for practice?</div>
                        <div class="options">
                            <label class="option">
                                <input type="radio" name="exercise2" value="option1">
                                Option 1
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise2" value="option2">
                                Option 2
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise2" value="option3">
                                Option 3
                            </label>
                            <label class="option">
                                <input type="radio" name="exercise2" value="option4">
                                Option 4
                            </label>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="">
                    <button type="submit" name="mark_completed" class="complete-button <?php echo $level_completed ? 'completed' : ''; ?>" <?php echo $level_completed ? 'disabled' : ''; ?>>
                        <?php if (!$level_completed): ?>
                            <i class="fas fa-check"></i> Mark as Completed
                        <?php else: ?>
                            <i class="fas fa-check-double"></i> Already Completed
                        <?php endif; ?>
                    </button>
                </form>
                
                <div class="navigation-buttons">
                    <?php if ($level > 1): ?>
                        <a href="level_content.php?class=<?php echo $class; ?>&level=<?php echo $level-1; ?>" class="nav-button prev-button">
                            <i class="fas fa-arrow-left"></i>
                            Previous Level
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <a href="level_content.php?class=<?php echo $class; ?>&level=<?php echo $level+1; ?>" class="nav-button next-button">
                        Next Level
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
