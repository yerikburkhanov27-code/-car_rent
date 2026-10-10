<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    json_ok($pdo->query('SELECT * FROM employees ORDER BY employee_id')->fetchAll());
}

if ($method === 'POST') {
    require_admin();
    $p = request_json();
    require_fields($p, ['first_name', 'last_name', 'position', 'phone']);
    $stmt = $pdo->prepare('INSERT INTO employees (first_name, last_name, position, phone) VALUES (?,?,?,?)');
    $stmt->execute([
        trim((string) $p['first_name']),
        trim((string) $p['last_name']),
        trim((string) $p['position']),
        trim((string) $p['phone']),
    ]);
    json_ok(['employee_id' => (int) $pdo->lastInsertId()], 'Saved');
}

if ($method === 'PUT') {
    require_admin();
    $p = request_json();
    require_fields($p, ['employee_id', 'first_name', 'last_name', 'position', 'phone']);
    $stmt = $pdo->prepare(
        'UPDATE employees SET first_name=?, last_name=?, position=?, phone=? WHERE employee_id=?'
    );
    $stmt->execute([
        trim((string) $p['first_name']),
        trim((string) $p['last_name']),
        trim((string) $p['position']),
        trim((string) $p['phone']),
        (int) $p['employee_id'],
    ]);
    json_ok(null, 'Saved');
}

if ($method === 'DELETE') {
    require_admin();
    $p = request_json();
    $id = (int) ($p['employee_id'] ?? $_GET['employee_id'] ?? 0);
    if ($id <= 0) {
        json_error('employee_id required');
    }
    try {
        $pdo->prepare('DELETE FROM employees WHERE employee_id=?')->execute([$id]);
    } catch (PDOException $e) {
        json_error('Cannot delete: employee is used in rentals', 409);
    }
    json_ok(null, 'Deleted');
}

json_error('Method not allowed', 405);
