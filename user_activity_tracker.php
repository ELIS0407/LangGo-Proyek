<?php
/**
 * User Activity Tracker
 * This file contains functions to track user activities for the weekly progress feature.
 */

/**
 * Track user activity
 * 
 * @param int $user_id The user ID
 * @param string $activity_type The activity type (level_complete, quiz_attempt, chat_message)
 * @param mysqli $conn Database connection
 * @return bool Whether the operation was successful
 */
function track_user_activity($user_id, $activity_type, $conn) {
    if (!in_array($activity_type, ['level_complete', 'quiz_attempt', 'chat_message'])) {
        return false;
    }
    
    date_default_timezone_set('Asia/Jakarta');
    
    $today = date('Y-m-d');
    $week_number = date('W');
    $year = date('Y');
    
    $check_query = "SELECT id, activity_count FROM user_weekly_activities 
                   WHERE user_id = ? AND activity_type = ? AND activity_date = ?";
    
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $activity_type, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $new_count = $row['activity_count'] + 1;
        $update_query = "UPDATE user_weekly_activities SET activity_count = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $new_count, $row['id']);
        return mysqli_stmt_execute($stmt);
    } else {
        $insert_query = "INSERT INTO user_weekly_activities 
                        (user_id, activity_type, activity_date, week_number, year) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "issis", $user_id, $activity_type, $today, $week_number, $year);
        return mysqli_stmt_execute($stmt);
    }
}

/**
 * Get user weekly activity
 * 
 * @param int $user_id The user ID
 * @param mysqli $conn Database connection
 * @return array Weekly activity counts
 */
function get_user_weekly_activity($user_id, $conn) {
    date_default_timezone_set('Asia/Jakarta');
    
    $days_of_week = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
    $activity_counts = array_fill_keys($days_of_week, 0);
    
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $query = "SELECT 
                activity_date,
                DAYOFWEEK(activity_date) as day_number,
                SUM(activity_count) as total_count
              FROM user_weekly_activities
              WHERE user_id = ? AND activity_date BETWEEN ? AND ?
              GROUP BY activity_date, day_number
              ORDER BY activity_date";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $week_start, $week_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $day_mapping = [
        1 => 'minggu',
        2 => 'senin',
        3 => 'selasa',
        4 => 'rabu',
        5 => 'kamis',
        6 => 'jumat',
        7 => 'sabtu'
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $day_number = $row['day_number'];
        if (isset($day_mapping[$day_number])) {
            $day_name = $day_mapping[$day_number];
            $activity_counts[$day_name] = (int)$row['total_count'];
        }
    }
    
    return $activity_counts;
}

/**
 * Force update today's activity (for debugging)
 * 
 * @param int $user_id The user ID
 * @param mysqli $conn Database connection
 * @return bool Whether the operation was successful
 */
function force_update_today_activity($user_id, $conn) {
    date_default_timezone_set('Asia/Jakarta');
    
    $today = date('Y-m-d');
    $week_number = date('W');
    $year = date('Y');
    $activity_type = 'level_complete';
    
    $delete_query = "DELETE FROM user_weekly_activities 
                   WHERE user_id = ? AND activity_date = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $today);
    mysqli_stmt_execute($stmt);
    
    $insert_query = "INSERT INTO user_weekly_activities 
                    (user_id, activity_type, activity_date, activity_count, week_number, year) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $activity_count = 5;
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "issiii", $user_id, $activity_type, $today, $activity_count, $week_number, $year);
    return mysqli_stmt_execute($stmt);
} 