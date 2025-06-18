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

// Check if this level is accessible (Level 1 is always accessible if advanced is unlocked)
if ($level_number > 1) {
    // Check if previous level is completed
    $check_query = "SELECT completed FROM user_progress 
                   WHERE user_id = $userId AND class_level = 'advanced' AND level_number = " . ($level_number - 1);
    $result = mysqli_query($conn, $check_query);
    $previous_completed = false;
    
    if ($row = mysqli_fetch_assoc($result)) {
        $previous_completed = $row['completed'];
    }
    
    if (!$previous_completed) {
        // Redirect to the previous level
        header("Location: advanced_levels.php?level=" . ($level_number - 1));
        exit;
    }
}

// Check if intermediate level 10 is completed (required for advanced access)
$intermediate_level10_query = "SELECT completed FROM user_progress 
                             WHERE user_id = $userId AND class_level = 'intermediate' AND level_number = 10";
$result = mysqli_query($conn, $intermediate_level10_query);
$intermediate_level10_completed = false;

if ($row = mysqli_fetch_assoc($result)) {
    $intermediate_level10_completed = $row['completed'];
}

if (!$intermediate_level10_completed) {
    // Redirect to intermediate level 10
    header("Location: intermediate_levels.php?level=10");
    exit;
}

// Define questions and answers for each level
$levels = [
    1 => [
        'title' => 'Dia sedang membaca apa?',
        'image' => 'https://img.freepik.com/free-vector/noodles-bowl-cartoon-vector-icon-illustration-food-object-icon-concept-isolated-premium-vector-flat-cartoon-style_138676-4006.jpg',
        'correct_answer' => 'book',
        'alternative_answers' => ['books', 'a book'],
        'type' => 'text',
        'speech_text' => 'book'
    ],
    2 => [
        'title' => 'Sebutkan buah yang ada pada gambar',
        'image' => 'https://img.freepik.com/free-vector/red-apple-isolated-white-background_1308-81441.jpg',
        'correct_answer' => 'apple',
        'alternative_answers' => ['an apple', 'the apple'],
        'type' => 'text',
        'speech_text' => 'apple'
    ],
    3 => [
        'title' => 'Sebutkan makanan yang ada pada gambar',
        'image' => 'https://img.freepik.com/free-vector/hamburger-with-meat-cheese_1308-30898.jpg',
        'correct_answer' => 'burger',
        'alternative_answers' => ['hamburger', 'a burger', 'a hamburger', 'the burger', 'the hamburger'],
        'type' => 'text',
        'speech_text' => 'burger'
    ],
    4 => [
        'title' => 'Sebutkan makanan yang ada pada gambar',
        'image' => 'https://img.freepik.com/free-vector/colorful-round-tasty-pizza_1284-10219.jpg',
        'correct_answer' => 'pizza',
        'alternative_answers' => ['a pizza', 'the pizza'],
        'type' => 'text',
        'speech_text' => 'pizza'
    ],
    5 => [
        'title' => 'Sebutkan hewan yang ada pada gambar',
        'image' => 'https://img.freepik.com/free-vector/milk-bottle-glass-cartoon-icon-illustration_138676-2683.jpg',
        'correct_answer' => 'panda',
        'alternative_answers' => ['a panda'],
        'type' => 'text',
        'speech_text' => 'panda'
    ],
    6 => [
        'title' => 'Dengarkan audio berikut dan susunlah kata menjadi kalimat yang benar',
        'audio_text' => 'The weather is very hot today',
        'words' => ['Cold', 'Very', 'Is', 'Weather', 'Hot', 'Today', 'The'],
        'correct_order' => ['The', 'Weather', 'Is', 'Very', 'Hot', 'Today'],
        'type' => 'audio',
        'speech_text' => 'the weather is very hot today'
    ],
    7 => [
        'title' => 'Dengarkan audio berikut dan susunlah kata menjadi kalimat yang benar',
        'audio_text' => 'My brother likes to play football',
        'words' => ['My', 'Play', 'Likes', 'To', 'Brother', 'Basketball', 'Football'],
        'correct_order' => ['My', 'Brother', 'Likes', 'To', 'Play', 'Football'],
        'type' => 'audio',
        'speech_text' => 'my brother likes to play football'
    ],
    8 => [
        'title' => 'Dengarkan audio berikut dan susunlah kata menjadi kalimat yang benar',
        'audio_text' => 'She goes to school by bus',
        'words' => ['She', 'To', 'Goes', 'School', 'Car', 'Bus', 'By', 'Train'],
        'correct_order' => ['She', 'Goes', 'To', 'School', 'By', 'Bus'],
        'type' => 'audio',
        'speech_text' => 'she goes to school by bus'
    ],
    9 => [
        'title' => 'Dengarkan audio berikut dan susunlah kata menjadi kalimat yang benar',
        'audio_text' => 'They are watching a movie tonight',
        'words' => ['They', 'Movie', 'Tonight', 'A', 'are', 'Watching', 'Tomorrow', 'Playing'],
        'correct_order' => ['They', 'Are', 'Watching', 'A', 'Movie', 'Tonight'],
        'type' => 'audio',
        'speech_text' => 'they are watching a movie tonight'
    ],
    10 => [
        'title' => 'Dengarkan audio berikut dan susunlah kata menjadi kalimat yang benar',
        'audio_text' => 'We should protect our environment',
        'words' => ['should', 'We', 'Destroy', 'Our', 'Environment', 'Protect', 'The'],
        'correct_order' => ['We', 'Should', 'Protect', 'Our', 'Environment'],
        'type' => 'audio',
        'speech_text' => 'we should protect our environment'
    ]
];

