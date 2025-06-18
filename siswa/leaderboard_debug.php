<?php
// Debug version of leaderboard.php with more error information
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once "../config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

echo "<h1>Leaderboard Debug</h1>";
echo "<p>User ID: $userId, Username: $username</p>";

// Get the class level from URL parameter
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

echo "<p>Class Level: $class_level</p>";

// Get top 10 students by completed levels for the selected class
$leaderboard_query = "SELECT u.id, u.username, u.profile_image, 
                      COUNT(CASE WHEN up.completed = 1 THEN 1 END) AS completed_levels,
                      COUNT(CASE WHEN up.completed = 1 THEN 1 END) * 5 AS score
                      FROM users u
                      LEFT JOIN user_progress up ON u.id = up.user_id AND up.class_level = '$class_level'
                      WHERE u.role = 'siswa'
                      GROUP BY u.id
                      HAVING score > 0
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
    echo "<tr><th>Rank</th><th>ID</th><th>Username</th><th>Completed Levels</th><th>Score</th></tr>";
    
    $rank = 1;
    foreach ($leaderboard as $player) {
        echo "<tr>";
        echo "<td>" . $rank . "</td>";
        echo "<td>" . $player['id'] . "</td>";
        echo "<td>" . $player['username'] . "</td>";
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
echo "<p><a href='test_data.php'>Generate Test Data</a></p>";
echo "<p><a href='leaderboard.php?level=basic'>View Regular Leaderboard</a></p>";
?> 