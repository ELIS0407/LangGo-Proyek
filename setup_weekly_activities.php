<?php
include_once "config.php";

echo "Setting up the user_weekly_activities table...<br>";

$sql = "CREATE TABLE IF NOT EXISTS user_weekly_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('level_complete', 'quiz_attempt', 'chat_message') NOT NULL,
    activity_date DATE NOT NULL,
    activity_count INT DEFAULT 1,
    week_number INT NOT NULL,
    year INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_activity (user_id, activity_type, activity_date)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table user_weekly_activities created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

echo "Generating sample data for weekly activities...<br>";
$user_query = "SELECT id FROM users WHERE role = 'siswa'";
$user_result = mysqli_query($conn, $user_query);

$activity_types = ['level_complete', 'quiz_attempt', 'chat_message'];
$current_date = date('Y-m-d');
$week_number = date('W');
$year = date('Y');


$count = 0;
while ($user = mysqli_fetch_assoc($user_result)) {
    $user_id = $user['id'];
    
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        if (rand(0, 1)) {
            $activity_type = $activity_types[array_rand($activity_types)];
            $activity_count = rand(1, 5);
            
            $insert_query = "INSERT IGNORE INTO user_weekly_activities 
                            (user_id, activity_type, activity_date, activity_count, week_number, year) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "issiii", $user_id, $activity_type, $date, $activity_count, $week_number, $year);
            
            if (mysqli_stmt_execute($stmt)) {
                $count++;
            }
        }
    }
}

echo "Generated $count sample activities<br>";
echo "Weekly activities setup complete!";
?> 