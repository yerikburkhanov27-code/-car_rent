<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

if (request_method() !== 'POST') {
    json_error('Method not allowed', 405);
}

$payload = request_json();
$username = trim((string) ($payload['username'] ?? ''));
$password = (string) ($payload['password'] ?? '');
if ($username === '' || $password === '') {
    json_error('Username and password required');
}
if (!login_admin($username, $password)) {
    json_error('Invalid credentials', 401);
}
json_ok(current_admin(), 'Logged in');
