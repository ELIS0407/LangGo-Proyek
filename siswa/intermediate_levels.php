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

// Get the level number from URL parameter
$level_number = isset($_GET['level']) ? intval($_GET['level']) : 1;

// Validate level number
if ($level_number < 1 || $level_number > 10) {
    $level_number = 1;
}

// Check if this level is accessible (Level 1 is always accessible if intermediate is unlocked)
if ($level_number > 1) {
    // Check if previous level is completed
    $check_query = "SELECT completed FROM user_progress 
                   WHERE user_id = $userId AND class_level = 'intermediate' AND level_number = " . ($level_number - 1);
    $result = mysqli_query($conn, $check_query);
    $previous_completed = false;
    
    if ($row = mysqli_fetch_assoc($result)) {
        $previous_completed = $row['completed'];
    }
    
    if (!$previous_completed) {
        // Redirect to the previous level
        header("Location: intermediate_levels.php?level=" . ($level_number - 1));
        exit;
    }
}

// Check if basic level 10 is completed (required for intermediate access)
$basic_level10_query = "SELECT completed FROM user_progress 
                      WHERE user_id = $userId AND class_level = 'basic' AND level_number = 10";
$result = mysqli_query($conn, $basic_level10_query);
$basic_level10_completed = false;

if ($row = mysqli_fetch_assoc($result)) {
    $basic_level10_completed = $row['completed'];
}

if (!$basic_level10_completed) {
    // Redirect to basic level 10
    header("Location: basic_levels.php?level=10");
    exit;
}

// Define questions and answers for each level
$levels = [
    1 => [
        'title' => 'Melengkapi kalimat 1',
        'context' => 'This jacket is yellow.',
        'question' => 'I like the yellow jacket.',
        'fill_question' => 'I like _____ hat',
        'correct_answer' => 'that',
        'options' => ['that', 'yellow'],
        'spoken_text' => 'I like that hat',
        'type' => 'fill'
    ],
    2 => [
        'title' => 'Melengkapi kalimat 2',
        'context' => 'The car is blue.',
        'question' => 'I want the blue car.',
        'fill_question' => 'I want _____ bike',
        'correct_answer' => 'this',
        'options' => ['blue', 'this'],
        'spoken_text' =>'I want this bike',
        'type' => 'fill'
    ],
    3 => [
        'title' => 'Melengkapi kalimat 3',
        'context' => 'The book is interesting.',
        'question' => 'I read the interesting book.',
        'fill_question' => 'I read _____ novel',
        'correct_answer' => 'the',
        'options' => ['interesting', 'the'],
        'spoken_text' =>'I read the novel',
        'type' => 'fill'
    ],
    4 => [
        'title' => 'Melengkapi kalimat 4',
        'context' => 'The food is delicious.',
        'question' => 'I eat the delicious food.',
        'fill_question' => 'I eat _____ cake',
        'correct_answer' => 'a',
        'options' => ['delicious', 'a'],
        'spoken_text' =>'I eat a cake',
        'type' => 'fill'
    ],
    5 => [
        'title' => 'Melengkapi kalimat 5',
        'context' => 'The house is big.',
        'question' => 'I live in the big house.',
        'fill_question' => 'I live in _____ apartment',
        'correct_answer' => 'an',
        'options' => ['an', 'big'],
        'spoken_text' =>'I live in an apartment',
        'type' => 'fill'
    ],
    6 => [
        'title' => 'Terjemahkan dan susunlah menjadi kalimat yang benar',
        'image' => '../assets/img/bahasa_inggris.jpg',
        'translation' => 'Saya sedang belajar bahasa Inggris',
        'words' => ['I', 'You', 'English', 'Learning', 'Am'],
        'correct_order' => ['I', 'Am', 'Learning', 'English'],
        'spoken_text' =>'I am learning english',
        'type' => 'drag'
    ],
    7 => [
        'title' => 'Terjemahkan dan susunlah menjadi kalimat yang benar',
        'image' => 'https://img.freepik.com/free-vector/teacher-concept-illustration_114360-2166.jpg',
        'translation' => 'Dia adalah seorang guru',
        'words' => ['She', 'Teacher', 'A', 'Is', 'The'],
        'correct_order' => ['She', 'Is', 'A', 'Teacher'],
        'spoken_text' =>'She is a teacher',
        'type' => 'drag'
    ],
    8 => [
        'title' => 'Terjemahkan dan susunlah menjadi kalimat yang benar',
        'image' => '../assets/img/bermain_game.jpeg',
        'translation' => 'Mereka sedang bermain game',
        'words' => ['They', 'Are', 'Games', 'Playing', 'The'],
        'correct_order' => ['They', 'Are', 'Playing', 'Games'],
        'spoken_text' =>'They are playing games',
        'type' => 'drag'
    ],
    9 => [
        'title' => 'Terjemahkan dan susunlah menjadi kalimat yang benar',
        'image' => 'https://img.freepik.com/free-vector/family-concept-illustration_114360-2047.jpg',
        'translation' => 'Kami tinggal di rumah besar',
        'words' => ['We', 'Live', 'House', 'Big', 'In', 'A'],
        'correct_order' => ['We', 'Live', 'In', 'A', 'Big', 'House'],
        'spoken_text' =>'we live in a big house',
        'type' => 'drag'
    ],
    10 => [
        'title' => 'Terjemahkan dan susunlah menjadi kalimat yang benar',
        'image' => 'https://img.freepik.com/free-vector/school-building-educational-institution-college_107791-1051.jpg',
        'translation' => 'Sekolah kami sangat besar',
        'words' => ['Our', 'School', 'Very', 'Is', 'Big', 'The'],
        'correct_order' => ['Our', 'School', 'Is', 'Very', 'Big'],
        'spoken_text' =>'our school is very big',
        'type' => 'drag'
    ]
];

