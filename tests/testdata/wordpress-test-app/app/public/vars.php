<?php

header("Content-Type: application/json");

$vars = [
    '_SERVER' => $_SERVER,
    '_GET' => $_GET,
    '_ENV' => $_ENV
];

echo json_encode($vars, JSON_PRETTY_PRINT) . PHP_EOL;
