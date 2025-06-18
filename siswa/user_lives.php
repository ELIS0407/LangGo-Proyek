<?php
// Check if user_lives table exists, if not create it
function initialize_lives_system($conn) {
    $check_table_query = "SHOW TABLES LIKE 'user_lives'";
    $table_exists = mysqli_query($conn, $check_table_query);
    if (mysqli_num_rows($table_exists) == 0) {
        $create_table_query = "CREATE TABLE user_lives (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            lives INT DEFAULT 5 NOT NULL,
            last_refill TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        mysqli_query($conn, $create_table_query);
        
        // Initialize lives for all existing students
        $get_users_query = "SELECT id FROM users WHERE role = 'siswa'";
        $users_result = mysqli_query($conn, $get_users_query);
        while ($user = mysqli_fetch_assoc($users_result)) {
            mysqli_query($conn, "INSERT INTO user_lives (user_id, lives) VALUES ({$user['id']}, 5)");
        }
    }
}

// Reset all refill timers to current time
function reset_all_refill_timers($conn) {
    $reset_query = "UPDATE user_lives SET last_refill = NOW()";
    return mysqli_query($conn, $reset_query);
}

// Get user lives data
function get_user_lives($conn, $userId) {
    $lives_query = "SELECT lives, last_refill FROM user_lives WHERE user_id = $userId";
    $lives_result = mysqli_query($conn, $lives_query);

    $lives_data = array(
        'lives' => 5, // Default value
        'last_refill' => date("Y-m-d H:i:s"), // Default to current time
        'minutes_until_refill' => 0,
        'needs_refill' => false
    );

    if (mysqli_num_rows($lives_result) > 0) {
        $row = mysqli_fetch_assoc($lives_result);
        $lives_data['lives'] = $row['lives'];
        $lives_data['last_refill'] = $row['last_refill'];
        
        // Check if it's time for a refill (5 minutes since last refill)
        $refill_interval = 5 * 60; // 5 minutes in seconds
        $time_since_refill = time() - strtotime($row['last_refill']);
        
        if ($time_since_refill >= $refill_interval && $lives_data['lives'] < 5) {
            // Refill one life
            $lives_data['lives'] = min(5, $lives_data['lives'] + 1);
            $lives_data['last_refill'] = date("Y-m-d H:i:s");
            $lives_data['needs_refill'] = true;
        } else if ($lives_data['lives'] < 5) {
            // Calculate minutes until next refill
            $seconds_until_refill = $refill_interval - $time_since_refill;
            // Ensure it's never more than the refill interval
            $seconds_until_refill = min($seconds_until_refill, $refill_interval);
            $lives_data['minutes_until_refill'] = $seconds_until_refill / 60;
        }
    } else {
        // Insert new record for user
        $insert_lives_query = "INSERT INTO user_lives (user_id, lives) VALUES ($userId, 5)";
        mysqli_query($conn, $insert_lives_query);
    }
    
    return $lives_data;
}

// Update lives in database if needed
function update_user_lives($conn, $userId, $lives, $refill = false) {
    if ($refill) {
        $update_query = "UPDATE user_lives SET lives = $lives, last_refill = NOW() WHERE user_id = $userId";
    } else {
        $update_query = "UPDATE user_lives SET lives = $lives WHERE user_id = $userId";
    }
    return mysqli_query($conn, $update_query);
}

// Decrease lives by 1
function decrease_lives($conn, $userId) {
    $lives_data = get_user_lives($conn, $userId);
    
    if ($lives_data['lives'] > 0) {
        $lives_data['lives']--;
        update_user_lives($conn, $userId, $lives_data['lives']);
    }
    
    return $lives_data['lives'];
}

// Reset refill timer for a specific user
function reset_user_refill_timer($conn, $userId) {
    $reset_query = "UPDATE user_lives SET last_refill = NOW() WHERE user_id = $userId";
    return mysqli_query($conn, $reset_query);
}

// Generate HTML for lives display
function generate_lives_html($lives, $minutes_until_refill) {
    $html = '<div class="lives-container">';
    $html .= '<div class="lives-hearts">';
    
    // Display filled hearts for remaining lives
    for ($i = 0; $i < $lives; $i++) {
        $html .= '<i class="fas fa-heart"></i>';
    }
    
    // Display empty hearts for lost lives
    for ($i = $lives; $i < 5; $i++) {
        $html .= '<i class="far fa-heart"></i>';
    }
    
    $html .= '</div>';
    
    // Show refill timer if needed
    if ($lives < 5) {
        $html .= '<div class="refill-timer">Refill in: ' . $minutes_until_refill . ' min</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?> 