// Get current level data
$current_level = $levels[$level_number];

// Process form submission
$message = '';
$correct_answer = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($current_level['type'] == 'text' && isset($_POST['answer'])) {
        $selected_answer = strtolower(trim($_POST['answer']));
        
        // Check if the answer is correct for text input
        if ($selected_answer === $current_level['correct_answer'] || in_array($selected_answer, $current_level['alternative_answers'])) {
            $correct_answer = true;
            $message = "Luar biasa!";
            
            // Update user progress in the database
            $class_level = 'advanced';
            $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                            VALUES ($userId, '$class_level', $level_number, TRUE, NOW())
                            ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
            
            mysqli_query($conn, $update_query);
            
            // Include activity tracker and record activity
            include_once "../user_activity_tracker.php";
            track_user_activity($userId, 'level_complete', $conn);
        } else {
            $message = "Salah!";
        }
    } elseif ($current_level['type'] == 'audio' && isset($_POST['answer'])) {
        $selected_answer = $_POST['answer'];
        $user_answer_array = explode(',', $selected_answer);
        $correct_order = $current_level['correct_order'];
        
        // Check if the answer is correct for drag-and-drop
        if ($user_answer_array == $correct_order) {
            $correct_answer = true;
            $message = "Luar biasa!";
            
            // Update user progress in the database
            $class_level = 'advanced';
            $update_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                            VALUES ($userId, '$class_level', $level_number, TRUE, NOW())
                            ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW()";
            
            mysqli_query($conn, $update_query);
            
            // Include activity tracker and record activity
            include_once "../user_activity_tracker.php";
            track_user_activity($userId, 'level_complete', $conn);
        } else {
            $message = "Salah!";
            
        
        }
    } elseif (empty($_POST['answer'])) {
        $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level <?php echo $level_number; ?> - Advanced Class - LangGo!</title>
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
        
        .flashcard {
            border: 2px solid #3f6791;
            border-radius: 15px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .flashcard-close {
            position: absolute;
            top: 10px;
            left: 10px;
            color: #3f6791;
            font-size: 20px;
        }
        
        .message {
            margin-top: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
            width: 100%;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .answer-form {
            width: 100%;
            max-width: 400px;
            margin-top: 20px;
        }
        
        .answer-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #3f6791;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .submit-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
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
            text-decoration: none;
            display: none;
        }
        
        .next-button.show {
            display: block;
        }
        
        .completion-message {
            font-size: 20px;
            color: #3f6791;
            margin-top: 20px;
            text-align: center;
        }
        
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
        
        /* Audio exercise styles */
        .audio-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .audio-button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #3f6791;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f0f0f0;
            transition: all 0.3s;
        }
        
        .audio-button:hover {
            background-color: #e0e0e0;
        }
        
        .audio-button i {
            font-size: 40px;
        }
        
        .word-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            min-height: 60px;
            border: 2px dashed #3f6791;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .word-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .word-option {
            padding: 10px 20px;
            background-color: #f0f0f0;
            border-radius: 20px;
            cursor: move;
            user-select: none;
            font-weight: 500;
        }
        
        .word-item {
            padding: 10px 20px;
            background-color: #3f6791;
            color: white;
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }
        
        .answer-display {
            font-size: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        
        @keyframes heartbeat {
            0% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
            75% { transform: scale(1.2); }
            100% { transform: scale(1); }
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
        
        <a href="level_content.php?level=advanced" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        

        
        <div class="main-content">
            <h1 class="level-title">Advanced - Level <?php echo $level_number; ?></h1>
            
            <div class="instruction">
                <i class="fas fa-info-circle"></i> <?php echo $current_level['title']; ?>
            </div>
            
            <div class="question-container">
                <?php if ($current_level['type'] == 'text'): ?>
                <div class="flashcard">
                    <div class="flashcard-close">
                        <i class="fas fa-times"></i>
                    </div>
                    <img src="<?php echo $current_level['image']; ?>" alt="Question Image" class="question-image">
                </div>
                <?php elseif ($current_level['type'] == 'audio'): ?>
                <div class="audio-container">
                    <button type="button" class="audio-button" onclick="playAudio('<?php echo htmlspecialchars($current_level['audio_text']); ?>')">
                        <i class="fas fa-volume-up"></i>
                    </button>
                </div>
                
                <?php if ($correct_answer): ?>
                <div class="answer-display">
                    <?php echo implode(' ', $current_level['correct_order']); ?>
                </div>
                <?php else: ?>
                <div id="word-container" class="word-container">
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $correct_answer ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!$correct_answer): ?>
                    <?php if ($current_level['type'] == 'text'): ?>
                    <form method="post" action="" class="answer-form" id="answerForm">
                        <input type="text" name="answer" class="answer-input" placeholder="Type your answer here..." required>
                        <button type="submit" class="submit-btn">Submit</button>
                    </form>
                    <?php elseif ($current_level['type'] == 'audio'): ?>
                    <div class="word-options">
                        <?php foreach ($current_level['words'] as $word): ?>
                        <div class="word-option" draggable="true" ondragstart="drag(event)" data-word="<?php echo $word; ?>"><?php echo $word; ?></div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="post" action="" id="audioForm">
                        <input type="hidden" name="answer" id="answer-input">
                        <button type="submit" class="submit-btn" id="submit-btn" disabled>Submit</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($correct_answer): ?>
                <?php if ($level_number < 10): ?>
                    <a href="advanced_levels.php?level=<?php echo $level_number + 1; ?>" class="continue-button">
                        Lanjutkan
                    </a>
                <?php else: ?>
                    <div class="completion-message">
                        Selamat! Anda telah menyelesaikan Level 10 dari Advanced Class.
                    </div>
                    <a href="level_content.php?level=advanced" class="continue-button">
                        Kembali ke Daftar Level
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
    // Jika jawaban benar, sembunyikan form jawaban
    <?php if ($correct_answer): ?>
    if (document.getElementById('answerForm')) {
        document.getElementById('answerForm').style.display = 'none';
    }
    <?php endif; ?>

    // Fungsi umum untuk memutar audio dari teks
    function playAudio(text) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
    }

    // Jika jawaban benar, putar audio otomatis berdasarkan jenis pertanyaan
    <?php if ($correct_answer): ?>
        let speakText = "";

        <?php if ($current_level['type'] === 'text'): ?>
    speakText = <?php echo json_encode($current_level['speech_text']); ?>;

        <?php elseif ($current_level['type'] === 'audio'): ?>
            speakText = <?php echo json_encode(implode(" ", $current_level['correct_order'])); ?>;
        <?php endif; ?>

        // Mainkan audionya
        const utter = new SpeechSynthesisUtterance(speakText);
        utter.lang = "en-US";
        utter.rate = 0.9;
        utter.pitch = 1;
        window.speechSynthesis.speak(utter);
    <?php endif; ?>

    // Drag and drop untuk soal audio
    let selectedWords = [];

    function updateWordContainer() {
        const container = document.getElementById('word-container');
        if (!container) return;

        container.innerHTML = '';
        selectedWords.forEach((word, index) => {
            const wordElement = document.createElement('div');
            wordElement.className = 'word-item';
            wordElement.textContent = word;
            wordElement.onclick = function () {
                removeWord(index);
            };
            container.appendChild(wordElement);
        });

        document.getElementById('answer-input').value = selectedWords.join(',');
        document.getElementById('submit-btn').disabled = selectedWords.length === 0;
    }

    function addWord(word) {
        selectedWords.push(word);
        updateWordContainer();
    }

    function removeWord(index) {
        selectedWords.splice(index, 1);
        updateWordContainer();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const wordOptions = document.querySelectorAll('.word-option');
        wordOptions.forEach(option => {
            option.addEventListener('click', function () {
                addWord(this.getAttribute('data-word'));
                this.style.display = 'none';
            });
        });

        updateWordContainer();
    });
</script>


</body>
</html> 