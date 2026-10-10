<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    if (!empty($_GET['client_id'])) {
        $id = (int) $_GET['client_id'];
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE client_id=?');
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        if (!$client) {
            json_error('Client not found', 404);
        }
        $hist = $pdo->prepare(
            'SELECT r.*, c.brand, c.model, c.license_plate
             FROM rentals r JOIN cars c ON c.car_id = r.car_id
             WHERE r.client_id=? ORDER BY r.rental_id DESC'
        );
        $hist->execute([$id]);
        json_ok(['client' => $client, 'history' => $hist->fetchAll()]);
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM clients
             WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR driver_license LIKE ?
             ORDER BY client_id DESC'
        );
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like, $like, $like]);
        json_ok($stmt->fetchAll());
    }
    json_ok($pdo->query('SELECT * FROM clients ORDER BY client_id DESC')->fetchAll());
}

if ($method === 'POST') {
    $p = request_json();
    require_fields($p, ['first_name', 'last_name', 'phone', 'driver_license']);
    $stmt = $pdo->prepare(
        'INSERT INTO clients (first_name, last_name, phone, driver_license) VALUES (?,?,?,?)'
    );
    try {
        $stmt->execute([
            trim((string) $p['first_name']),
            trim((string) $p['last_name']),
            trim((string) $p['phone']),
            trim((string) $p['driver_license']),
        ]);
    } catch (PDOException $e) {
        json_error('Driver license already exists', 409);
    }
    json_ok(['client_id' => (int) $pdo->lastInsertId()], 'Saved');
}

if ($method === 'PUT') {
    require_admin();
    $p = request_json();
    require_fields($p, ['client_id', 'first_name', 'last_name', 'phone', 'driver_license']);
    $stmt = $pdo->prepare(
        'UPDATE clients SET first_name=?, last_name=?, phone=?, driver_license=? WHERE client_id=?'
    );
    $stmt->execute([
        trim((string) $p['first_name']),
        trim((string) $p['last_name']),
        trim((string) $p['phone']),
        trim((string) $p['driver_license']),
        (int) $p['client_id'],
    ]);
    json_ok(null, 'Saved');
}

if ($method === 'DELETE') {
    require_admin();
    $p = request_json();
    $id = (int) ($p['client_id'] ?? $_GET['client_id'] ?? 0);
    if ($id <= 0) {
        json_error('client_id required');
    }
    try {
        $pdo->prepare('DELETE FROM clients WHERE client_id=?')->execute([$id]);
    } catch (PDOException $e) {
        json_error('Cannot delete: client has rentals', 409);
    }
    json_ok(null, 'Deleted');
}

json_error('Method not allowed', 405);
