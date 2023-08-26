<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'dataSource' => $_ENV['DATASOURCE'],  // Cambiar a 'local' para usar datos locales
];
