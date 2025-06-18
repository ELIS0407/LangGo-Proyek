<?php
// Command-line script to generate test data for the leaderboard
// Run with: php generate_test_data.php

include_once "config.php";

echo "Generating test data for leaderboard...\n";

// Get all student users
$users_query = "SELECT id, username FROM users WHERE role = 'siswa'";
$users_result = mysqli_query($conn, $users_query);

if (!$users_result) {
    die("Error getting users: " . mysqli_error($conn) . "\n");
}

$users = [];
while ($row = mysqli_fetch_assoc($users_result)) {
    $users[] = $row;
}

echo "Found " . count($users) . " student users\n";

// Class levels
$class_levels = ['basic', 'intermediate', 'advanced'];

// Generate random progress data for each user
foreach ($users as $user) {
    echo "Generating data for user: " . $user['username'] . " (ID: " . $user['id'] . ")\n";
    
    foreach ($class_levels as $class_level) {
        // Randomly determine how many levels this user has completed (between 0 and 10)
        $completed_levels = rand(0, 10);
        echo "  - $class_level: $completed_levels levels completed\n";
        
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
                echo "Error inserting data for user {$user['username']}, level $level: " . mysqli_error($conn) . "\n";
            }
        }
    }
}

echo "Test data generated successfully!\n";

// Verify data was inserted
$count_query = "SELECT COUNT(*) as count FROM user_progress WHERE completed = 1";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
echo "Total completed levels: " . $count_row['count'] . "\n";

?> 