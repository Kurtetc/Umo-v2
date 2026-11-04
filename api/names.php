<?php
/**
 * v1.1: отдаёт ТОЛЬКО список названий (id + name) — лёгкий ответ для мгновенной
 * загрузки страницы и списков поиска. Полные характеристики подтягиваются
 * отдельным запросом api/details.php при выборе модели для сравнения.
 *
 *   api/names.php?type=gpu  ->  [{"id":1,"name":"GeForce RTX 5090"}, ...]
 *   api/names.php?type=cpu  ->  [{"id":1,"name":"AMD Ryzen 7 9800X3D"}, ...]
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
    $rows = $pdo->query("SELECT id, name FROM {$table} ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'detail' => "Не удалось прочитать таблицу {$table}: " . $e->getMessage() . '. Импортируйте database/' . $table . '_import.sql через phpMyAdmin.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$rows) {
    http_response_code(500);
    echo json_encode(['error' => 'empty_table', 'detail' => "Таблица {$table} пуста. Импортируйте database/{$table}_import.sql через phpMyAdmin."], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
