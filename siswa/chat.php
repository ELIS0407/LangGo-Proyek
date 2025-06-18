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

    /* Header */
    .header {
    background-color: #3f6791;
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
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
    text-decoration: none;
    color: white;
    transition: background-color 0.3s;
    }

    .nav-item:hover,
    .nav-item.active {
    background-color: rgba(255, 255, 255, 0.1);
    }

    .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    }

    /* Chat Container */
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

    /* Input Section */
    .chat-input {
    background-color: #3f6791;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
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

    .emoji-btn {
    color: white;
    font-size: 24px;
    cursor: pointer;
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

    /* Emoji Picker */
    .emoji-picker {
    position: absolute;
    bottom: 65px;
    left: 0;
    width: 560px;
    max-height: 300px;
    overflow-y: auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    padding: 15px;
    z-index: 100;
    }

    .emoji-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 10px;
    }

    .emoji-grid span {
    font-size: 24px;
    cursor: pointer;
    text-align: center;
    transition: transform 0.1s ease-in-out;
    }

    .emoji-grid span:hover {
    transform: scale(1.3);
    }

    .hidden {
    display: none;
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
        <div id="loading-overlay" class="loading-overlay hidden">
            <div class="loading-spinner"></div>
        </div>
        <div class="chat-messages" id="chat-messages"></div>
        <div class="chat-input">
            <div class="emoji-btn" id="emoji-btn"><i class="far fa-smile"></i></div>
            <input type="text" id="message-input" class="input-field" placeholder="Type a message" autocomplete="off">
            <button id="send-btn" class="send-btn"><i class="fas fa-paper-plane"></i></button>
            <div id="emoji-picker" class="emoji-picker hidden">
                <div class="emoji-grid">
                    <?php
                    $emojis = ["😀","😁","😂","🤣","😃","😄","😅","😆","😉","😊","😋","😎","😍","😘","😗","😙","😚",
                               "🙂","🤗","🤩","🤔","🤨","😐","😑","😶","🙄","😏","😣","😥","😮","🤐","😯","😪","😫",
                               "🥱","😴","😌","😛","😜","😝","🤤","😒","😓","😔","😕","🙃","🤑","😲","☹️","🙁","😖",
                               "😞","😟","😤","😢","😭","😦","😧","😨","😩","🤯","😬","😰","😱","🥵","🥶","😳","🤪",
                               "😵","😡","😠","🤬","😷","🤒","🤕","🤢","🤮","🤧","😇","🥳","🥺","🙏","👏","👍","👎","👋"];
                    foreach ($emojis as $emoji) {
                        echo "<span>$emoji</span>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    var lastMessageId = 0;
    var classLevel = '<?php echo $class_level_db; ?>';
    var isFirstLoad = true;

    $('#emoji-btn').on('click', function (e) {
        $('#emoji-picker').toggleClass('hidden');
        e.stopPropagation();
    });

    $('.emoji-grid').on('mousedown', 'span', function (e) {
        e.preventDefault();
        const emoji = $(this).text();
        $('#message-input').val($('#message-input').val() + emoji).focus();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#emoji-picker, #emoji-btn').length) {
            $('#emoji-picker').addClass('hidden');
        }
    });

    $('#send-btn').click(sendMessage);

    $('#message-input').keypress(function(e) {
        if (e.which === 13) sendMessage();
    });

    loadMessages();
    setInterval(checkNewMessages, 3000);

    function loadMessages() {
        $.ajax({
            url: '../siswa/chat_operations.php',
            type: 'GET',
            data: { operation: 'get_all_messages', class_level: classLevel },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#chat-messages').empty();
                    if (response.messages.length > 0) {
                        $.each(response.messages, function(index, message) {
                            appendMessage(message);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        });
                        scrollToBottom();
                    }
                    $('#loading-overlay').addClass('hidden');
                    isFirstLoad = false;
                }
            }
        });
    }

    function checkNewMessages() {
        if (isFirstLoad) return;
        $.ajax({
            url: '../siswa/chat_operations.php',
            type: 'GET',
            data: { operation: 'get_messages', class_level: classLevel, last_id: lastMessageId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.messages.length > 0) {
                    $.each(response.messages, function(index, message) {
                        appendMessage(message);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                    scrollToBottom();
                }
            }
        });
    }

    function sendMessage() {
        var messageText = $('#message-input').val().trim();
        if (messageText === '') return;
        $('#message-input').val('');
        $.ajax({
            url: '../siswa/chat_operations.php',
            type: 'POST',
            data: { operation: 'send_message', message: messageText, class_level: classLevel },
            dataType: 'json'
        });
    }

    function appendMessage(message) {
        var messageClass = message.is_self ? 'message-self' : 'message-other';
        var senderClass = message.sender_role === 'guru' ? 'teacher' : 'student';
        var time = new Date(message.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        var html = '<div class="message ' + messageClass + '">' +
                   '<div class="message-sender ' + senderClass + '">' + message.sender + '</div>' +
                   '<div class="message-text">' + message.message + '</div>' +
                   '<div class="message-time">' + time + '</div>' +
                   '</div>';
        $('#chat-messages').append(html);
    }

    function scrollToBottom() {
        var el = document.getElementById('chat-messages');
        el.scrollTop = el.scrollHeight;
    }
});
</script>
</body>
</html>