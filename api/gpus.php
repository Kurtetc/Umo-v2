<?php
/**
 * Отдаёт весь каталог видеокарт из таблицы gpus в виде JSON-массива.
 * Фронтенд (js/app.js) обращается только сюда — это единственный источник данных.
 */
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = db_connect();
$rows = $pdo->query('SELECT * FROM gpus')->fetchAll();
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
