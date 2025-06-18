<?php
// Simple script to check database tables and records
include_once "config.php";

echo "<h1>Database Check</h1>";

// Check if user_progress table exists
$check_table_query = "SHOW TABLES LIKE 'user_progress'";
$table_exists = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($table_exists) > 0) {
    echo "<p>✅ user_progress table exists</p>";
    
    // Count records
    $count_query = "SELECT COUNT(*) as count FROM user_progress";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    echo "<p>Records in user_progress: " . $count_row['count'] . "</p>";
    
    // Check if there are completed levels
    $completed_query = "SELECT COUNT(*) as count FROM user_progress WHERE completed = 1";
    $completed_result = mysqli_query($conn, $completed_query);
    $completed_row = mysqli_fetch_assoc($completed_result);
    echo "<p>Completed levels in user_progress: " . $completed_row['count'] . "</p>";
    
    // Show some sample records
    echo "<h2>Sample Records:</h2>";
    $sample_query = "SELECT up.*, u.username FROM user_progress up 
                    JOIN users u ON up.user_id = u.id 
                    WHERE completed = 1 
                    LIMIT 5";
    $sample_result = mysqli_query($conn, $sample_query);
    
    if (mysqli_num_rows($sample_result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User</th><th>Class Level</th><th>Level Number</th><th>Completed</th><th>Completed At</th></tr>";
        
        while ($row = mysqli_fetch_assoc($sample_result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['class_level'] . "</td>";
            echo "<td>" . $row['level_number'] . "</td>";
            echo "<td>" . ($row['completed'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $row['completed_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No completed levels found.</p>";
    }
} else {
    echo "<p>❌ user_progress table does not exist</p>";
}

// Check users table
$users_query = "SELECT COUNT(*) as count FROM users WHERE role = 'siswa'";
$users_result = mysqli_query($conn, $users_query);
$users_row = mysqli_fetch_assoc($users_result);
echo "<p>Student users in the database: " . $users_row['count'] . "</p>";

// Run the test data script if needed
echo "<p><a href='siswa/test_data.php'>Generate Test Data</a></p>";
echo "<p><a href='siswa/leaderboard.php?level=basic'>View Leaderboard</a></p>";
?> 