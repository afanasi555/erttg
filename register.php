<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$login = $input['login'] ?? '';
$password = $input['password'] ?? '';
$username = $input['username'] ?? ("@" . $login);
$nickname = $input['nickname'] ?? $login;

if (strlen($login) < 4 || strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Слишком коротко']);
    exit;
}

if (empty($login) || empty($password) || empty($nickname)) {
    echo json_encode(['success' => false, 'error' => 'Заполни всё']);
    exit;
}

try {
    // Проверка — есть ли уже такой логин
    $check = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    $check->execute([$login]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Логин занят']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (login, password, username, nickname, registered_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$login, $hash, $username, $nickname]);

    $userId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'user_id' => (int)$userId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Регистрация сломалась']);
}
?>