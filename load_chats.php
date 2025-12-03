<?php
require_once 'db.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(["error" => "invalid user_id"]);
    exit;
}

try {
    // Получаем все чаты пользователя
    $stmt = $pdo->prepare("
        SELECT 
            CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END as partner_id
        FROM chats 
        WHERE user1_id = ? OR user2_id = ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $chats = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Получаем все сообщения
    $messages = [];
    if (!empty($chats)) {
        $in = str_repeat('?,', count($chats) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT c.id as chat_id, m.sender_id, m.message
            FROM messages m
            JOIN chats c ON m.chat_id = c.id
            WHERE (c.user1_id = ? AND c.user2_id IN ($in)) 
               OR (c.user2_id = ? AND c.user1_id IN ($in))
            ORDER BY m.sent_at
        ");
        $params = array_merge([$user_id], $chats, [$user_id], $chats);
        $stmt->execute($params);

        while ($row = $stmt->fetch()) {
            $partner = ($row['sender_id'] == $user_id) ? 
                ($row['chat_id'] == $user_id ? $chats[0] : "другой") : 
                $row['sender_id'];

            $prefix = ($row['sender_id'] == $user_id) ? "Я: " : "$partner: ";
            $messages[$partner][] = $prefix . $row['message'];
        }
    }

    echo json_encode([
        "user_id" => $user_id,
        "active_chats" => $chats,
        "messages" => $messages
    ]);

} catch (Exception $e) {
    error_log("LOAD CHATS ERROR: " . $e->getMessage());
    echo json_encode(["error" => "load failed"]);
}
?>