<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function require_fields(array $payload, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($payload[$field]) || $payload[$field] === '') {
            json_error("Missing field: {$field}");
        }
    }
}
