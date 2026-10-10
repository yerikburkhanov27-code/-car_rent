<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
require_admin();

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    json_ok($pdo->query('SELECT admin_id, username, created_at FROM admins ORDER BY admin_id')->fetchAll());
}

if ($method === 'POST') {
    $p = request_json();
    require_fields($p, ['username', 'password']);
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?,?)');
    try {
        $stmt->execute([trim((string) $p['username']), password_hash((string) $p['password'], PASSWORD_DEFAULT)]);
    } catch (PDOException $e) {
        json_error('Username already exists', 409);
    }
    json_ok(['admin_id' => (int) $pdo->lastInsertId()], 'Saved');
}

if ($method === 'PUT') {
    $p = request_json();
    require_fields($p, ['admin_id', 'username']);
    if (!empty($p['password'])) {
        $stmt = $pdo->prepare('UPDATE admins SET username=?, password_hash=? WHERE admin_id=?');
        $stmt->execute([
            trim((string) $p['username']),
            password_hash((string) $p['password'], PASSWORD_DEFAULT),
            (int) $p['admin_id'],
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE admins SET username=? WHERE admin_id=?');
        $stmt->execute([trim((string) $p['username']), (int) $p['admin_id']]);
    }
    json_ok(null, 'Saved');
}

if ($method === 'DELETE') {
    $p = request_json();
    $id = (int) ($p['admin_id'] ?? $_GET['admin_id'] ?? 0);
    if ($id <= 0) {
        json_error('admin_id required');
    }
    $me = current_admin();
    if ($me && (int) $me['admin_id'] === $id) {
        json_error('Cannot delete the signed-in admin');
    }
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count <= 1) {
        json_error('Cannot delete the last admin');
    }
    $pdo->prepare('DELETE FROM admins WHERE admin_id=?')->execute([$id]);
    json_ok(null, 'Deleted');
}

json_error('Method not allowed', 405);
