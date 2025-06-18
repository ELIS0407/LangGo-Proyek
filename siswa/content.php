<?php
session_start();
include_once "../config.php";
include_once "user_lives.php"; // Include the lives system

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Initialize lives system
initialize_lives_system($conn);

// Reset all refill timers (temporary fix for the timer issue)
reset_all_refill_timers($conn);

// Reset current user's refill timer
reset_user_refill_timer($conn, $userId);

// Get user lives data
$lives_data = get_user_lives($conn, $userId);
$user_lives = $lives_data['lives'];
$minutes_until_refill = $lives_data['minutes_until_refill'];

// If needs refill, update in database
if ($lives_data['needs_refill']) {
    update_user_lives($conn, $userId, $user_lives, true);
}

// Get the class level and level number from URL parameters
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';
$level_number = isset($_GET['number']) ? intval($_GET['number']) : 1;

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

// Validate level number
if ($level_number < 1 || $level_number > 15) {
    $level_number = 1;
}

// Check if this level is accessible for the user
// For now, only level 1 is accessible
if ($level_number > 1) {
    header("Location: level_content.php?level={$class_level}");
    exit;
}

// Process form submission
$message = '';
$correct_answer = false;
$lost_life = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer'])) {
    $selected_answer = $_POST['answer'];
    
    // Check if the answer is correct (for level 1, the correct answer is "Sandwich")
    if ($selected_answer === "Sandwich") {
        $correct_answer = true;
        $message = "Correct! Great job!";
        
        // Update user progress in the database
        $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                        VALUES (?, ?, ?, TRUE, NOW())
                        ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "isi", $userId, $class_level, $level_number);
        mysqli_stmt_execute($stmt);
    } else {
        $message = "Incorrect. Try again!";
        
        // Decrease lives for wrong answer
        if ($user_lives > 0) {
            $user_lives = decrease_lives($conn, $userId);
            $lost_life = true;
        }
        
        // Refresh lives data
        $lives_data = get_user_lives($conn, $userId);
        $user_lives = $lives_data['lives'];
        $minutes_until_refill = $lives_data['minutes_until_refill'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level <?php echo $level_number; ?> - <?php echo ucfirst($class_level); ?> Class - LangGo!</title>
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
        
        .level-title {
            font-size: 28px;
            color: #3f6791;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .instruction {
            color: #3f6791;
            font-size: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .question-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 600px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .question-image {
            width: 200px;
            height: auto;
            margin-bottom: 30px;
        }
        
        .question-text {
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .answer-blank {
            width: 150px;
            height: 2px;
            background-color: #333;
            display: inline-block;
            margin: 0 5px;
        }
        
        .options-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            width: 100%;
            max-width: 600px;
        }
        
        .option {
            padding: 10px 20px;
            border: 2px solid #3f6791;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option:hover {
            background-color: #3f6791;
            color: white;
        }
        
        .message {
            margin-top: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
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
        
        .next-button {
            background-color: #3f6791;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            margin-top: 20px;
            display: none;
        }
        
        .next-button.show {
            display: block;
        }
        
        .lives-container {
            position: absolute;
            top: 100px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .lives-hearts {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .lives-hearts i {
            font-size: 24px;
        }
        
        .lives-hearts i.fas.fa-heart {
            color: #e74c3c;
        }
        
        .lives-hearts i.far.fa-heart {
            color: #95a5a6;
        }
        
        .refill-timer {
            font-size: 18px;
            color: #3f6791;
        }
        
        .lives-lost-animation {
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0% { transform: translate(1px, 1px) rotate(0deg); }
            10% { transform: translate(-1px, -2px) rotate(-1deg); }
            20% { transform: translate(-3px, 0px) rotate(1deg); }
            30% { transform: translate(3px, 2px) rotate(0deg); }
            40% { transform: translate(1px, -1px) rotate(1deg); }
            50% { transform: translate(-1px, 2px) rotate(-1deg); }
            60% { transform: translate(-3px, 1px) rotate(0deg); }
            70% { transform: translate(3px, 1px) rotate(-1deg); }
            80% { transform: translate(-1px, -1px) rotate(1deg); }
            90% { transform: translate(1px, 2px) rotate(0deg); }
            100% { transform: translate(1px, -1px) rotate(-1deg); }
        }
        
        @media (max-width: 768px) {
            .options-container {
                flex-direction: column;
                align-items: center;
            }
            
            .option {
                width: 80%;
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
        
        <a href="level_content.php?level=<?php echo $class_level; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <!-- Lives display -->
        <div class="lives-container <?php echo $lost_life ? 'lives-lost-animation' : ''; ?>">
            <div class="lives-hearts">
                <?php for ($i = 0; $i < $user_lives; $i++): ?>
                <i class="fas fa-heart"></i>
                <?php endfor; ?>
                
                <?php for ($i = $user_lives; $i < 5; $i++): ?>
                <i class="far fa-heart"></i>
                <?php endfor; ?>
            </div>
            <?php if ($user_lives < 5): ?>
            <div class="refill-timer">Refill in: <?php echo $minutes_until_refill; ?> min 00 sec</div>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <h1 class="level-title"><?php echo ucfirst($class_level); ?> - Level <?php echo $level_number; ?></h1>
            
            <div class="instruction">
                <i class="fas fa-info-circle"></i> Lengkapilah kalimat berikut!!
            </div>
            
            <div class="question-container">
                <img src="../assets/img/sandwich.png" alt="Sandwich" class="question-image">
                
                <div class="question-text">
                    How much is that <span class="answer-blank"></span> ?
                </div>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $correct_answer ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="" id="answerForm">
                <div class="options-container">
                    <button type="submit" name="answer" value="Party" class="option">Party</button>
                    <button type="submit" name="answer" value="City" class="option">City</button>
                    <button type="submit" name="answer" value="Street" class="option">Street</button>
                    <button type="submit" name="answer" value="Sandwich" class="option">Sandwich</button>
                    <button type="submit" name="answer" value="Egg" class="option">Egg</button>
                </div>
            </form>
            
            <?php if ($correct_answer): ?>
                <a href="level_content.php?level=<?php echo $class_level; ?>" class="next-button show">
                    Continue
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // If the answer is correct, disable the form
        <?php if ($correct_answer): ?>
        document.getElementById('answerForm').style.display = 'none';
        <?php endif; ?>

        // Lives refill timer
        <?php if ($user_lives < 5): ?>
        let minutesRemaining = <?php echo floor($minutes_until_refill); ?>;
        let secondsRemaining = <?php echo round(($minutes_until_refill - floor($minutes_until_refill)) * 60); ?>;
        
        function updateRefillTimer() {
            if (secondsRemaining <= 0) {
                secondsRemaining = 59;
                minutesRemaining--;
            } else {
                secondsRemaining--;
            }
            
            if (minutesRemaining <= 0 && secondsRemaining <= 0) {
                // Reload page to refresh lives
                location.reload();
            } else {
                document.querySelector('.refill-timer').textContent = `Refill in: ${minutesRemaining} min ${secondsRemaining.toString().padStart(2, '0')} sec`;
                setTimeout(updateRefillTimer, 1000);
            }
        }
        
        // Start the timer
        updateRefillTimer();
        <?php endif; ?>
    </script>
</body>
</html> 