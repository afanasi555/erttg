<?php
$host = 'localhost';
$db   = 'u3330627_klyukva';        // ← ТВОЯ БАЗА ДАННЫХ
$user = 'u3330627_klyukva';        // ← ТВОЙ ЛОГИН
$pass = '3VBzQkN63H4q3ngw';     // ← ВСТАВЬ СВОЙ ПАРОЛЬ

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

?>
