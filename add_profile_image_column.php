<?php

include 'config.php';

$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_image'");

if (mysqli_num_rows($check_column) == 0) {
    $alter_query = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "Profile image column added successfully.";
    } else {
        echo "Error adding profile image column: " . mysqli_error($conn);
    }
} else {
    echo "Profile image column already exists.";
}

mysqli_close($conn);
?> 