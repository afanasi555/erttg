<?php
require 'config.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT username, nickname, avatar FROM users WHERE username LIKE ? LIMIT 20");
$stmt->execute(["%$q%"]);
$users = $stmt->fetchAll();

echo json_encode(array_map(function($u) {
    return [
        'username' => $u['username'],
        'nickname' => $u['nickname'],
        'avatar' => $u['avatar']
    ];
}, $users));
?>
