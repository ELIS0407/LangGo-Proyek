<?php
include_once "config.php";
include_once "user_activity_tracker.php";

date_default_timezone_set('Asia/Jakarta');

echo "<h2>Update Aktivitas Hari Ini (" . date('l, d F Y') . ")</h2>";


$query = "SELECT id, username FROM users WHERE role = 'siswa'";
$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr><th>User ID</th><th>Username</th><th>Status</th></tr>";

while ($user = mysqli_fetch_assoc($result)) {
    $user_id = $user['id'];
    $username = $user['username'];
    
    
    $result_update = force_update_today_activity($user_id, $conn);
    
    echo "<tr>";
    echo "<td>" . $user_id . "</td>";
    echo "<td>" . $username . "</td>";
    echo "<td>" . ($result_update ? "Berhasil" : "Gagal") . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Aktivitas yang Terekam</h2>";


$query = "SELECT 
    a.activity_date, 
    a.activity_type, 
    a.activity_count, 
    u.username
FROM user_weekly_activities a
JOIN users u ON a.user_id = u.id
WHERE a.activity_date = CURDATE()
ORDER BY u.username";

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr><th>Username</th><th>Tanggal</th><th>Hari</th><th>Tipe Aktivitas</th><th>Jumlah</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    $day_name = date('l', strtotime($row['activity_date']));
    
    echo "<tr>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['activity_date'] . "</td>";
    echo "<td>" . $day_name . "</td>";
    echo "<td>" . $row['activity_type'] . "</td>";
    echo "<td>" . $row['activity_count'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><a href='siswa/dashboard.php'>Lihat Dashboard</a></p>";
?> 