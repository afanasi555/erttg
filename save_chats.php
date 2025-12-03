<?php
require_once 'db.php'; // твой подключатель

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['user_id']) || $data['user_id'] <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid user_id"]);
    exit;
}

$user_id = (int)$data['user_id'];
$active_chats = $data['active_chats'] ?? [];
$messages = $data['messages'] ?? [];

try {
    $pdo->beginTransaction();

    // 1. Очищаем старые чаты пользователя
    $stmt = $pdo->prepare("DELETE FROM chats WHERE user1_id = ? OR user2_id = ?");
    $stmt->execute([$user_id, $user_id]);

    // 2. Добавляем новые чаты
    $insertChat = $pdo->prepare("INSERT INTO chats (user1_id, user2_id, created_at) VALUES (?, ?, NOW())");
    foreach ($active_chats as $chat_user) {
        $other_id = $chat_user; // у тебя в базе user2_id — это ID собеседника
        $insertChat->execute([$user_id, $other_id]);
    }

    // 3. Сохраняем сообщения
    if (!empty($messages)) {
        $insertMsg = $pdo->prepare("
            INSERT INTO messages (chat_id, sender_id, message, sent_at) 
            VALUES (
                (SELECT id FROM chats WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)), 
                ?, ?, NOW()
            )
        ");

        foreach ($messages as $chat_user => $msgList) {
            foreach ($msgList as $msg) {
                // Парсим: "Я: текст" или "Анна Асти: блюю"
                $isMine = str_starts_with($msg, "Я: ");
                $sender = $isMine ? $user_id : $chat_user;
                $text = trim(substr($msg, strpos($msg, ": ") + 2));

                $insertMsg->execute([$user_id, $chat_user, $chat_user, $user_id, $sender, $text]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "saved_chats" => count($active_chats)]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("SAVE CHATS ERROR: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "save failed"]);
}
?>