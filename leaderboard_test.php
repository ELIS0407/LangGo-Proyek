<?php
// Test version of leaderboard.php that doesn't require login
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "../config.php";

// Set default user for testing
$username = "test_user";
$userId = 1; // Assuming user ID 1 exists

// Get the class level from URL parameter
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

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

echo "<p><strong>Leaderboard Query:</strong> $leaderboard_query</p>";

$leaderboard_result = mysqli_query($conn, $leaderboard_query);

if (!$leaderboard_result) {
    echo "<p style='color: red;'>Error in leaderboard query: " . mysqli_error($conn) . "</p>";
} else {
    echo "<p>Query executed successfully</p>";
}

$leaderboard = [];
while ($row = mysqli_fetch_assoc($leaderboard_result)) {
    $leaderboard[] = $row;
}

echo "<p>Found " . count($leaderboard) . " players for the leaderboard</p>";

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
    echo "<p style='color: red;'>Error in all users query: " . mysqli_error($conn) . "</p>";
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
        echo "<p>Found current user at rank $user_rank with score $user_score</p>";
        break;
    }
    
    $prev_score = $row['score'];
}

// If user wasn't found in the results, they have no completed levels
if ($user_rank == 0) {
    $user_rank = "-";
    echo "<p>Current user not found in rankings</p>";
}

// Display the leaderboard data for debugging
echo "<h2>Leaderboard Data:</h2>";
if (count($leaderboard) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Rank</th><th>ID</th><th>Username</th><th>Profile Image</th><th>Completed Levels</th><th>Score</th></tr>";
    
    $rank = 1;
    foreach ($leaderboard as $player) {
        echo "<tr>";
        echo "<td>" . $rank . "</td>";
        echo "<td>" . $player['id'] . "</td>";
        echo "<td>" . $player['username'] . "</td>";
        echo "<td>" . ($player['profile_image'] ? $player['profile_image'] : 'Default') . "</td>";
        echo "<td>" . $player['completed_levels'] . "</td>";
        echo "<td>" . $player['score'] . "</td>";
        echo "</tr>";
        $rank++;
    }
    
    echo "</table>";
} else {
    echo "<p>No data available</p>";
}

// Link to generate test data
echo "<p><a href='../generate_test_data.php'>Generate Test Data</a></p>";
echo "<p><a href='leaderboard.php?level=basic'>View Regular Leaderboard</a></p>";

// Now display the leaderboard HTML
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard Test - <?php echo ucfirst($class_level); ?> Class - LangGo!</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
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
        
        .debug-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center; margin-bottom: 20px;">Leaderboard Test</h1>
    
    <div class="container">
        <div class="leaderboard-header">
            <i class="fas fa-trophy"></i> Top Students - <?php echo ucfirst($class_level); ?> Class
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
                <div style="padding: 30px; text-align: center;">
                    <p>No data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 