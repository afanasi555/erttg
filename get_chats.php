<?php
require 'config.php';

$user_id = $_GET['user_id'] ?? 0;
if (!$user_id) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
    SELECT u.username, u.nickname, u.avatar, m.message as last_msg, m.sent_at
    FROM chats c
    JOIN users u ON u.id IN (c.user1_id, c.user2_id) AND u.id != ?
    LEFT JOIN messages m ON m.chat_id = c.id
    LEFT JOIN (SELECT chat_id, MAX(sent_at) as mt FROM messages GROUP BY chat_id) lm ON lm.chat_id = c.id AND m.sent_at = lm.mt
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY lm.mt DESC NULLS LAST
");
$stmt->execute([$user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll();

$result = [];
foreach ($chats as $c) {
    $result[] = [
        'username' => $c['username'],
        'nickname' => $c['nickname'],
        'avatar' => $c['avatar'],
        'last_message' => $c['last_msg'] ?? '',
        'time' => $c['sent_at'] ? date('H:i', strtotime($c['sent_at'])) : ''
    ];
}
echo json_encode($result);
?>
