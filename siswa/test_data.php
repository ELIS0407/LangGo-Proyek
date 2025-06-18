<?php
// This is a temporary file to generate test data for the leaderboard
// Delete this file after testing

session_start();
include_once "../config.php";

// Check if we're running from browser or command line
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Check login for browser access
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
        header("location: ../login.php");
        exit;
    }
}

// Get all student users
$users_query = "SELECT id, username FROM users WHERE role = 'siswa'";
$users_result = mysqli_query($conn, $users_query);

if (!$users_result) {
    die("Error getting users: " . mysqli_error($conn));
}

$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[] = $row;
}

// Class levels
$class_levels = ['basic', 'intermediate', 'advanced'];

// Generate random progress data for each user
foreach ($users as $user) {
    foreach ($class_levels as $class_level) {
        // Randomly determine how many levels this user has completed (between 0 and 10)
        $completed_levels = rand(0, 10);
        
        // Delete any existing progress for this user in this class level
        $delete_query = "DELETE FROM user_progress WHERE user_id = {$user['id']} AND class_level = '$class_level'";
        mysqli_query($conn, $delete_query);
        
        // Insert progress data
        for ($level = 1; $level <= 10; $level++) {
            $completed = ($level <= $completed_levels) ? 1 : 0;
            $completed_at = ($completed) ? date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days")) : NULL;
            
            $insert_query = "INSERT INTO user_progress (user_id, class_level, level_number, completed, completed_at) 
                            VALUES ({$user['id']}, '$class_level', $level, $completed, " . 
                            ($completed ? "'" . $completed_at . "'" : "NULL") . ")";
            
            if (!mysqli_query($conn, $insert_query)) {
                echo "Error inserting data for user {$user['username']}, level $level: " . mysqli_error($conn) . "<br>";
            }
        }
    }
}

echo "Test data generated successfully!";
echo "<br><br><a href='leaderboard.php?level=basic'>View Basic Leaderboard</a>";
echo "<br><a href='leaderboard.php?level=intermediate'>View Intermediate Leaderboard</a>";
echo "<br><a href='leaderboard.php?level=advanced'>View Advanced Leaderboard</a>";
?> 