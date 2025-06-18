<?php
session_start();
include 'config.php';

$role = isset($_GET['role']) ? $_GET['role'] : 'siswa';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = "Semua field harus diisi";
    } else {
        // Check if username already exists
        $check_sql = "SELECT * FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Username sudah digunakan";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $insert_sql = "INSERT INTO users (username, email, phone, password, role) 
                          VALUES ('$username', '$email', '$phone', '$hashed_password', '$role')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success = "Registrasi berhasil! Silakan login";
            } else {
                $error = "Error: " . $insert_sql . "<br>" . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LangGo!</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <img src="assets/img/Logo-LangGo.png" alt="LangGo Logo">
        </div>
        <div class="right-panel">
            <h1>LangGo! Belajar Bahasa Cepat dan Seru<br>Berbasis WEB</h1>
            
            <?php if (!empty($error)): ?>
                <div class="message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="register.php?role=<?php echo $role; ?>" method="post">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username.." required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email.." required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                    <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" placeholder="No.telepon" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password.." required>
                    </div>
                </div>
                <button type="submit" class="btn">Sign Up</button>
                <a href="login.php?role=<?php echo $role; ?>" class="btn btn-secondary">Sign In</a>
            </form>
        </div>
    </div>
</body>
</html> 