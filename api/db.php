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
        echo json_encode([
            'error' => 'db_connect_failed',
            'detail' => $e->getMessage(),
            'hint' => '1) В панели OpenServer убедитесь, что МОДУЛЬ MySQL запущен (зелёный индикатор; если он остановлен — нажмите "Запустить"). 2) Если MySQL запущен, но ошибка остаётся — скорее всего у вас ДРУГОЙ порт: откройте Консоль -> netstat -ano | findstr 3306, посмотрите, на каком порту слушает mysqld (у некоторых сборки OpenServer используют 3307/3308), и пропишите его в api/config.php: host => "127.0.0.1:3307". 3) Проверьте user/pass в api/config.php (OpenServer по умолчанию: root / пустой пароль). 4) Убедитесь, что правите config.php именно того домена, который открыт в браузере (папка domains/mysite2/api/config.php), а не копию из другого домена.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
/** То же, но бросает исключение наружу (для режима ?diag=1). */
function db_connect_silent() {
    $cfg = require __DIR__ . '/config.php';
    return db_pdo($cfg);
}
