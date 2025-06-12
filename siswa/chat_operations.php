<?php
session_start();
include '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$query = "SELECT id, role FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
    $user_role = $row['role'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['operation']) && $_POST['operation'] === 'send_message') {
    $message = trim($_POST['message']);
    $class_level = strtolower($_POST['class_level']);
    
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
        exit;
    }
    
    $query = "INSERT INTO chat_messages (sender_id, message, class_level) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $message, $class_level);
    
    if (mysqli_stmt_execute($stmt)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['operation']) && $_GET['operation'] === 'get_messages') {
    $class_level = strtolower($_GET['class_level']);
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    $query = "
        SELECT cm.id, cm.message, cm.sent_at, u.username as sender_name, u.role as sender_role
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.class_level = ? AND cm.id > ?
        ORDER BY cm.sent_at ASC
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $class_level, $last_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender_name'],
            'message' => $row['message'],
            'is_self' => ($row['sender_name'] === $username),
            'sent_at' => $row['sent_at'],
            'sender_role' => $row['sender_role']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['operation']) && $_GET['operation'] === 'get_all_messages') {
    $class_level = strtolower($_GET['class_level']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    $query = "
        SELECT cm.id, cm.message, cm.sent_at, u.username as sender_name, u.role as sender_role
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.class_level = ?
        ORDER BY cm.sent_at DESC
        LIMIT ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $class_level, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender_name'],
            'message' => $row['message'],
            'is_self' => ($row['sender_name'] === $username),
            'sent_at' => $row['sent_at'],
            'sender_role' => $row['sender_role']
        ];
    }
    
    $messages = array_reverse($messages);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}


header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid operation']);
exit; 