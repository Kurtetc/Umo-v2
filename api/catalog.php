<?php
/**
 * v1.2: ЕДИНЫЙ лёгкий каталог — названия + год релиза + производительность (для шкал).
 * Ответ сжимается gzip-ом (если включён mod_deflate), фронтенд кэширует его в
 * localStorage на 30 дней и обновляет в фоне. Полные характеристики подтягиваются
 * из api/details.php только когда модель добавляют в сравнение.
 *
 *   api/catalog.php?type=gpu -> {"v":"<etag>","b":[["id","name","brand","year","perf"], ...]}
 * Ответ — компактные массивы (не объекты): ~в 3 раза меньше байт, чем JSON с ключами.
 * Фронтенд кэширует его в localStorage на 30 дней; ETag позволяет серверу отвечать
 * «304 Not Modified» за миллисекунды без чтения БД.
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

/* ---- Серверный файловый кэш ответа + ETag ------------------------------
   Если файл кэша свежий (моложе TTL), БД вообще не опрашивается: отдаём
   готовый JSON, а при совпадении If-None-Match — пустой 304. Это делает
   повторную загрузку страницы мгновенной даже на медленном MySQL. */
$CACHE_TTL = 6 * 3600; // перечитывать БД не чаще, чем раз в 6 часов
$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/catalog_' . $type . '.json';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
$cachedJson = null;
if (is_readable($cacheFile) && (time() - (int)@filemtime($cacheFile)) < $CACHE_TTL) {
    $cachedJson = @file_get_contents($cacheFile);
}
if ($cachedJson !== null && $cachedJson !== false && $cachedJson !== '') {
    $etag = '"' . md5($cachedJson) . '"';
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }
    echo $cachedJson;
    exit;
}

$pdo = db_connect();
$rows = [];
try {
    if ($type === 'cpu') {
        $stmt = $pdo->query('SELECT id, name, brand, rel_year, perf FROM cpus ORDER BY name');
        while (($r = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            $rows[] = [$r[0], $r[1], $r[2], $r[3] === null ? '' : (string)$r[3], $r[4] === null ? '' : (string)$r[4]];
        }
    } else {
        // Год берём из card_release_date («03.03.2001» → 2001) прямо в SQL — дёшево.
        // Бренд — из vendor («nvd» → NVIDIA и т.п.), производительность — Passmark G3D Mark.
        $stmt = $pdo->query(
            "SELECT id, name,
                    CASE WHEN LOWER(vendor) LIKE '%amd%' OR LOWER(vendor) LIKE '%radeon%' THEN 'AMD'
                         WHEN LOWER(vendor) LIKE '%intel%' OR LOWER(vendor) LIKE '%arc%' THEN 'INTEL'
                         ELSE 'NVIDIA' END AS brand,
                    COALESCE(NULLIF(CAST(RIGHT(card_release_date, 4) AS UNSIGNED),0),'') AS y,
                    COALESCE(`Passmark G3D Mark`,'') AS p
             FROM gpus ORDER BY name"
        );
        while (($r = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
            $rows[] = [$r[0], $r[1], $r[2], (string)$r[3], (string)$r[4]];
        }
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
$json = json_encode(['v' => md5(serialize($rows)), 'b' => $rows], JSON_UNESCAPED_UNICODE);
@file_put_contents($cacheFile, $json, LOCK_EX); // если папка api не на запись — просто не кэшируем
$etag = '"' . md5($json) . '"';
header('ETag: ' . $etag);
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}
echo $json;
