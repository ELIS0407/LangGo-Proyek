<?php
session_start();
include_once "../config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Get the class level from URL parameter
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

// Debug: Print SQL query
// echo "DEBUG: Class level is $class_level<br>";

// Get top 10 students by completed levels for the selected class
$leaderboard_query = "SELECT u.id, u.username, u.profile_image, 
                      COUNT(CASE WHEN up.completed = 1 THEN 1 END) AS completed_levels,
                      COUNT(CASE WHEN up.completed = 1 THEN 1 END) * 5 AS score
                      FROM users u
                      LEFT JOIN user_progress up ON u.id = up.user_id AND up.class_level = '$class_level'
                      WHERE u.role = 'siswa'
                      GROUP BY u.id
                      ORDER BY score DESC, completed_levels DESC
                      LIMIT 10";

// Debug: Print SQL query
// echo "DEBUG: $leaderboard_query<br>";

$leaderboard_result = mysqli_query($conn, $leaderboard_query);

if (!$leaderboard_result) {
    die("Error in leaderboard query: " . mysqli_error($conn));
}

$leaderboard = [];
while ($row = mysqli_fetch_assoc($leaderboard_result)) {
    $leaderboard[] = $row;
}

// Calculate user's rank manually
$user_rank = 0;
$user_score = 0;
$user_completed = 0;

// Get all users' scores
$all_users_query = "SELECT u.id, u.username, 
                   COUNT(CASE WHEN up.completed = 1 THEN 1 END) AS completed_levels,
                   COUNT(CASE WHEN up.completed = 1 THEN 1 END) * 5 AS score
                   FROM users u
                   LEFT JOIN user_progress up ON u.id = up.user_id AND up.class_level = '$class_level'
                   WHERE u.role = 'siswa'
                   GROUP BY u.id
                   ORDER BY score DESC, completed_levels DESC";

$all_users_result = mysqli_query($conn, $all_users_query);

if (!$all_users_result) {
    die("Error in all users query: " . mysqli_error($conn));
}

$current_rank = 0;
$prev_score = -1;
$rank_counter = 0;

while ($row = mysqli_fetch_assoc($all_users_result)) {
    $rank_counter++;
    
    // If this is a new score, update the rank
    if ($row['score'] != $prev_score) {
        $current_rank = $rank_counter;
    }
    
    // If this is the current user, save their rank and score
    if ($row['id'] == $userId) {
        $user_rank = $current_rank;
        $user_score = $row['score'];
        $user_completed = $row['completed_levels'];
        break;
    }
    
    $prev_score = $row['score'];
}

// If user wasn't found in the results, they have no completed levels
if ($user_rank == 0) {
    $user_rank = "-";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - <?php echo ucfirst($class_level); ?> Class - LangGo!</title>
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
        
        .leaderboard-title {
            font-size: 28px;
            color: #3f6791;
            margin-bottom: 10px;
            font-weight: 500;
            text-align: center;
        }
        
        .leaderboard-subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .leaderboard-container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .leaderboard-header {
            background-color: #3f6791;
            color: white;
            padding: 15px 20px;
            font-size: 20px;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .leaderboard-list {
            padding: 10px;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        
        .leaderboard-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .rank-1 {
            background-color: #FFD700; /* Gold */
            color: #333;
        }
        
        .rank-2 {
            background-color: #C0C0C0; /* Silver */
            color: #333;
        }
        
        .rank-3 {
            background-color: #CD7F32; /* Bronze */
            color: #fff;
        }
        
        .default-rank {
            background-color: #3f6791;
            color: white;
        }
        
        .leaderboard-user {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-size: 16px;
            font-weight: 500;
        }
        
        .leaderboard-score {
            font-size: 20px;
            font-weight: 600;
            color: #3f6791;
        }
        
        .current-user {
            background-color: rgba(63, 103, 145, 0.1);
        }
        
        .user-rank-info {
            margin-top: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 800px;
        }
        
        .user-rank-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .user-rank-value {
            font-size: 24px;
            color: #3f6791;
            font-weight: 600;
        }
        
        .level-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .level-tab {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .level-tab.active {
            background-color: #3f6791;
            color: white;
        }
        
        .level-tab:not(.active) {
            background-color: #e0e0e0;
            color: #333;
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
        
        .empty-state {
            padding: 30px;
            text-align: center;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .leaderboard-item {
                padding: 10px;
            }
            
            .leaderboard-rank {
                width: 30px;
                height: 30px;
                margin-right: 10px;
            }
            
            .user-avatar {
                width: 30px;
                height: 30px;
            }
            
            .user-name {
                font-size: 14px;
            }
            
            .leaderboard-score {
                font-size: 16px;
            }
            
            .back-button {
                top: 80px;
                left: 10px;
                padding: 8px 12px;
                font-size: 14px;
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
                <?php echo htmlspecialchars($username); ?>
            </div>
        </div>
        
        <a href="level_content.php?level=<?php echo htmlspecialchars($class_level); ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="main-content">
            <h1 class="leaderboard-title">Leaderboard</h1>
            <p class="leaderboard-subtitle"><?php echo ucfirst(htmlspecialchars($class_level)); ?> Class</p>
            
            <div class="level-tabs">
                <a href="leaderboard.php?level=basic" class="level-tab <?php echo $class_level === 'basic' ? 'active' : ''; ?>">Basic</a>
                <a href="leaderboard.php?level=intermediate" class="level-tab <?php echo $class_level === 'intermediate' ? 'active' : ''; ?>">Intermediate</a>
                <a href="leaderboard.php?level=advanced" class="level-tab <?php echo $class_level === 'advanced' ? 'active' : ''; ?>">Advanced</a>
            </div>
            
            <div class="leaderboard-container">
                <div class="leaderboard-header">
                    <i class="fas fa-trophy"></i> Top Students
                </div>
                
                <div class="leaderboard-list">
                    <?php if (count($leaderboard) > 0): ?>
                        <?php 
                        $rank = 1;
                        foreach ($leaderboard as $player): 
                        ?>
                            <div class="leaderboard-item <?php echo $player['username'] === $username ? 'current-user' : ''; ?>">
                                <div class="leaderboard-rank <?php echo ($rank <= 3) ? 'rank-' . $rank : 'default-rank'; ?>">
                                    <?php echo $rank; ?>
                                </div>
                                <div class="leaderboard-user">
                                    <div class="user-avatar">
                                        <?php if (!empty($player['profile_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($player['profile_image']); ?>" alt="<?php echo htmlspecialchars($player['username']); ?>">
                                        <?php else: ?>
                                            <img src="../assets/img/orang.png" alt="Default Avatar">
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-name"><?php echo htmlspecialchars($player['username']); ?></div>
                                </div>
                                <div class="leaderboard-score"><?php echo (int)$player['score']; ?></div>
                            </div>
                            <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="user-rank-info">
                <div class="user-rank-title">Your Rank</div>
                <div class="user-rank-value"><?php echo $user_rank; ?> (Score: <?php echo (int)$user_score; ?>)</div>
            </div>
        </div>
    </div>
</body>
</html>
