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

$feedback = "";
$feedback_icon = "";
$feedback_color = "";

if ($score >= 80) {
    $feedback = "Excellent! You've mastered this topic.";
    $feedback_icon = "fas fa-trophy";
    $feedback_color = "#4caf50";
} elseif ($score >= 60) {
    $feedback = "Good job! You've done well.";
    $feedback_icon = "fas fa-thumbs-up";
    $feedback_color = "#2196f3";
} elseif ($score >= 40) {
    $feedback = "Not bad. Keep practicing to improve.";
    $feedback_icon = "fas fa-book";
    $feedback_color = "#ff9800";
} else {
    $feedback = "You need more practice. Try again after reviewing the material.";
    $feedback_icon = "fas fa-redo";
    $feedback_color = "#f44336";
}
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
            justify-content: center;
            align-items: center;
        }
        
        .result-container {
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .result-header {
            margin-bottom: 30px;
        }
        
        .quiz-title {
            font-size: 24px;
            color: #3f6791;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .congrats-text {
            font-size: 18px;
            color: #666;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #3f6791;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin: 30px auto;
        }
        
        .score-value {
            font-size: 48px;
            font-weight: 700;
        }
        
        .score-label {
            font-size: 16px;
            opacity: 0.8;
        }
        
        .feedback {
            margin: 30px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            color: <?php echo $feedback_color; ?>;
        }
        
        .feedback-icon {
            font-size: 30px;
            margin-right: 15px;
        }
        
        .feedback-text {
            font-size: 18px;
            text-align: left;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f44336;
            opacity: 0;
            animation: confetti 5s ease-in-out infinite;
        }
        
        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
        
        @media (max-width: 768px) {
            .result-container {
                padding: 30px 20px;
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
                <img src="../assets/img/Logo-LangGo.png" alt="LangGo Logo">
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
            <div class="result-container">
                <div class="result-header">
                    <h1 class="quiz-title"><?php echo htmlspecialchars($quiz_title); ?></h1>
                    <p class="congrats-text">Quiz completed!</p>
                </div>
                
                <div class="score-circle">
                    <div class="score-value"><?php echo $score; ?></div>
                    <div class="score-label">points</div>
                </div>
                
                <div class="feedback">
                    <i class="<?php echo $feedback_icon; ?> feedback-icon"></i>
                    <div class="feedback-text"><?php echo $feedback; ?></div>
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
    </div>
    
    <?php if ($score >= 80): ?>
    <script>
        function createConfetti() {
            const colors = ['#f44336', '#2196f3', '#ffeb3b', '#4caf50', '#9c27b0', '#ff9800'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                document.body.appendChild(confetti);
            }
        }
        
        window.onload = createConfetti;
    </script>
    <?php endif; ?>
</body>
</html> 