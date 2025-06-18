<?php
date_default_timezone_set('Asia/Jakarta');

echo "<h2>Debug Informasi Hari</h2>";

echo "<p><strong>Hari ini:</strong> " . date('l, d F Y') . "</p>";

echo "<p><strong>Hari dalam seminggu:</strong></p>";
echo "<ul>";
for ($i = 0; $i < 7; $i++) {
    $day = date('l, d F Y', strtotime("-$i days"));
    echo "<li>$i hari yang lalu: $day</li>";
}
echo "</ul>";

echo "<p><strong>Start dan End minggu ini:</strong></p>";
$week_start = date('l, d F Y', strtotime('monday this week'));
$week_end = date('l, d F Y', strtotime('sunday this week'));
echo "<p>Monday this week: $week_start</p>";
echo "<p>Sunday this week: $week_end</p>";

echo "<p><strong>Nama hari dalam bahasa Inggris:</strong></p>";
$day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($day_names as $day) {
    echo "<p>$day: " . strtolower($day) . "</p>";
}

echo "<p><strong>Test DATE_FORMAT in MySQL:</strong></p>";
include_once "config.php";
$query = "SELECT 
    DATE_FORMAT(NOW(), '%W') AS day_name,
    DAYNAME(NOW()) AS day_name2,
    LOWER(DATE_FORMAT(NOW(), '%W')) AS day_name_lower
";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

echo "<p>DATE_FORMAT(NOW(), '%W'): " . $row['day_name'] . "</p>";
echo "<p>DAYNAME(NOW()): " . $row['day_name2'] . "</p>";
echo "<p>LOWER(DATE_FORMAT(NOW(), '%W')): " . $row['day_name_lower'] . "</p>";


$query = "SELECT 
    activity_date,
    DATE_FORMAT(activity_date, '%W') as day_name,
    COUNT(*) as total
    FROM user_weekly_activities
    WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY activity_date, day_name
    ORDER BY activity_date DESC";

$result = mysqli_query($conn, $query);

echo "<p><strong>Data dari user_weekly_activities:</strong></p>";
echo "<table border='1'>";
echo "<tr><th>Tanggal</th><th>Hari (MySQL)</th><th>Jumlah Data</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['activity_date'] . "</td>";
    echo "<td>" . $row['day_name'] . "</td>";
    echo "<td>" . $row['total'] . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 