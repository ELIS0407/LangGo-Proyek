<?php
session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Get the level number from URL parameter
$level_number = isset($_GET['level']) ? intval($_GET['level']) : 1;

// Validate level number
if ($level_number < 1 || $level_number > 10) {
    $level_number = 1;
}

// Check if this level is accessible (Level 1 is always accessible)
if ($level_number > 1) {
    // Check if previous level is completed
    $check_query = "SELECT completed FROM user_progress 
                   WHERE user_id = $userId AND class_level = 'basic' AND level_number = " . ($level_number - 1);
    $result = mysqli_query($conn, $check_query);
    $previous_completed = false;
    
    if ($row = mysqli_fetch_assoc($result)) {
        $previous_completed = $row['completed'];
    }
    
    if (!$previous_completed) {
        // Redirect to the previous level
        header("Location: basic_levels.php?level=" . ($level_number - 1));
        exit;
    }
}

// Define questions and answers for each level
$levels = [
    1 => [
        'image' => '../assets/img/sandwich.jpeg',
        'question' => 'How much is that __________ ?',
        'correct_answer' => 'Sandwich',
        'options' => ['Party', 'City', 'Street', 'Sandwich', 'Egg'],
        'audio_text' => 'How much is that sandwich?'
    ],
    2 => [
        'image' => 'https://img.freepik.com/free-vector/red-apple-isolated-white-background_1308-81441.jpg',
        'question' => 'I like to eat __________ every day.',
        'correct_answer' => 'Apple',
        'options' => ['Apple', 'Book', 'Car', 'House', 'Pen'],
        'audio_text' => 'I like to eat apple every day'
    ],
    3 => [
        'image' => 'https://img.freepik.com/free-vector/stack-books-graphic-illustration_53876-8852.jpg',
        'question' => 'She reads a __________ in the library.',
        'correct_answer' => 'Book',
        'options' => ['Car', 'Book', 'Dog', 'Fish', 'Cat'],
        'audio_text' => 'She reads a book in the library'
    ],
    4 => [
        'image' => 'https://img.freepik.com/free-vector/sticker-template-cat-cartoon-character_1308-67896.jpg',
        'question' => 'The __________ is sleeping on the sofa.',
        'correct_answer' => 'Cat',
        'options' => ['Dog', 'Bird', 'Cat', 'Mouse', 'Horse'],
        'audio_text' => 'The cat is sleeping on the sofa',
    ],
    5 => [
        'image' => 'https://img.freepik.com/free-vector/school-building-educational-institution-college_107791-1051.jpg',
        'question' => 'We study at __________ every morning.',
        'correct_answer' => 'School',
        'options' => ['Home', 'Park', 'School', 'Market', 'Hospital'],
        'audio_text' => 'We study at school every morning',
    ],
    6 => [
        'image' => 'https://img.freepik.com/free-vector/english-teacher-concept-illustration_114360-7477.jpg',
        'question' => 'Hello, my name __________ Frans.',
        'correct_answer' => 'is',
        'options' => ['am', 'are', 'is', 'be', 'was'],
        'audio_text' => 'Hello, my name is Frans',
    ],
    7 => [
        'image' => 'https://img.freepik.com/free-vector/teacher-concept-illustration_114360-2166.jpg',
        'question' => 'She __________ a teacher.',
        'correct_answer' => 'is',
        'options' => ['am', 'are', 'is', 'be', 'was'],
        'audio_text' => 'She is a teacher',
    ],
    8 => [
        'image' => 'https://img.freepik.com/free-vector/group-students-school_24877-51462.jpg',
        'question' => 'They __________ babies.',
        'correct_answer' => 'are',
        'options' => ['am', 'are', 'is', 'be', 'was'],
        'audio_text' => 'They are babies',
    ],
    9 => [
        'image' => 'https://img.freepik.com/free-vector/doctor-character-background_1270-84.jpg',
        'question' => 'I __________ a doctor.',
        'correct_answer' => 'am',
        'options' => ['am', 'are', 'is', 'be', 'was'],
        'audio_text' => 'I am a doctor',
    ],
    10 => [
        'image' => 'https://img.freepik.com/free-vector/family-concept-illustration_114360-2047.jpg',
        'question' => 'We __________ a family.',
        'correct_answer' => 'are',
        'options' => ['am', 'are', 'is', 'be', 'was'],
        'audio_text' => 'We are a family',
    ]
];

