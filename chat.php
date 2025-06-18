<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$class_level = "Advanced";

$class_level_db = strtolower($class_level);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Chat - LangGo!</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/themes/default.css" />
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
            color: white;
        }
        
        .user-level {
            font-size: 12px;
            color: #ccc;
        }
        
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #1e4164;
            position: relative;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-self {
            align-self: flex-end;
            background-color: #dcf8c6;
            border-top-right-radius: 0;
        }
        
        .message-other {
            align-self: flex-start;
            background-color: #ffffff;
            border-top-left-radius: 0;
        }
        
        .message-sender {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .message-sender.student {
            color: #3f6791;
        }
        
        .message-sender.teacher {
            color: #9c27b0;
        }
        
        .message-text {
            color: #333;
        }
        
        .message-time {
            font-size: 11px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }
        
        .chat-input {
            background-color: #3f6791;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .emoji-btn {
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        .emoji-picker {
            position: absolute;
            bottom: 60px;
            z-index: 100;
            max-height: 300px;
            width: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .hidden {
            display: none;
        }

        
        .input-field {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            background-color: white;
        }
        
        .input-field:focus {
            outline: none;
        }
        
        .send-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        
        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                gap: 10px;
            }
            
            .nav-item {
                padding: 8px 10px;
                font-size: 14px;
            }
            
            .message {
                max-width: 90%;
            }
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
                <a href="class.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i>
                    CLASS
                </a>
                <a href="chat.php" class="nav-item active">
                    <i class="fas fa-comments"></i>
                    CLASS CHAT
                </a>
                <a href="quiz.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    QUIZ
                </a>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-user"></i>
                    PROFILE
                </a>
            </div>
            <div class="user-info">
                <div>
                    <?php echo $username; ?>
                   
                </div>
            </div>
        </div>
        
        <div class="chat-container">
            <div id="loading-overlay" class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            
            <div class="chat-messages" id="chat-messages">
            </div>
            
            <div class="chat-input">
                <div class="emoji-btn" id="emoji-btn">
                    <i class="far fa-smile"></i>
                </div>
                <emoji-picker class="hidden" id="emoji-picker"></emoji-picker>

                
                <input type="text" id="message-input" class="input-field" placeholder="Type a message" autocomplete="off">
                <button id="send-btn" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            var lastMessageId = 0;
            var classLevel = '<?php echo $class_level_db; ?>';
            var isFirstLoad = true;
            
            loadMessages();
            
            setInterval(checkNewMessages, 3000);
            
            $('#send-btn').click(sendMessage);
            
            $('#message-input').keypress(function(e) {
                if (e.which === 13) { // Enter key
                    sendMessage();
                }
            });
            
            function loadMessages() {
                $.ajax({
                    url: 'chat_operations.php',
                    type: 'GET',
                    data: {
                        operation: 'get_all_messages',
                        class_level: classLevel
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#chat-messages').empty();
                            
                            if (response.messages.length > 0) {
                                // Display messages
                                $.each(response.messages, function(index, message) {
                                    appendMessage(message);
                                    lastMessageId = Math.max(lastMessageId, message.id);
                                });
                                
                                // Scroll to bottom
                                scrollToBottom();
                            }
                            
                            // Hide loading overlay
                            $('#loading-overlay').addClass('hidden');
                            isFirstLoad = false;
                        } else {
                            console.error('Error loading messages:', response.message);
                            $('#loading-overlay').addClass('hidden');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        $('#loading-overlay').addClass('hidden');
                    }
                });
            }
            
            function checkNewMessages() {
                if (isFirstLoad) return; // Skip if still loading initially
                
                $.ajax({
                    url: 'chat_operations.php',
                    type: 'GET',
                    data: {
                        operation: 'get_messages',
                        class_level: classLevel,
                        last_id: lastMessageId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success' && response.messages.length > 0) {
                            // Display new messages
                            $.each(response.messages, function(index, message) {
                                appendMessage(message);
                                lastMessageId = Math.max(lastMessageId, message.id);
                            });
                            
                            // Scroll to bottom if new messages
                            scrollToBottom();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error checking for new messages:', error);
                    }
                });
            }
            
            function sendMessage() {
                var messageText = $('#message-input').val().trim();
                if (messageText === '') return;
                
                $('#message-input').val('');
                
                $.ajax({
                    url: 'chat_operations.php',
                    type: 'POST',
                    data: {
                        operation: 'send_message',
                        message: messageText,
                        class_level: classLevel
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                        } else {
                            console.error('Error sending message:', response.message);
                            alert('Failed to send message: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error sending message:', error);
                        alert('Failed to send message. Please try again.');
                    }
                });
            }
            
            function appendMessage(message) {
                var messageClass = message.is_self ? 'message-self' : 'message-other';
                var senderClass = message.sender_role === 'guru' ? 'teacher' : 'student';
                var time = new Date(message.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                var messageHtml = 
                    '<div class="message ' + messageClass + '" data-id="' + message.id + '">' +
                        '<div class="message-sender ' + senderClass + '">' + message.sender + '</div>' +
                        '<div class="message-text">' + message.message + '</div>' +
                        '<div class="message-time">' + time + '</div>' +
                    '</div>';
                
                $('#chat-messages').append(messageHtml);
            }
            
            function scrollToBottom() {
                var chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }


            // Tampilkan/sembunyikan emoji picker
            $('#emoji-btn').click(function(e) {
                $('#emoji-picker').toggleClass('hidden');
                e.stopPropagation();
            });


            // Tambahkan emoji ke input saat dipilih
            document.querySelector('#emoji-picker').addEventListener('emoji-click', event => {
                const emoji = event.detail.unicode;
                $('#message-input').val($('#message-input').val() + emoji).focus();
            });


            // Sembunyikan saat klik di luar
            $(document).click(function(e) {
                if (!$(e.target).closest('#emoji-picker, #emoji-btn').length) {
                    $('#emoji-picker').addClass('hidden');
                }
            
            });
            
            

        });
    </script>
</body>
</html> 