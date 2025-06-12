<?php
session_start();
include '../config.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['code'])) {
    header("location: quiz.php");
    exit;
}

$quiz_code = $_GET['code'];
$quiz_data = null;
$quiz_questions = [];
$username = $_SESSION['username'];
$user_id = null;
$current_question = isset($_GET['q']) ? intval($_GET['q']) : 0;
$selected_option = isset($_GET['selected']) ? $_GET['selected'] : '';
$is_correct = false;
$correct_answer = '';
$streak = isset($_SESSION['quiz_streak']) ? $_SESSION['quiz_streak'] : 0;
$correct_count = isset($_SESSION['correct_count']) ? $_SESSION['correct_count'] : 0;
$incorrect_count = isset($_SESSION['incorrect_count']) ? $_SESSION['incorrect_count'] : 0;

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
}

$stmt = mysqli_prepare($conn, "SELECT id, title, description, class_level FROM quizzes WHERE code = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, "s", $quiz_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $quiz_data = $row;
    
    $stmt = mysqli_prepare($conn, "SELECT id, question, option_a, option_b, option_c, option_d, correct_answer FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
    mysqli_stmt_bind_param($stmt, "i", $quiz_data['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $quiz_questions[] = $row;
    }
} else {
    header("Location: quiz.php");
    exit;
}

if (count($quiz_questions) == 0) {
    header("Location: quiz.php");
    exit;
}

if ($current_question >= count($quiz_questions)) {
    // Calculate final score
    $score = $correct_count * 10;
    
    // Record quiz attempt
    $stmt = mysqli_prepare($conn, "INSERT INTO quiz_attempts (quiz_id, user_id, score) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iii", $quiz_data['id'], $user_id, $score);
    mysqli_stmt_execute($stmt);
    
    $streak_value = $streak;
    
    unset($_SESSION['quiz_streak']);
    unset($_SESSION['correct_count']);
    unset($_SESSION['incorrect_count']);
    
    header("Location: quiz_result_new.php?score=" . $score . "&quiz=" . urlencode($quiz_data['title']) . "&correct=" . $correct_count . "&incorrect=" . $incorrect_count . "&streak=" . $streak_value);
    exit;
}

$current_q = $quiz_questions[$current_question];

if ($selected_option) {
    $correct_answer = $current_q['correct_answer'];
    $is_correct = ($selected_option === $correct_answer);
    
    if ($is_correct) {
        $streak++;
        $correct_count++;
    } else {
        $streak = 0;
        $incorrect_count++;
    }
    
    $_SESSION['quiz_streak'] = $streak;
    $_SESSION['correct_count'] = $correct_count;
    $_SESSION['incorrect_count'] = $incorrect_count;
}

function getOptionText($question, $option) {
    switch ($option) {
        case 'A': return $question['option_a'];
        case 'B': return $question['option_b'];
        case 'C': return $question['option_c'];
        case 'D': return $question['option_d'];
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - LangGo!</title>
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
        
        .quiz-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .question-container {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            width: 100%;
            text-align: center;
        }
        
        .question-text {
            font-size: 20px;
            font-weight: 500;
            color: #333;
            padding: 20px;
        }
        
        .options-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }
        
        .option-btn {
            padding: 15px 20px;
            border-radius: 15px;
            border: none;
            background-color: #f9f9f9;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-decoration: none;
        }
        
        .option-btn:hover {
            background-color: #f0f0f0;
        }
        
        .option-btn.correct {
            background-color: #00c853;
            color: white;
        }
        
        .option-btn.incorrect {
            background-color: #ff1744;
            color: white;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-top: 30px;
        }
        
        .stat-card {
            background-color: #f0f7ff;
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 150px;
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
        
        .next-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 30px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .next-btn:hover {
            background-color: #2c4b6a;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .stat-card {
                width: 100%;
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
                    <div class="user-level"><?php echo ucfirst($quiz_data['class_level']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="quiz-container">
                <div class="question-container">
                    <div class="question-text">
                        <?php echo htmlspecialchars($current_q['question']); ?>
                    </div>
                </div>
                
                <?php if (!$selected_option): ?>
                <div class="options-container">
                    <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question; ?>&selected=A" class="option-btn">
                        <?php echo htmlspecialchars($current_q['option_a']); ?>
                    </a>
                    <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question; ?>&selected=B" class="option-btn">
                        <?php echo htmlspecialchars($current_q['option_b']); ?>
                    </a>
                    <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question; ?>&selected=C" class="option-btn">
                        <?php echo htmlspecialchars($current_q['option_c']); ?>
                    </a>
                    <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question; ?>&selected=D" class="option-btn">
                        <?php echo htmlspecialchars($current_q['option_d']); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="options-container">
                    <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <?php 
                            $option_class = '';
                            if ($option === $correct_answer) {
                                $option_class = 'correct';
                            } else if ($option === $selected_option && $option !== $correct_answer) {
                                $option_class = 'incorrect';
                            }
                        ?>
                        <div class="option-btn <?php echo $option_class; ?>">
                            <?php echo htmlspecialchars(getOptionText($current_q, $option)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question + 1; ?>" class="next-btn">
                    Next Question
                </a>
                <?php endif; ?>
            </div>
            
            <?php if ($selected_option || $current_question > 0): ?>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 