// Get current level data
$current_level = $levels[$level_number];

// Process form submission
$message = '';
$correct_answer = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer'])) {
    $selected_answer = $_POST['answer'];
    
            if ($selected_answer === $current_level['correct_answer']) {
            $correct_answer = true;
            $message = "Correct! Great job!";
        $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                        VALUES ($userId, 'basic', $level_number, TRUE, NOW())
                        ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
        
        mysqli_query($conn, $update_query);
        

        include_once "../user_activity_tracker.php";
        track_user_activity($userId, 'level_complete', $conn);
    } else {
        $message = "Incorrect. Try again!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level <?php echo $level_number; ?> - Basic Class - LangGo!</title>
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
        
        /* Audio button styles */
        .audio-button {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 16px;
            color: #333;
        }
        
        .audio-button i {
            font-size: 24px;
            color: #3f6791;
            margin-right: 10px;
            cursor: pointer;
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
            text-decoration: none;
        }
        
        .next-button.show {
            display: block;
        }
        
        /* Style for the answer display */
        .answer-display {
            display: inline-block;
            padding: 5px 15px;
            background-color: #f0f0f0;
            border-radius: 20px;
            font-weight: 500;
            margin: 0 5px;
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
        
        <a href="level_content.php?level=basic" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="main-content">
            <h1 class="level-title">Basic - Level <?php echo $level_number; ?></h1>
            
            <div class="instruction">
                <i class="fas fa-info-circle"></i> Lengkapilah kalimat berikut!!
            </div>
            
            <div class="question-container">
                <img src="<?php echo $current_level['image']; ?>" alt="Question Image" class="question-image">
                
                <?php if (isset($current_level['has_audio']) && $current_level['has_audio'] && !$correct_answer): ?>

                <div class="audio-button">
                    <i class="fas fa-volume-up" onclick="playAudio('<?php echo htmlspecialchars($current_level['audio_text']); ?>')"></i>
                    <?php echo isset($current_level['audio_instruction']) ? $current_level['audio_instruction'] : $current_level['audio_text']; ?>
                </div>
                <?php endif; ?>
                
                <div class="question-text">
                    <?php 
                    // Split the question at the blank space and output with blank line
                    $question_parts = explode('__________', $current_level['question']);
                    
                    if ($correct_answer) {
                        // If answer is correct, show the answer in the blank
                        echo $question_parts[0] . '<span class="answer-display">' . $current_level['correct_answer'] . '</span>' . $question_parts[1];
                    } else {
                        // Otherwise show the blank line
                        echo $question_parts[0] . '<span class="answer-blank"></span>' . $question_parts[1];
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
            
            <?php if ($correct_answer): ?>
                <?php if ($level_number < 10): ?>
                    <a href="basic_levels.php?level=<?php echo $level_number + 1; ?>" class="continue-button">
                        Lanjutkan
                    </a>
                <?php elseif ($level_number == 10): ?>
                    <div class="result-message correct" style="margin-bottom: 20px;">
                        <i class="fas fa-unlock"></i> Selamat! Anda telah membuka Level Intermediate!
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="level_content.php?level=basic" class="continue-button">
                            Kembali ke Level Basic
                        </a>
                        <a href="level_content.php?level=intermediate" class="continue-button" style="background-color: #28a745;">
                            Mulai Level Intermediate
                        </a>
                    </div>
                <?php else: ?>
                    <a href="level_content.php?level=basic" class="continue-button">
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
        
        // Function to play audio using the Web Speech API
        function playAudio(text) {
            // Create a speech synthesis utterance
            const utterance = new SpeechSynthesisUtterance(text);
            
            // Set properties
            utterance.lang = 'en-US'; 
            utterance.rate = 0.9; // Slightly slower rate
            utterance.pitch = 1; // Normal pitch
            
            // Speak the text
            window.speechSynthesis.speak(utterance);
        }
        // Auto play audio if correct answer
        function autoPlayAudioIfCorrect() {
            const correct = <?php echo json_encode($correct_answer); ?>;
            if (correct) {
                const text = <?php echo json_encode($current_level['audio_text']); ?>;
                playAudio(text);
            }
        }

        // Jalankan setelah halaman dimuat
        window.onload = autoPlayAudioIfCorrect;

    </script>
</body>
</html> 