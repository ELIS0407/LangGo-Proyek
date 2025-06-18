<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'guru') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$response = ['success' => false, 'message' => '', 'image_url' => ''];

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_path = "../assets/img/profiles/" . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_image_path = "assets/img/profiles/" . $new_file_name;
                
                $check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_image'");
                if (mysqli_num_rows($check_column) == 0) {
                    mysqli_query($conn, "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)");
                }
                
                $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $profile_image_path, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = "Profile picture updated successfully";
                    $response['image_url'] = "../" . $profile_image_path;
                } else {
                    $response['message'] = "Failed to update database: " . mysqli_error($conn);
                }
            } else {
                $response['message'] = "Failed to upload image";
            }
        } else {
            $response['message'] = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
        }
    } else {
        $response['message'] = "No image uploaded or error occurred";
    }
} else {
    $response['message'] = "User not found";
}


header('Content-Type: application/json');
echo json_encode($response);
?> 