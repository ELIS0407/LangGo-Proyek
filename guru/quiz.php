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

$quizzes = [];
if ($user_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT q.id, q.title, q.description, q.class_level, q.code, q.is_active, q.created_at,
            (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count,
            (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) as attempt_count,
            (SELECT AVG(score) FROM quiz_attempts WHERE quiz_id = q.id) as avg_score
        FROM quizzes q
        WHERE q.created_by = ?
        ORDER BY q.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $quizzes[] = $row;
    }
}

if (isset($_POST['toggle_status']) && isset($_POST['quiz_id'])) {
    $quiz_id = $_POST['quiz_id'];
    
    $stmt = mysqli_prepare($conn, "SELECT is_active FROM quizzes WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $new_status = $row['is_active'] ? 0 : 1;
        
        $stmt = mysqli_prepare($conn, "UPDATE quizzes SET is_active = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $quiz_id);
        mysqli_stmt_execute($stmt);
        
        header("Location: quiz.php");
        exit;
    }
}

if (isset($_POST['delete_quiz']) && isset($_POST['quiz_id'])) {
    $quiz_id = $_POST['quiz_id'];
    
    $stmt = mysqli_prepare($conn, "SELECT id FROM quizzes WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM quizzes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $quiz_id);
        mysqli_stmt_execute($stmt);
        
        header("Location: quiz.php");
        exit;
    }
}

function generateQuizCode() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_quiz'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $class_level = $_POST['class_level'];
    
    if (empty($title)) {
        $error_message = "Quiz title is required.";
    } else {
        $code = generateQuizCode();
        $stmt = mysqli_prepare($conn, "SELECT id FROM quizzes WHERE code = ?");
        mysqli_stmt_bind_param($stmt, "s", $code);
        
        while (true) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 0) {
                break;
            }
            $code = generateQuizCode();
        }
        
        $stmt = mysqli_prepare($conn, "INSERT INTO quizzes (title, description, class_level, created_by, code) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $title, $description, $class_level, $user_id, $code);
        
        if (mysqli_stmt_execute($stmt)) {
            $quiz_id = mysqli_insert_id($conn);
            $success_message = "Quiz created successfully! You can now add questions.";
            
            header("Location: edit_quiz.php?id=" . $quiz_id);
            exit;
        } else {
            $error_message = "Error creating quiz: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management - LangGo!</title>
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
        
        .quiz-container {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .create-quiz-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            color: #3f6791;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .create-btn {
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
        
        .create-btn:hover {
            background-color: #2c4b6a;
        }
        
        .quizzes-list-section {
            margin-top: 20px;
        }
        
        .quizzes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .quizzes-table th, .quizzes-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .quizzes-table th {
            color: #3f6791;
            font-weight: 500;
            background-color: #f9f9f9;
        }
        
        .quiz-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s;
        }
        
        .edit-btn {
            color: #2196f3;
        }
        
        .view-btn {
            color: #4caf50;
        }
        
        .toggle-btn {
            color: #ff9800;
        }
        
        .delete-btn {
            color: #f44336;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .quiz-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .status-inactive {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .quiz-level {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .level-basic {
            background-color: #e3f2fd;
            color: #2196f3;
        }
        
        .level-intermediate {
            background-color: #fff8e1;
            color: #ff9800;
        }
        
        .level-advanced {
            background-color: #fce4ec;
            color: #e91e63;
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
        
        .error-message {
            color: #f44336;
            margin-top: 10px;
        }
        
        .success-message {
            color: #4caf50;
            margin-top: 10px;
        }
        
        .divider {
            width: 100%;
            height: 1px;
            background-color: #eee;
            margin: 30px 0;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .stat-title {
            font-size: 14px;
            color: #666;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #3f6791;
        }
        
        .stat-icon {
            font-size: 24px;
            color: #3f6791;
            margin-bottom: 5px;
        }
        
        .confirmation-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 100;
            justify-content: center;
            align-items: center;
        }
        
        .dialog-content {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        .dialog-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .dialog-message {
            color: #666;
            margin-bottom: 20px;
        }
        
        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .dialog-btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .cancel-btn {
            background-color: #f5f5f5;
            color: #333;
            border: none;
        }
        
        .confirm-btn {
            background-color: #f44336;
            color: white;
            border: none;
        }
        
        @media (max-width: 768px) {
            .quiz-form {
                grid-template-columns: 1fr;
            }
            
            .quizzes-table thead {
                display: none;
            }
            
            .quizzes-table, .quizzes-table tbody, .quizzes-table tr, .quizzes-table td {
                display: block;
                width: 100%;
            }
            
            .quizzes-table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 10px;
            }
            
            .quizzes-table td {
                text-align: right;
                padding: 10px;
                position: relative;
                border-bottom: 1px solid #eee;
            }
            
            .quizzes-table td:last-child {
                border-bottom: none;
            }
            
            .quizzes-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 50%;
                font-weight: 500;
                text-align: left;
                color: #3f6791;
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
                    <i class="fas fa-clipboard-list"></i>
                    Chat
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
                    <div class="user-level">Instruktur/Guru</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <h1 class="page-title">Quiz Management</h1>
            
            <div class="quiz-container">
                <div class="create-quiz-section">
                    <h2 class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        Create New Quiz
                    </h2>
                    
                    <form class="quiz-form" method="post" action="">
                        <div class="form-group">
                            <label class="form-label" for="title">Quiz Title</label>
                            <input type="text" id="title" name="title" class="form-input" placeholder="Enter quiz title" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="class_level">Class Level</label>
                            <select id="class_level" name="class_level" class="form-select" required>
                                <option value="basic">Basic</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description" class="form-input form-textarea" placeholder="Enter quiz description"></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="submit" name="create_quiz" class="create-btn">
                                <i class="fas fa-plus"></i>
                                Create Quiz
                            </button>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="error-message"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="success-message"><?php echo $success_message; ?></div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="divider"></div>
                
                <div class="quizzes-list-section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i>
                        Your Quizzes
                    </h2>
                    
                    <?php if (count($quizzes) > 0): ?>
                        <div class="stats-container">
                            <div class="stat-card">
                                <i class="fas fa-clipboard-list stat-icon"></i>
                                <div class="stat-title">Total Quizzes</div>
                                <div class="stat-value"><?php echo count($quizzes); ?></div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-users stat-icon"></i>
                                <div class="stat-title">Total Attempts</div>
                                <div class="stat-value">
                                    <?php
                                    $total_attempts = 0;
                                    foreach ($quizzes as $quiz) {
                                        $total_attempts += $quiz['attempt_count'];
                                    }
                                    echo $total_attempts;
                                    ?>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-check-circle stat-icon"></i>
                                <div class="stat-title">Active Quizzes</div>
                                <div class="stat-value">
                                    <?php
                                    $active_quizzes = 0;
                                    foreach ($quizzes as $quiz) {
                                        if ($quiz['is_active']) {
                                            $active_quizzes++;
                                        }
                                    }
                                    echo $active_quizzes;
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <table class="quizzes-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Level</th>
                                    <th>Code</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Avg. Score</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $quiz): ?>
                                    <tr>
                                        <td data-label="Title"><?php echo htmlspecialchars($quiz['title']); ?></td>
                                        <td data-label="Level">
                                            <span class="quiz-level level-<?php echo $quiz['class_level']; ?>">
                                                <?php echo ucfirst(htmlspecialchars($quiz['class_level'])); ?>
                                            </span>
                                        </td>
                                        <td data-label="Code"><?php echo htmlspecialchars($quiz['code']); ?></td>
                                        <td data-label="Questions"><?php echo $quiz['question_count']; ?></td>
                                        <td data-label="Attempts"><?php echo $quiz['attempt_count']; ?></td>
                                        <td data-label="Avg. Score"><?php echo $quiz['avg_score'] ? number_format($quiz['avg_score'], 1) : '-'; ?></td>
                                        <td data-label="Status">
                                            <?php if ($quiz['is_active']): ?>
                                                <span class="quiz-status status-active">Active</span>
                                            <?php else: ?>
                                                <span class="quiz-status status-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="quiz-actions">
                                                <a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="action-btn edit-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($quiz['attempt_count'] > 0): ?>
                                                <a href="view_results.php?id=<?php echo $quiz['id']; ?>" class="action-btn view-btn" title="View Results">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <form method="post" action="" style="display: inline;">
                                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                    <button type="submit" name="toggle_status" class="action-btn toggle-btn" title="<?php echo $quiz['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas <?php echo $quiz['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <button class="action-btn delete-btn" title="Delete" 
                                                        onclick="confirmDelete(<?php echo $quiz['id']; ?>, '<?php echo htmlspecialchars($quiz['title'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list empty-icon"></i>
                            <p>You haven't created any quizzes yet.</p>
                            <p>Create your first quiz using the form above!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="confirmation-dialog" id="deleteDialog">
        <div class="dialog-content">
            <h3 class="dialog-title">Delete Quiz</h3>
            <p class="dialog-message">Are you sure you want to delete the quiz "<span id="quizTitle"></span>"? This action cannot be undone.</p>
            <div class="dialog-buttons">
                <button class="dialog-btn cancel-btn" onclick="closeDialog()">Cancel</button>
                <form method="post" action="" id="deleteForm">
                    <input type="hidden" name="quiz_id" id="deleteQuizId">
                    <button type="submit" name="delete_quiz" class="dialog-btn confirm-btn">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function confirmDelete(quizId, quizTitle) {
            document.getElementById('deleteQuizId').value = quizId;
            document.getElementById('quizTitle').textContent = quizTitle;
            document.getElementById('deleteDialog').style.display = 'flex';
        }
        
        function closeDialog() {
            document.getElementById('deleteDialog').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const dialog = document.getElementById('deleteDialog');
            if (event.target === dialog) {
                closeDialog();
            }
        }
    </script>
</body>
</html> 