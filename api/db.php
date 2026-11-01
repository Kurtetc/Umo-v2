<?php
/** Общее подключение к MySQL. Ошибки выводятся как JSON с кодом 500 для понятной диагностики во фронтенде. */
function db_pdo(array $cfg) {
    return new PDO(
        'mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['db'] . ';charset=' . $cfg['charset'],
        $cfg['user'],
        $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}
function db_connect() {
    $cfg = require __DIR__ . '/config.php';
    try {
        return db_pdo($cfg);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'db_connect_failed', 'detail' => $e->getMessage() . '. Проверьте настройки в api/config.php (хост, пользователь, пароль, имя базы). На OpenServer по умолчанию: root / пустой пароль.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
/** То же, но бросает исключение наружу (для режима ?diag=1). */
function db_connect_silent() {
    $cfg = require __DIR__ . '/config.php';
    return db_pdo($cfg);
}
