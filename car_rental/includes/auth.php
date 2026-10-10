<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_admin(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin']) ? $_SESSION['admin'] : null;
}

function require_admin(): void
{
    if (!current_admin()) {
        if (is_api_request()) {
            json_error('Admin access required', 401);
        }
        header('Location: login.php');
        exit;
    }
}

function is_api_request(): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, '/api/');
}

function login_admin(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT admin_id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    $_SESSION['admin'] = [
        'admin_id' => (int) $row['admin_id'],
        'username' => $row['username'],
    ];
    return true;
}

function logout_admin(): void
{
    unset($_SESSION['admin']);
}
