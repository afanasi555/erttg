<?php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['sender_id']) || empty($data['receiver_username']) || empty($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$sender_id = (int)$data['sender_id'];
$receiver_username = trim($data['receiver_username']);
$message = trim($data['message']);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$receiver_username]);
$receiver = $stmt->fetch();
if (!$receiver) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$receiver_id = $receiver['id'];

// Ищем или создаём чат
$stmt = $pdo->prepare("SELECT id FROM chats WHERE (user1_id IN (?, ?) AND user2_id IN (?, ?))");
$stmt->execute([$sender_id, $receiver_id, $sender_id, $receiver_id]);
$chat = $stmt->fetch();

if (!$chat) {
    $stmt = $pdo->prepare("INSERT INTO chats (user1_id, user2_id) VALUES (?, ?)");
    $stmt->execute([$sender_id, $receiver_id]);
    $chat_id = $pdo->lastInsertId();
} else {
    $chat_id = $chat['id'];
}

// Сохраняем сообщение
$stmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, message) VALUES (?, ?, ?)");
$stmt->execute([$chat_id, $sender_id, $message]);

echo json_encode(['success' => true]);
?>
