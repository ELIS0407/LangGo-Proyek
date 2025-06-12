<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
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

$error_message = '';
$quiz_code = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quiz_code'])) {
    $quiz_code = trim($_POST['quiz_code']);
    
    $stmt = mysqli_prepare($conn, "SELECT id, title, class_level FROM quizzes WHERE code = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, "s", $quiz_code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $quiz_id = $row['id'];
        
        $stmt = mysqli_prepare($conn, "SELECT id FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $quiz_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_message = "Anda sudah mengerjakan quiz ini sebelumnya.";
        } else {
            header("Location: take_quiz_new.php?code=" . $quiz_code);
            exit;
        }
    } else {
        $error_message = "Kode quiz tidak valid atau quiz tidak aktif.";
    }
}

$quiz_history = [];
if ($user_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT qa.score, q.title, q.class_level, qa.completed_at
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.user_id = ?
        ORDER BY qa.completed_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $quiz_history[] = $row;
    }
}

$total_score = 0;
if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT SUM(score) as total FROM quiz_attempts WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $total_score = $row['total'] ? $row['total'] : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - LangGo!</title>
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
            position: relative;
        }
        
        .quiz-code-box {
            background-color: #3f6791;
            border-radius: 10px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .quiz-form {
            width: 100%;
            display: flex;
            gap: 10px;
        }
        
        .quiz-input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .quiz-input:focus {
            outline: none;
        }
        
        .quiz-btn {
            background-color: #3f6791;
            color: white;
            border: 2px solid white;
            border-radius: 5px;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .quiz-btn:hover {
            background-color: #2c4b6a;
        }
        
        .error-message {
            color: #ff6b6b;
            margin-top: 15px;
            text-align: center;
        }
        
        .quiz-history {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .history-title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #3f6791;
            font-weight: 500;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .history-table th, .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .history-table th {
            font-weight: 500;
            color: #3f6791;
        }
        
        .total-score {
            margin-top: 20px;
            font-size: 18px;
            color: #3f6791;
            font-weight: 500;
        }
        
        .students-image {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 300px;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .quiz-form {
                flex-direction: column;
            }
            
            .students-image {
                width: 200px;
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
            <div class="quiz-code-box">
                <form class="quiz-form" method="post" action="quiz.php">
                    <input type="text" name="quiz_code" class="quiz-input" placeholder="Masukkan kode quiz" value="<?php echo htmlspecialchars($quiz_code); ?>" required>
                    <button type="submit" class="quiz-btn">Gabung</button>
                </form>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($quiz_history)): ?>
            <div class="quiz-history">
                <h2 class="history-title">Riwayat Quiz</h2>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Level</th>
                            <th>Score</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quiz_history as $history): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($history['title']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($history['class_level'])); ?></td>
                                <td><?php echo htmlspecialchars($history['score']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($history['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total-score">Total Score: <?php echo $total_score; ?></div>
            </div>
            <?php endif; ?>
            
            <img src="../assets/img/orang.png" alt="Students" class="students-image">
        </div>
    </div>
</body>
</html> 