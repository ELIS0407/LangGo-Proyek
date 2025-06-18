<?php
// Script to create the user_progress table for the leaderboard functionality

// Include database configuration
include_once "config.php";

// SQL to create the user_progress table
$sql = "CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_level ENUM('basic', 'intermediate', 'advanced') NOT NULL,
    level_number INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, class_level, level_number)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table user_progress created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

// Add the table creation to the setup_database.sql file for future reference
$sql_to_append = "
-- Create user_progress table
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_level ENUM('basic', 'intermediate', 'advanced') NOT NULL,
    level_number INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, class_level, level_number)
);
";

// Append to setup_database.sql
file_put_contents("setup_database.sql", $sql_to_append, FILE_APPEND);

echo "<br>SQL added to setup_database.sql file";
?> 