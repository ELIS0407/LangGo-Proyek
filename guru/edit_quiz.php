<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'guru') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = null;

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
}

$is_new_quiz = !isset($_GET['id']);
$quiz_id = $is_new_quiz ? null : $_GET['id'];
$quiz_data = null;
$quiz_questions = [];
$page_title = $is_new_quiz ? "Create New Quiz" : "Edit Quiz";

if (!$is_new_quiz) {
    // Editing an existing quiz
    $stmt = mysqli_prepare($conn, "SELECT * FROM quizzes WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $quiz_data = $row;
        
        $stmt = mysqli_prepare($conn, "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $quiz_questions[] = $row;
        }
    } else {
        header("location: quiz.php");
        exit;
    }
} else {
    // Creating a new quiz
    $quiz_data = [
        'title' => '',
        'description' => '',
        'class_level' => 'basic',
        'code' => ''
    ];
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quiz'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $class_level = $_POST['class_level'];
    
    if (empty($title)) {
        $error_message = "Quiz title is required.";
    } else {
        if ($is_new_quiz) {
            // Generate a unique code for the quiz
            $code = generateQuizCode();
            
            $stmt = mysqli_prepare($conn, "INSERT INTO quizzes (title, description, class_level, code, created_by) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $class_level, $code, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $quiz_id = mysqli_insert_id($conn);
                $success_message = "Quiz created successfully!";
                
                // Redirect to edit the newly created quiz
                header("location: quiz.php?id=" . $quiz_id);
                exit;
            } else {
                $error_message = "Error creating quiz: " . mysqli_error($conn);
            }
        } else {
            // Update existing quiz
            $stmt = mysqli_prepare($conn, "UPDATE quizzes SET title = ?, description = ?, class_level = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $title, $description, $class_level, $quiz_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Quiz updated successfully!";
                
                $quiz_data['title'] = $title;
                $quiz_data['description'] = $description;
                $quiz_data['class_level'] = $class_level;
            } else {
                $error_message = "Error updating quiz: " . mysqli_error($conn);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $question = trim($_POST['question']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answers = isset($_POST['correct_answers']) ? $_POST['correct_answers'] : [];
    
    if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answers)) {
        $error_message = "All fields are required and at least one correct answer must be selected.";
    } else {
        $correct_answer_str = implode(',', $correct_answers);
        $stmt = mysqli_prepare($conn, "INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issssss", $quiz_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_answer_str);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Question added successfully!";
            
            $stmt = mysqli_prepare($conn, "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
            mysqli_stmt_bind_param($stmt, "i", $quiz_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $quiz_questions = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $quiz_questions[] = $row;
            }
        } else {
            $error_message = "Error adding question: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_question'])) {
    $question_id = $_POST['question_id'];
    
    $stmt = mysqli_prepare($conn, "DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $question_id, $quiz_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Question deleted successfully!";

        $stmt = mysqli_prepare($conn, "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $quiz_questions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $quiz_questions[] = $row;
        }
    } else {
        $error_message = "Error deleting question: " . mysqli_error($conn);
    }
}

// Function to generate a unique quiz code
function generateQuizCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - LangGo!</title>
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
            gap: 30px;
        }
        
        .page-title {
            font-size: 28px;
            color: #3f6791;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 18px;
            color: #666;
            text-align: center;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: #3f6791;
            text-decoration: none;
        }
        
        .breadcrumb .separator {
            color: #999;
        }
        
        .breadcrumb .current {
            color: #666;
        }
        
        .quiz-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            color: #3f6791;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quiz-info {
            margin-bottom: 30px;
        }
        
        .quiz-code {
            display: inline-block;
            padding: 8px 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            font-family: monospace;
            font-size: 16px;
            margin-top: 10px;
            color: #333;
        }
        
        .copy-code {
            background: none;
            border: none;
            color: #3f6791;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .quiz-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-size: 14px;
            color: #555;
        }
        
        .form-input, .form-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3f6791;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .submit-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .submit-btn:hover {
            background-color: #2c4b6a;
        }
        
        .divider {
            width: 100%;
            height: 1px;
            background-color: #eee;
            margin: 30px 0;
        }
        
        .questions-section {
            margin-top: 20px;
        }
        
        .questions-list {
            margin-top: 20px;
        }
        
        .question-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .question-number {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: #3f6791;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
        }
        
        .question-text {
            font-size: 18px;
            color: #333;
            margin-left: 40px;
            margin-bottom: 15px;
            padding-right: 30px;
        }
        
        .options-list {
            margin-left: 40px;
            margin-bottom: 10px;
        }
        
        .option-item {
            margin-bottom: 10px;
            padding: 10px 15px;
            background-color: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        
        .option-label {
            display: inline-block;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background-color: #eee;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .option-label.correct {
            background-color: #4caf50;
            color: white;
        }
        
        .question-actions {
            text-align: right;
        }
        
        .delete-question {
            background: none;
            border: none;
            color: #f44336;
            cursor: pointer;
            font-size: 16px;
        }
        
        .delete-question:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #f44336;
            margin-top: 10px;
        }
        
        .success-message {
            color: #4caf50;
            margin-top: 10px;
        }
        
        .add-question-form {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .quiz-form, .form-row {
                grid-template-columns: 1fr;
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    DASHBOARD
                </a>
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
                </a>
                <a href="quiz.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                    <div class="user-level">Teacher</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="breadcrumb">
                <a href="quiz.php">Quiz Management</a>
                <span class="separator">></span>
                <span class="current"><?php echo $page_title; ?></span>
            </div>
            
            <h1 class="page-title"><?php echo $page_title; ?></h1>
            <?php if (!$is_new_quiz): ?>
                <p class="page-subtitle"><?php echo htmlspecialchars($quiz_data['title']); ?></p>
            <?php endif; ?>
            
            <div class="quiz-container">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <div class="quiz-info">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Quiz Information
                    </h2>
                    
                    <?php if (!$is_new_quiz && !empty($quiz_data['code'])): ?>
                    <div>
                        <strong>Quiz Code:</strong>
                        <span class="quiz-code" id="quizCode"><?php echo htmlspecialchars($quiz_data['code']); ?></span>
                        <button class="copy-code" onclick="copyQuizCode()" title="Copy to clipboard">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <form class="quiz-form" method="post" action="" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="form-label" for="title">Quiz Title</label>
                            <input type="text" id="title" name="title" class="form-input" value="<?php echo htmlspecialchars($quiz_data['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="class_level">Class Level</label>
                            <select id="class_level" name="class_level" class="form-select" required>
                                <option value="basic" <?php echo $quiz_data['class_level'] === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                <option value="intermediate" <?php echo $quiz_data['class_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $quiz_data['class_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-input form-textarea"><?php echo htmlspecialchars($quiz_data['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_quiz" class="submit-btn">
                                <i class="fas fa-save"></i>
                                <?php echo $is_new_quiz ? 'Create Quiz' : 'Update Quiz'; ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (!$is_new_quiz): ?>
                <div class="divider"></div>
                
                <div class="questions-section">
                    <h2 class="section-title">
                        <i class="fas fa-question-circle"></i>
                        Questions
                    </h2>
                    
                    <div class="questions-list">
                        <?php if (count($quiz_questions) > 0): ?>
                            <?php $question_number = 1; ?>
                            <?php foreach ($quiz_questions as $question): ?>
                                <div class="question-card">
                                    <div class="question-number"><?php echo $question_number; ?></div>
                                    <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                                    <div class="options-list">
                                        <div class="option-item">
                                            <span class="option-label <?php echo in_array('A', explode(',', $question['correct_answer'])) ? 'correct' : ''; ?>">A</span>
                                            <?php echo htmlspecialchars($question['option_a']); ?>
                                        </div>
                                        <div class="option-item">
                                            <span class="option-label <?php echo in_array('B', explode(',', $question['correct_answer'])) ? 'correct' : ''; ?>">B</span>
                                            <?php echo htmlspecialchars($question['option_b']); ?>
                                        </div>
                                        <div class="option-item">
                                            <span class="option-label <?php echo in_array('C', explode(',', $question['correct_answer'])) ? 'correct' : ''; ?>">C</span>
                                            <?php echo htmlspecialchars($question['option_c']); ?>
                                        </div>
                                        <div class="option-item">
                                            <span class="option-label <?php echo in_array('D', explode(',', $question['correct_answer'])) ? 'correct' : ''; ?>">D</span>
                                            <?php echo htmlspecialchars($question['option_d']); ?>
                                        </div>
                                    </div>
                                    <div class="question-actions">
                                        <form method="post" action="" style="display: inline;">
                                            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                            <button type="submit" name="delete_question" class="delete-question" onclick="return confirm('Are you sure you want to delete this question?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php $question_number++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-question-circle empty-icon"></i>
                                <p>No questions yet.</p>
                                <p>Add questions using the form below!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="add-question-form">
                        <h3 class="section-title">
                            <i class="fas fa-plus-circle"></i>
                            Add New Question
                        </h3>
                        
                        <form method="post" action="">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label" for="question">Question</label>
                                <textarea id="question" name="question" class="form-input" required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="option_a">Option A</label>
                                    <input type="text" id="option_a" name="option_a" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="option_b">Option B</label>
                                    <input type="text" id="option_b" name="option_b" class="form-input" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="option_c">Option C</label>
                                    <input type="text" id="option_c" name="option_c" class="form-input" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="option_d">Option D</label>
                                    <input type="text" id="option_d" name="option_d" class="form-input" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Correct Answer(s)</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="correct_answers[]" value="A">
                                        A
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="correct_answers[]" value="B">
                                        B
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="correct_answers[]" value="C">
                                        C
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="correct_answers[]" value="D">
                                        D
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 20px;">
                                <button type="submit" name="add_question" class="submit-btn">
                                    <i class="fas fa-plus"></i>
                                    Add Question
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function copyQuizCode() {
            const codeElement = document.getElementById('quizCode');
            const textArea = document.createElement('textarea');
            textArea.value = codeElement.textContent;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            alert('Quiz code copied to clipboard!');
        }
    </script>
</body>
</html>