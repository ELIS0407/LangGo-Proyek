<?php
session_start();
include 'config.php';

$role = isset($_GET['role']) ? $_GET['role'] : 'siswa';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE username = '$username' AND role = '$role'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            // Verify password
            if (password_verify($password, $row['password'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $row['id'];
                
                // Redirect based on role
                if ($role == 'siswa') {
                    header("location: siswa/dashboard.php");
                } else {
                    header("location: guru/dashboard.php");
                }
                exit;
            } else {
                $error = "Password salah";
            }
        } else {
            $error = "Username tidak ditemukan";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LangGo!</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <img src="Logo-LangGo.png" alt="LangGo Logo">
        </div>
        <div class="right-panel">
            <h1>LangGo! Belajar Bahasa Cepat dan Seru<br>Berbasis WEB</h1>
            
            <?php if (!empty($error)): ?>
                <div class="message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="login.php?role=<?php echo $role; ?>" method="post">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username.." required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password.." required>
                    </div>
                </div>
                <button type="submit" class="btn">Sign In</button>
                <a href="register.php?role=<?php echo $role; ?>" class="btn btn-secondary">Sign Up</a>
            </form>
        </div>
    </div>
</body>
</html> 