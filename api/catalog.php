<?php
/**
 * v1.2: ЕДИНЫЙ лёгкий каталог — названия + год релиза + производительность (для шкал).
 * Ответ сжимается gzip-ом (если включён mod_deflate), фронтенд кэширует его в
 * localStorage на 30 дней и обновляет в фоне. Полные характеристики подтягиваются
 * из api/details.php только когда модель добавляют в сравнение.
 *
 *   api/catalog.php?type=gpu -> [{"n":"GeForce RTX 5090","y":2025,"p":25000}, ...]
 *   api/catalog.php?type=cpu -> [{"n":"AMD Ryzen 7 9800X3D","y":2024,"p":40000}, ...]
 */
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
// Разрешаем браузеру/CDN кэшировать ответ 1 час (фронтенд и так держит копию 30 дней).
header('Cache-Control: public, max-age=3600');

$type = isset($_GET['type']) ? strtolower($_GET['type']) : 'gpu';
if (!in_array($type, ['gpu', 'cpu'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_type']);
    exit;
}

$pdo = db_connect();
try {
    if ($type === 'cpu') {
        $rows = $pdo->query('SELECT name, rel_year AS y, perf AS p FROM cpus ORDER BY name')->fetchAll();
    } else {
        // Год берём из card_release_date («03.03.2001» → 2001) прямо в SQL — дёшево.
        $rows = $pdo->query(
            "SELECT name AS n, CAST(RIGHT(card_release_date, 4) AS UNSIGNED) AS y, `Passmark G3D Mark` AS p
             FROM gpus ORDER BY name"
        )->fetchAll();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'query_failed', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$rows) {
    http_response_code(500);
    echo json_encode(['error' => 'empty_table', 'detail' => 'Таблица пуста. Импортируйте database/' . ($type === 'cpu' ? 'cpus' : 'gpus') . '_import.sql через phpMyAdmin.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