// Get current level data
$current_level = $levels[$level_number];

// Process form submission
$message = '';
$correct_answer = false;
$lost_life = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($current_level['type'] == 'fill' && isset($_POST['answer'])) {
        $selected_answer = $_POST['answer'];
        

        if ($selected_answer === $current_level['correct_answer']) {
            $correct_answer = true;
            $message = "Correct! Great job!";
            
            // Update user progress in the database
            $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                            VALUES ($userId, 'intermediate', $level_number, TRUE, NOW())
                            ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
            
            mysqli_query($conn, $update_query);
            
            // Include activity tracker and record activity
            include_once "../user_activity_tracker.php";
            track_user_activity($userId, 'level_complete', $conn);
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
    } elseif ($current_level['type'] == 'drag' && isset($_POST['answer'])) {
        $selected_answer = $_POST['answer'];
        $user_answer_array = explode(',', $selected_answer);
        $correct_order = $current_level['correct_order'];
        
        // Check if the answer is correct for drag-and-drop
        if ($user_answer_array == $correct_order) {
            $correct_answer = true;
            $message = "Correct! Great job!";
            
            // Update user progress in the database
            $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                            VALUES ($userId, 'intermediate', $level_number, TRUE, NOW())
                            ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
            
            mysqli_query($conn, $update_query);
            
            // Include activity tracker and record activity
            include_once "../user_activity_tracker.php";
            track_user_activity($userId, 'level_complete', $conn);
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
    } elseif (empty($_POST['answer'])) {
        // Just refreshing the page after an incorrect answer
        $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level <?php echo $level_number; ?> - Intermediate Class - LangGo!</title>
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
        
        .context-box {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .example-box {
            width: 100%;
            border-top: 1px solid #ddd;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            padding: 15px;
            text-align: center;
        }
        
        .fill-box {
            width: 100%;
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .answer-blank {
            display: inline-block;
            width: 80px;
            height: 2px;
            background-color: #333;
            margin: 0 5px;
            vertical-align: middle;
        }
        
        .options-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
            max-width: 400px;
        }
        
        .option {
            padding: 10px 20px;
            border: 2px solid #3f6791;
            border-radius: 30px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            background-color: white;
            text-align: center;
        }
        
        .option:hover {
            background-color: #3f6791;
            color: white;
        }
        
        /* Result message styles */
        .result-message {
            display: flex;
            align-items: center;
            margin-top: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .result-message i {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .result-message.correct {
            background-color: #d4edda;
            color: #155724;
        }
        
        .result-message.incorrect {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .result-message .correct-answer {
            margin-left: 5px;
            font-weight: 600;
        }
        
        /* Continue button */
        .continue-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
        }
        
        /* OK button */
        .ok-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
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
        
        /* Drag and Drop Styles */
        .drag-container {
            width: 100%;
            max-width: 600px;
        }
        
        .drag-image-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .drag-image {
            width: 150px;
            height: auto;
            border-radius: 5px;
        }
        
        .speech-bubble {
            position: relative;
            background: #ffffff;
            border-radius: 10px;
            padding: 15px;
            width: 60%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .speech-bubble:after {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 0;
            height: 0;
            border: 10px solid transparent;
            border-right-color: #ffffff;
            border-left: 0;
            margin-top: -10px;
            margin-left: -10px;
        }
        
        .answer-area {
            min-height: 60px;
            border: 2px dashed #3f6791;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        
        .answer-line {
            width: 90%;
            height: 2px;
            background-color: #ccc;
        }
        
        .words-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .word-box {
            padding: 8px 15px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: grab;
            user-select: none;
            font-size: 16px;
        }
        
        .word-box.dragging {
            opacity: 0.5;
        }
        
        .word-box.placed {
            background-color: #e6f7ff;
            border: 1px solid #91d5ff;
            margin: 0 5px;
        }
        
        .check-button {
            background-color: #3f6791;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .options-container {
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
        
        <a href="level_content.php?level=intermediate" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        
        <div class="main-content">
            <h1 class="level-title"><?php echo $current_level['title']; ?></h1>
            
            <div class="instruction">
                <i class="fas fa-info-circle"></i> 
                <?php if ($current_level['type'] == 'fill'): ?>
                    Lengkapilah kalimat berikut!!
                <?php else: ?>
                    Terjemahkan dan susunlah menjadi kalimat yang benar
                <?php endif; ?>
            </div>
            
            <?php if ($current_level['type'] == 'fill'): ?>
            <!-- Fill-in-the-blank question type -->
            <div class="question-container">
                <div class="context-box">
                    <?php echo $current_level['context']; ?>
                </div>
                
                <div class="example-box">
                    <?php echo $current_level['question']; ?>
                </div>
                
                <div class="fill-box">
                    <?php 
                    // If answer is correct, show it in the blank
                    if ($correct_answer) {
                        $fill_parts = explode('_____', $current_level['fill_question']);
                        echo $fill_parts[0] . '<strong>' . $current_level['correct_answer'] . '</strong>' . $fill_parts[1];
                    } else {
                        $fill_parts = explode('_____', $current_level['fill_question']);
                        echo $fill_parts[0] . '<span class="answer-blank"></span>' . $fill_parts[1];
                    }
                    ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="result-message <?php echo $correct_answer ? 'correct' : 'incorrect'; ?>">
                        <?php if ($correct_answer): ?>
                            <i class="fas fa-check-circle"></i> Luar biasa!
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Salah! 
                            <span class="correct-answer">Jawaban yang benar: <?php echo $current_level['correct_answer']; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="" id="answerForm">
                <div class="options-container">
                    <?php foreach ($current_level['options'] as $option): ?>
                        <button type="submit" name="answer" value="<?php echo $option; ?>" class="option"><?php echo $option; ?></button>
                    <?php endforeach; ?>
                </div>
            </form>
            
            <?php else: ?>
            <!-- Drag-and-drop question type -->
            <div class="question-container drag-container">
                <div class="drag-image-container">
                    <img src="<?php echo $current_level['image']; ?>" alt="Question Image" class="drag-image">
                    <div class="speech-bubble">
                        <?php echo $current_level['translation']; ?>
                    </div>
                </div>
                
                <div class="answer-area" id="answerArea">
                    <?php if ($correct_answer): ?>
                        <?php foreach ($current_level['correct_order'] as $word): ?>
                            <div class="word-box placed"><?php echo $word; ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="answer-line"></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($message): ?>
                    <div class="result-message <?php echo $correct_answer ? 'correct' : 'incorrect'; ?>">
                        <?php if ($correct_answer): ?>
                            <i class="fas fa-check-circle"></i> Luar biasa!
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Salah! 
                            <span class="correct-answer">Jawaban yang benar: <?php echo implode(' ', $current_level['correct_order']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="post" action="" id="answerForm" class="drag-form">
                <input type="hidden" name="answer" id="answerInput">
                
                <div class="words-container" id="wordsContainer">
                    <?php if (!$correct_answer): ?>
                        <?php foreach ($current_level['words'] as $word): ?>
                            <div class="word-box draggable" data-word="<?php echo $word; ?>"><?php echo $word; ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php if (!$correct_answer): ?>
                    <button type="button" id="checkButton" class="check-button">Periksa</button>
                <?php endif; ?>
            </form>
            <?php endif; ?>
            
            <?php if ($correct_answer): ?>
                <?php if ($level_number < 10): ?>
                    <a href="intermediate_levels.php?level=<?php echo $level_number + 1; ?>" class="continue-button">
                        Lanjutkan
                    </a>
                <?php else: ?>
                    <a href="level_content.php?level=intermediate" class="continue-button">
                        Selesai! Kembali ke Pilihan Level
                    </a>
                <?php endif; ?>
            <?php elseif ($message): ?>
                <form method="post" action="">
                    <button type="submit" class="ok-button">Oke</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // If the answer is correct, disable the form
        <?php if ($correct_answer): ?>
        document.getElementById('answerForm').style.display = 'none';
        <?php endif; ?>
        
        <?php if ($current_level['type'] == 'drag' && !$correct_answer): ?>
        // Drag and Drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const draggables = document.querySelectorAll('.draggable');
            const answerArea = document.getElementById('answerArea');
            const wordsContainer = document.getElementById('wordsContainer');
            const checkButton = document.getElementById('checkButton');
            const answerInput = document.getElementById('answerInput');
            
            let placedWords = [];
            
            // Add event listeners for draggable elements
            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', () => {
                    draggable.classList.add('dragging');
                });
                
                draggable.addEventListener('dragend', () => {
                    draggable.classList.remove('dragging');
                });
                
                // Make elements draggable
                draggable.setAttribute('draggable', 'true');
                
                // Add click event for mobile
                draggable.addEventListener('click', () => {
                    if (draggable.parentNode === wordsContainer) {
                        // Move to answer area
                        answerArea.appendChild(draggable);
                        draggable.classList.add('placed');
                        updatePlacedWords();
                    } else {
                        // Move back to words container
                        wordsContainer.appendChild(draggable);
                        draggable.classList.remove('placed');
                        updatePlacedWords();
                    }
                });
            });
            
            // Make answer area accept drops
            answerArea.addEventListener('dragover', e => {
                e.preventDefault();
            });
            
            answerArea.addEventListener('drop', e => {
                e.preventDefault();
                const draggable = document.querySelector('.dragging');
                if (draggable) {
                    answerArea.appendChild(draggable);
                    draggable.classList.add('placed');
                    updatePlacedWords();
                }
            });
            
            // Make words container accept drops to move back
            wordsContainer.addEventListener('dragover', e => {
                e.preventDefault();
            });
            
            wordsContainer.addEventListener('drop', e => {
                e.preventDefault();
                const draggable = document.querySelector('.dragging');
                if (draggable) {
                    wordsContainer.appendChild(draggable);
                    draggable.classList.remove('placed');
                    updatePlacedWords();
                }
            });
            
            // Update the hidden input with the current order of words
            function updatePlacedWords() {
                placedWords = [];
                const placedElements = answerArea.querySelectorAll('.word-box');
                placedElements.forEach(element => {
                    placedWords.push(element.dataset.word);
                });
                
                // Remove the answer line if words are placed
                const answerLine = answerArea.querySelector('.answer-line');
                if (answerLine) {
                    if (placedWords.length > 0) {
                        answerLine.style.display = 'none';
                    } else {
                        answerLine.style.display = 'block';
                    }
                }
            }
            
            // Check button event
            checkButton.addEventListener('click', () => {
                answerInput.value = placedWords.join(',');
                document.getElementById('answerForm').submit();
            });
        });
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
<script>
    function speak(text) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US'; // gunakan 'id-ID' jika ingin bahasa Indonesia
        utterance.rate = 0.95;
        utterance.pitch = 1;
        window.speechSynthesis.cancel(); // hentikan suara sebelumnya jika ada
        window.speechSynthesis.speak(utterance);
    }

    // Setelah submit dan jawaban benar, putar audio dari spoken_text
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $correct_answer && isset($current_level['spoken_text'])): ?>
        speak(<?php echo json_encode($current_level['spoken_text']); ?>);
    <?php endif; ?>
</script>

</html> 