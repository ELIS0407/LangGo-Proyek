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
$current_question = isset($_GET['q']) ? (int)$_GET['q'] : 1;
$selected_answers = isset($_GET['selected']) ? $_GET['selected'] : [];
if (!is_array($selected_answers)) {
    $selected_answers = [$selected_answers];
}
$is_correct = false;
$correct_answers = [];
$streak = isset($_SESSION['quiz_streak']) ? $_SESSION['quiz_streak'] : 0;
$correct_count = isset($_SESSION['correct_count']) ? $_SESSION['correct_count'] : 0;
$incorrect_count = isset($_SESSION['incorrect_count']) ? $_SESSION['incorrect_count'] : 0;
$show_result = isset($_GET['show_result']) ? true : false;

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
    
    $stmt = mysqli_prepare($conn, "SELECT id FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $quiz_data['id'], $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        header("Location: quiz.php");
        exit;
    }
    
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

if ($current_question > count($quiz_questions)) {
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

$current_q = $quiz_questions[$current_question - 1] ?? null;

if (!$current_q) {
    header("Location: quiz.php");
    exit;
}

$correct_answers = explode(',', $current_q['correct_answer']);

if ($show_result) {
    $is_correct = count(array_diff($correct_answers, $selected_answers)) === 0 && 
                  count(array_diff($selected_answers, $correct_answers)) === 0;
    
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    
    foreach ($answers as $question_id => $selected_answers) {
        $stmt = mysqli_prepare($conn, "SELECT correct_answer FROM quiz_questions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $correct_answers = explode(',', $row['correct_answer']);
            $selected_answers = is_array($selected_answers) ? $selected_answers : [$selected_answers];
            
            // Check if all correct answers are selected and no incorrect answers are selected
            $all_correct = count(array_diff($correct_answers, $selected_answers)) === 0;
            $no_incorrect = count(array_diff($selected_answers, $correct_answers)) === 0;
            
            if ($all_correct && $no_incorrect) {
                $score += 10;
            }
        }
    }
    
    $stmt = mysqli_prepare($conn, "INSERT INTO quiz_attempts (quiz_id, user_id, score) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iii", $quiz_data['id'], $user_id, $score);
    mysqli_stmt_execute($stmt);
    
    header("Location: quiz_result.php?score=" . $score . "&quiz=" . urlencode($quiz_data['title']));
    exit;
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
        
        .quiz-header {
            text-align: center;
        }
        
        .quiz-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .quiz-description {
            font-size: 16px;
            color: #666;
        }
        
        .quiz-level {
            font-size: 14px;
            color: #ccc;
            margin-top: 10px;
        }
        
        .timer-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .timer-label {
            font-size: 16px;
            color: #666;
        }
        
        .timer {
            font-size: 24px;
            font-weight: 700;
            margin-top: 5px;
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
            border: 2px solid #e0e0e0;
            background-color: #f9f9f9;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .option-btn:hover {
            background-color: #f0f7ff;
            border-color: #3f6791;
            transform: translateX(5px);
        }
        
        .option-btn.selected {
            background-color: #f0f7ff;
            border-color: #3f6791;
        }
        
        .option-btn.selected .option-label {
            color: #3f6791;
            font-weight: 500;
        }
        
        .option-btn input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin: 0;
            accent-color: #3f6791;
        }
        
        .option-btn .option-label {
            flex: 1;
            cursor: pointer;
            font-size: 16px;
            color: #444;
        }
        
        .option-btn.correct {
            background-color: #00c853;
            border-color: #00c853;
            color: white;
        }
        
        .option-btn.incorrect {
            background-color: #ff1744;
            border-color: #ff1744;
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
                <img src="../assets/img/Logo-LangGo.png" alt="LangGo Logo">
            </div>
            <div class="nav-menu">
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    Chat
                </a>
                <a href="quiz.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    Quiz
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    Profile
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                    <div class="user-level">Student</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="quiz-container">
                <div class="quiz-header">
                    <h1 class="quiz-title"><?php echo htmlspecialchars($quiz_data['title']); ?></h1>
                    <p class="quiz-description"><?php echo htmlspecialchars($quiz_data['description']); ?></p>
                    <div class="quiz-level"><?php echo ucfirst(htmlspecialchars($quiz_data['class_level'])); ?> Level</div>
                </div>
                
                <div class="question-container">
                    <div class="question-text">
                        <?php echo htmlspecialchars($current_q['question']); ?>
                        <?php 
                        if (count($correct_answers) > 1): 
                        ?>
                        <span class="answer-count-indicator">(<?php echo count($correct_answers); ?> jawaban benar)</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$show_result): ?>
                <form method="get" action="" id="answerForm">
                    <input type="hidden" name="code" value="<?php echo htmlspecialchars($quiz_code); ?>">
                    <input type="hidden" name="q" value="<?php echo $current_question; ?>">
                    <input type="hidden" name="show_result" value="1">
                    
                    <div class="options-container">
                        <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <label class="option-btn <?php echo in_array($option, $selected_answers) ? 'selected' : ''; ?>">
                            <input type="checkbox" name="selected[]" value="<?php echo $option; ?>" 
                                <?php echo in_array($option, $selected_answers) ? 'checked' : ''; ?>>
                            <span class="option-label"><?php echo htmlspecialchars(getOptionText($current_q, $option)); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="next-btn">
                        <i class="fas fa-check"></i>
                        Next Question
                    </button>
                </form>
                <?php else: ?>
                <div class="options-container">
                    <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <?php 
                            $option_class = '';
                            if (in_array($option, $correct_answers)) {
                                $option_class = 'correct';
                            } else if (in_array($option, $selected_answers) && !in_array($option, $correct_answers)) {
                                $option_class = 'incorrect';
                            }
                        ?>
                        <div class="option-btn <?php echo $option_class; ?>">
                            <?php echo htmlspecialchars(getOptionText($current_q, $option)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="?code=<?php echo $quiz_code; ?>&q=<?php echo $current_question + 1; ?>" class="next-btn">
                    <i class="fas fa-arrow-right"></i>
                    Next Question
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($selected_answers) || $current_question > 0): ?>
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
      
            document.querySelectorAll('.option-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
     
                    if (e.target.type === 'checkbox') return;
          
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    
                 
                    this.classList.toggle('selected', checkbox.checked);
                });
            });

            document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const optionBtn = this.closest('.option-btn');
                    optionBtn.classList.toggle('selected', this.checked);
                });
            });
        });

    </script>
</body>
</html>