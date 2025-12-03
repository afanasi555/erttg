<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';
$password = $input['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Пустые поля']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password, nickname FROM users WHERE login = ? LIMIT 1");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => true,
            'user_id' => (int)$user['id'],
            'nickname' => $user['nickname'] ?? $login,
            'avatar' => ''
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Сервер устал']);
}
?>