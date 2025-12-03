<?php
require 'config.php';

$user_id = $_GET['user_id'] ?? 0;
$with = $_GET['with'] ?? '';

if (!$user_id || !$with) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$with]);
$other = $stmt->fetch();
if (!$other) { echo json_encode([]); exit; }

$other_id = $other['id'];
$stmt = $pdo->prepare("SELECT id FROM chats WHERE (user1_id IN (?,?) AND user2_id IN (?,?))");
$stmt->execute([$user_id, $other_id, $user_id, $other_id]);
$chat = $stmt->fetch();
if (!$chat) { echo json_encode([]); exit; }

$chat_id = $chat['id'];
$stmt = $pdo->prepare("SELECT sender_id, message, DATE_FORMAT(sent_at, '%H:%i') as time FROM messages WHERE chat_id = ? ORDER BY sent_at");
$stmt->execute([$chat_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
