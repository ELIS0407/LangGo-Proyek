<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'guru') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = null;
$email = "";
$phone = "";
$join_date = "";
$profile_image = "../assets/img/profile-placeholder.png"; // Default profile image

$stmt = mysqli_prepare($conn, "SELECT id, email, phone, created_at, profile_image FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
    $email = $row['email'];
    $phone = $row['phone'];
    $join_date = date('M Y', strtotime($row['created_at']));
    
    if (!empty($row['profile_image'])) {
        $profile_image = "../" . $row['profile_image'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - LangGo!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header {
            background-color: #3f6791;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 65px;
            margin-right: 10px;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            color: white;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .user-level {
            font-size: 12px;
            color: #ccc;
        }
        
        .main-content {
            flex: 1;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .profile-header {
            padding: 30px;
            text-align: center;
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #ccc;
            margin: 0 auto 20px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .edit-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 2;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-join-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .profile-info {
            padding: 0 20px 20px;
        }
        
        .info-item {
            padding: 15px;
            border-radius: 10px;
            background-color: #f9f9f9;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .info-item i {
            font-size: 20px;
            color: #3f6791;
            width: 24px;
            text-align: center;
        }
        
        .info-text {
            font-size: 16px;
            color: #333;
            flex: 1;
        }
        
        .password-item {
            position: relative;
        }
        
        .eye-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        
        .logout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c82333;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #3f6791;
        }
        
        .file-input-container {
            margin-bottom: 20px;
        }
        
        .file-input-label {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            color: #333;
        }
        
        .file-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .file-preview img {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        
        .file-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .upload-btn {
            background-color: #3f6791;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .upload-btn:hover {
            background-color: #2c4b6a;
        }
        
        .status-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            display: none;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="../assets/img/Logo-LangGo.png" alt="LangGo Logo">
            </div>
            <div class="nav-menu">
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    Chat
                </a>
                <a href="quiz.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-user"></i>
                    PROFILE
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                    <div class="user-level">Instruktur/Guru</div>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-pic">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Picture" id="profileImage">
                        <div class="edit-icon" id="editProfileBtn">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                    </div>
                    <h1 class="profile-name"><?php echo $username; ?></h1>
                    <p class="profile-join-date">Joined in <?php echo $join_date; ?></p>
                </div>
                
                <div class="profile-info">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span class="info-text"><?php echo $email; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span class="info-text"><?php echo $phone; ?></span>
                    </div>
                    
                    <div class="info-item password-item">
                        <i class="fas fa-lock"></i>
                        <span class="info-text">*****</span>
                        <i class="fas fa-eye eye-icon"></i>
                    </div>
                    
                    <form action="../logout.php" method="post">
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Update Profile Picture</h2>
            
            <form id="profileForm" enctype="multipart/form-data">
                <div class="file-input-container">
                    <label class="file-input-label">Choose an image:</label>
                    <div class="file-preview">
                        <img id="imagePreview" src="#" alt="Preview">
                    </div>
                    <input type="file" id="profileImageInput" name="profile_image" class="file-input" accept="image/*">
                </div>
                
                <button type="submit" class="upload-btn">Upload</button>
            </form>
            
            <div id="statusMessage" class="status-message"></div>
        </div>
    </div>
    
    <script>
        document.querySelector('.eye-icon').addEventListener('click', function() {
            const passwordText = document.querySelector('.password-item .info-text');
            if (passwordText.textContent === '*****') {
                passwordText.textContent = 'Password tersembunyi';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordText.textContent = '*****';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
        
        const modal = document.getElementById('profileModal');
        const editBtn = document.getElementById('editProfileBtn');
        const closeBtn = document.querySelector('.close');
        const fileInput = document.getElementById('profileImageInput');
        const imagePreview = document.getElementById('imagePreview');
        const profileForm = document.getElementById('profileForm');
        const statusMessage = document.getElementById('statusMessage');
        const profileImage = document.getElementById('profileImage');
        
        editBtn.onclick = function() {
            modal.style.display = 'block';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        fileInput.onchange = function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
        
        profileForm.onsubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            statusMessage.textContent = 'Uploading...';
            statusMessage.className = 'status-message';
            statusMessage.style.display = 'block';
            
            fetch('upload_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusMessage.textContent = data.message;
                    statusMessage.className = 'status-message success';
                    
                    profileImage.src = data.image_url;
                    
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 2000);
                } else {
                    statusMessage.textContent = data.message;
                    statusMessage.className = 'status-message error';
                }
            })
            .catch(error => {
                statusMessage.textContent = 'An error occurred. Please try again.';
                statusMessage.className = 'status-message error';
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html> 