<?php
// index.php — ГЛАВНЫЙ СТРАЖ КЛЮКВАГРАМА
// Кладётся в /public_html/api/index.php
// Все запросы идут через него → он решает, кто живёт, а кто — нет

// === НАСТРОЙКИ ===
$VALID_ENDPOINTS = [
    'register.php',
    'login.php',
    'send_message.php',
    'get_messages.php',
    'get_chats.php',
    'search_users.php',
    'config.php'
];

$REQUEST_URI = $_SERVER['REQUEST_URI'];
$SCRIPT_NAME = $_SERVER['SCRIPT_NAME'];
$PATH_INFO = $_SERVER['PATH_INFO'] ?? '';
$QUERY_STRING = $_SERVER['QUERY_STRING'] ?? '';

// Определяем, какой файл хочет юзер
$requested_file = '';
if ($PATH_INFO) {
    $requested_file = ltrim($PATH_INFO, '/');
} else {
    // Если типа /api/login.php → берём имя файла
    $base = basename($SCRIPT_NAME);
    if ($base !== 'index.php' && in_array($base, $VALID_ENDPOINTS)) {
        $requested_file = $base;
    }
}

// Если ничего не запрошено — покажем статус
if (empty($requested_file)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'KLYUKVA_GOD_IS_ALIVE',
        'time' => date('H:i:s'),
        'endpoints' => $VALID_ENDPOINTS,
        'message' => 'Всё работает. Клюкваграм бессмертен.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Проверяем, есть ли файл и валиден ли он
$full_path = __DIR__ . '/' . $requested_file;

if (!in_array($requested_file, $VALID_ENDPOINTS)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Endpoint not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!file_exists($full_path)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "Файл $requested_file отсутствует на сервере"], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_readable($full_path)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "Файл $requested_file недоступен для чтения"], JSON_UNESCAPED_UNICODE);
    exit;
}

// === МЫ ПОДМЕНЯЕМ СЕБЯ НА НУЖНЫЙ ФАЙЛ, НО ЛОВИМ ВСЁ ===
ob_start();
$has_error = false;
$error_message = '';

set_error_handler(function($severity, $message, $file, $line) use (&$has_error, &$error_message) {
    $has_error = true;
    $error_message = "PHP Error: $message in $file:$line";
    return true;
});

set_exception_handler(function($exception) use (&$has_error, &$error_message) {
    $has_error = true;
    $error_message = "Exception: " . $exception->getMessage();
});

register_shutdown_function(function() use (&$has_error, &$error_message, $requested_file) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $has_error = true;
        $error_message = "Fatal Error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line'];
    }

    if ($has_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => "Серверный сбой в $requested_file",
            'debug' => $error_message,
            'protected_by' => 'KLYUKVA_GOD'
        ], JSON_UNESCAPED_UNICODE);
    }
});

try {
    // Включаем целевой файл
    require $full_path;
} catch (Throwable $e) {
    $has_error = true;
    $error_message = $e->getMessage();
}

// Если дошли сюда и ошибка — выводим
if ($has_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => "Критическая ошибка в $requested_file",
        'protected_by' => 'KLYUKVA_GOD'
    ], JSON_UNESCAPED_UNICODE);
}

// Если всё ок — выводим то, что накопилось в буфере
$output = ob_get_clean();
if (!empty($output)) {
    echo $output;
}