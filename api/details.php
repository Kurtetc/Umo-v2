<?php
/**
 * v1.1: отдаёт ПОЛНУЮ запись (все характеристики) по id или названию.
 * Вызывается фронтендом только когда пользователь добавляет модель в сравнение.
 *
 *   api/details.php?type=gpu&id=123          -> { "id":123, ...все поля... }
 *   api/details.php?type=cpu&name=Ryzen 7    -> [ {...}, {...} ]  (по точному совпаданию имени)
 */
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'gpu';
if (!in_array($type, ['gpu', 'cpu'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_type', 'detail' => 'Параметр type должен быть gpu или cpu'], JSON_UNESCAPED_UNICODE);
    exit;
}
$table = $type === 'cpu' ? 'cpus' : 'gpus';
$pdo = db_connect();

try {
    if (isset($_GET['id']) && $_GET['id'] !== '') {
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
        $st->execute([$_GET['id']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'detail' => "Запись с id={$_GET['id']} в таблице {$table} не найдена"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($row, JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['name']) && trim($_GET['name']) !== '') {
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE name = ?");
        $st->execute([trim($_GET['name'])]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'detail' => "Модель «{$_GET['name']}» в таблице {$table} не найдена"], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'bad_request', 'detail' => 'Нужен параметр id или name'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
