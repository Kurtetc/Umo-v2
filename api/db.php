<?php
/** Общее подключение к MySQL. Ошибки выводятся как JSON с кодом 500 для понятной диагностики во фронтенде. */
function db_connect() {
    $cfg = require __DIR__ . '/config.php';
    try {
        $pdo = new PDO(
            'mysql:host=' . $cfg['host'] . ';dbname=' . $cfg['db'] . ';charset=' . $cfg['charset'],
            $cfg['user'],
            $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'db_connect_failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
