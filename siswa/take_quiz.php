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
    
    $stmt = mysqli_prepare($conn, "SELECT id, question, option_a, option_b, option_c, option_d FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    
    foreach ($answers as $question_id => $answer) {
        $stmt = mysqli_prepare($conn, "SELECT correct_answer FROM quiz_questions WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $question_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            if ($answer === $row['correct_answer']) {
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
        }
        
        .quiz-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .quiz-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .quiz-title {
            font-size: 24px;
            color: #3f6791;
            font-weight: 600;
        }
        
        .quiz-description {
            margin-top: 10px;
            color: #666;
        }
        
        .quiz-level {
            display: inline-block;
            background-color: #3f6791;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-top: 15px;
        }
        
        .quiz-form {
            margin-top: 20px;
        }
        
        .question-item {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .question-text {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .options-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option-item:hover {
            background-color: #f0f7ff;
            border-color: #3f6791;
        }
        
        .option-item input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .option-label {
            font-size: 16px;
            cursor: pointer;
            flex: 1;
        }
        
        .submit-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 30px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
            width: 100%;
        }
        
        .submit-btn:hover {
            background-color: #2c4b6a;
        }
        
        .timer-container {
            position: fixed;
            top: 100px;
            right: 40px;
            background-color: #3f6791;
            color: white;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .timer-label {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .timer {
            font-size: 24px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .timer-container {
                position: static;
                margin-bottom: 20px;
                width: 100%;
                max-width: 800px;
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
            <div class="timer-container">
                <div class="timer-label">Remaining Time</div>
                <div class="timer" id="timer">20:00</div>
            </div>
            
            <div class="quiz-container">
                <div class="quiz-header">
                    <h1 class="quiz-title"><?php echo htmlspecialchars($quiz_data['title']); ?></h1>
                    <p class="quiz-description"><?php echo htmlspecialchars($quiz_data['description']); ?></p>
                    <div class="quiz-level"><?php echo ucfirst(htmlspecialchars($quiz_data['class_level'])); ?> Level</div>
                </div>
                
                <form class="quiz-form" method="post" action="" id="quiz-form">
                    <?php $question_number = 1; ?>
                    <?php foreach ($quiz_questions as $question): ?>
                        <div class="question-item">
                            <h3 class="question-text"><?php echo $question_number; ?>. <?php echo htmlspecialchars($question['question']); ?></h3>
                            <div class="options-list">
                                <label class="option-item">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" required>
                                    <span class="option-label">A. <?php echo htmlspecialchars($question['option_a']); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B">
                                    <span class="option-label">B. <?php echo htmlspecialchars($question['option_b']); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C">
                                    <span class="option-label">C. <?php echo htmlspecialchars($question['option_c']); ?></span>
                                </label>
                                <label class="option-item">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D">
                                    <span class="option-label">D. <?php echo htmlspecialchars($question['option_d']); ?></span>
                                </label>
                            </div>
                        </div>
                        <?php $question_number++; ?>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="submit_quiz" class="submit-btn">Submit Quiz</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Quiz timer
        let timeLeft = 20 * 60; // 20 minutes in seconds
        const timerElement = document.getElementById('timer');
        const quizForm = document.getElementById('quiz-form');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            timerElement.textContent = `${minutes}:${seconds}`;
            
            if (timeLeft === 0) {
                clearInterval(timerInterval);
                quizForm.submit();
            } else {
                timeLeft--;
            }
        }
        
        const timerInterval = setInterval(updateTimer, 1000);
        
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</body>
</html> 