<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = null;
$email = "";
$phone = "";
$join_date = "";
$level_text = "BASIC";
$profile_image = "../assets/img/profile-placeholder.png"; 

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

$quiz_score = 0;
if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT SUM(score) as total_score FROM quiz_attempts WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $quiz_score = $row['total_score'] ? $row['total_score'] : 0;
    }
}

$level = 1;
if ($quiz_score >= 100) {
    $level = floor($quiz_score / 10);
}

if ($user_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT q.class_level 
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.user_id = ?
        ORDER BY qa.completed_at DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $level_text = strtoupper($row['class_level']);
    }
}

$days_of_week = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
$progress_data = array_fill_keys($days_of_week, 0);

if ($user_id) {
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $stmt = mysqli_prepare($conn, "
        SELECT 
            LOWER(DATE_FORMAT(completed_at, '%W')) as day_name, 
            COUNT(*) as attempt_count
        FROM quiz_attempts
        WHERE user_id = ? AND completed_at BETWEEN ? AND ?
        GROUP BY day_name
    ");
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $week_start, $week_end);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($progress_data[$row['day_name']])) {
            $progress_data[$row['day_name']] = $row['attempt_count'] * 50; // Scale for visualization
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LangGo!</title>
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
            height: 50px;
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
        }
        
        .user-name {
            text-align: right;
        }
        
        .level-tag {
            font-size: 12px;
            color: #ccc;
        }
        
        .content {
            display: flex;
            flex: 1;
        }
        
        .sidebar {
            width: 255px;
            background-color: #3f6791;
            color: white;
            padding: 20px;
        }
        
        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            background-color: #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
            position: relative;
            background-size: cover;
            background-position: center;
            overflow: hidden;
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
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .join-date {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .contact-info {
            width: 100%;
        }
        
        .info-item {
            background-color: white;
            color: #333;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .password-item {
            display: flex;
            justify-content: space-between;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-icon {
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 40px;
            font-weight: 600;
            color: #3f6791;
        }
        
        .progress-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .progress-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .chart-container {
            height: 250px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding-top: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .chart-bar {
            width: 40px;
            background-color: #ccc;
            border-radius: 5px 5px 0 0;
            position: relative;
        }
        
        .chart-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
        }
        
        .bar-senin {
            height: <?php echo $progress_data['senin']; ?>px;
            background-color: #36D6D6;
        }
        
        .bar-selasa {
            height: <?php echo $progress_data['selasa']; ?>px;
            background-color: #36B7F0;
        }
        
        .bar-rabu {
            height: <?php echo $progress_data['rabu']; ?>px;
            background-color: #5A9DF8;
        }
        
        .bar-kamis {
            height: <?php echo $progress_data['kamis']; ?>px;
            background-color: #9E93E8;
        }
        
        .bar-jumat {
            height: <?php echo $progress_data['jumat']; ?>px;
            background-color: #B36FE0;
        }
        
        .bar-sabtu {
            height: <?php echo $progress_data['sabtu']; ?>px;
            background-color: #D23BE7;
        }
        
        .bar-minggu {
            height: <?php echo $progress_data['minggu']; ?>px;
            background-color: #E23B9F;
        }
        
        .logout-btn {
            margin-top: 20px;
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
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
                <img src="../Logo-LangGo.png" alt="LangGo Logo">
            </div>
            <div class="nav-menu">
                <a href="class.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    CLASS
                </a>
                <a href="chat.php" class="nav-item">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
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
                <div class="user-name">
                    <div><?php echo $username; ?></div>
                    <div class="level-tag"><?php echo $level_text; ?></div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="sidebar">
                <div class="profile-section">
                    <div class="profile-pic">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Picture" id="profileImage">
                        <div class="edit-icon" id="editProfileBtn">
                            <i class="fas fa-pencil-alt"></i>
                        </div>
                    </div>
                    <div class="profile-name"><?php echo $username; ?></div>
                    <div class="join-date">Joined in <?php echo $join_date; ?></div>
                </div>
                
                <div class="contact-info">
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <?php echo $email; ?>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <?php echo $phone; ?>
                    </div>
                    <div class="info-item password-item">
                        <div>
                            <i class="fas fa-lock"></i>
                            *****
                        </div>
                        <i class="fas fa-eye eye-icon"></i>
                    </div>
                </div>
                
                <form action="../logout.php" method="post">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
            
            <div class="main-content">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-title">Selected class</div>
                        <div class="stat-icon">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiMzZjY3OTEiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNNCAxOWgxNnYySDR6TTQgM2gxNnYySDR6Ii8+PHBhdGggZD0iTTQgNWg3djEwSDR6Ii8+PHBhdGggZD0iTTEzIDVoN3YxMGgtN3oiLz48cGF0aCBkPSJNOCAxNWwyIDNNMTYgMTVsMi0zIi8+PC9zdmc+" alt="Book Icon">
                        </div>
                        <div class="stat-value"><?php echo $level_text; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">quiz score</div>
                        <div class="stat-icon">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiMzZjY3OTEiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTQgMkgxMGE0IDQgMCAwIDAtNCA0djEyYTQgNCAwIDAgMCA0IDRoMTBhNCA0IDAgMCAwIDQtNFY4eiIvPjxwb2x5bGluZSBwb2ludHM9IjE0IDIgMTQgOCAyMCA4Ii8+PC9zdmc+" alt="Quiz Icon">
                        </div>
                        <div class="stat-value"><?php echo $quiz_score; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">highest level</div>
                        <div class="stat-icon">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiMzZjY3OTEiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTIgMmwyIDJoNmE0IDQgMCAwIDEgNCA0djEwYTQgNCAwIDAgMS00IDRINGE0IDQgMCAwIDEtNC00VjhhNCA0IDAgMCAxIDQtNGg2bDItMnoiLz48cGF0aCBkPSJNOSAxMmg2TTEyIDl2NiIvPjwvc3ZnPg==" alt="Level Icon">
                        </div>
                        <div class="stat-value">Level<br><?php echo $level; ?></div>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-title">Progress Belajar</div>
                    <div class="chart-container">
                        <div class="chart-bar bar-senin">
                            <div class="chart-label">Senin</div>
                        </div>
                        <div class="chart-bar bar-selasa">
                            <div class="chart-label">Selasa</div>
                        </div>
                        <div class="chart-bar bar-rabu">
                            <div class="chart-label">Rabu</div>
                        </div>
                        <div class="chart-bar bar-kamis">
                            <div class="chart-label">Kamis</div>
                        </div>
                        <div class="chart-bar bar-jumat">
                            <div class="chart-label">Jumat</div>
                        </div>
                        <div class="chart-bar bar-sabtu">
                            <div class="chart-label">Sabtu</div>
                        </div>
                        <div class="chart-bar bar-minggu">
                            <div class="chart-label">Minggu</div>
                        </div>
                    </div>
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
        const modal = document.getElementById('profileModal');
        const editBtn = document.getElementById('editProfileBtn');
        const closeBtn = document.querySelector('.close');
        const fileInput = document.getElementById('profileImageInput');
        const imagePreview = document.getElementById('imagePreview');
        const profileForm = document.getElementById('profileForm');
        const statusMessage = document.getElementById('statusMessage');
        const profileImage = document.getElementById('profileImage');
        
        document.querySelector('.eye-icon').addEventListener('click', function() {
            const passwordText = document.querySelector('.password-item div:first-child').childNodes[2];
            if (passwordText.textContent.trim() === '*****') {
                passwordText.textContent = 'Password tersembunyi';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordText.textContent = '*****';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
        
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