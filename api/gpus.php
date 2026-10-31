<?php
/**
 * Отдаёт весь каталог видеокарт из таблицы gpus в виде JSON-массива.
 * Фронтенд (js/app.js) сначала обращается сюда; если PHP/MySQL недоступны,
 * он автоматически читает bd.csv напрямую.
 */
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_connect();
$rows = $pdo->query('SELECT * FROM gpus')->fetchAll();
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
