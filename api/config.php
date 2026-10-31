<?php
/**
 * Настройки подключения к базе данных MySQL.
 * OpenServer (локально): host=localhost, user=root, пароль обычно пустой, база — создайте сами (например gpu_site).
 * Beget: хост localhost, имя БД/пользователя/пароль смотрите в панели Beget -> "Базы данных MySQL".
 */
return [
    'host' => 'localhost',
    'user' => 'root',            // на Beget замените на вашего пользователя (обычно логин_имя)
    'pass' => '',                // на Beget — пароль от базы из панели
    'db'   => 'gpu_site',        // имя базы (на Beget будет вида username_gpu_site)
    'charset' => 'utf8mb4',
];
