<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    $sql = 'SELECT * FROM cars WHERE 1=1';
    $params = [];
    if (!empty($_GET['status']) && in_array($_GET['status'], ['available', 'rented'], true)) {
        $sql .= ' AND status = ?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['brand'])) {
        $sql .= ' AND brand = ?';
        $params[] = $_GET['brand'];
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $sql .= ' AND daily_rate <= ?';
        $params[] = (float) $_GET['max_price'];
    }
    if (!empty($_GET['q'])) {
        $sql .= ' AND (brand LIKE ? OR model LIKE ? OR license_plate LIKE ?)';
        $q = '%' . $_GET['q'] . '%';
        array_push($params, $q, $q, $q);
    }
    $sql .= ' ORDER BY brand, model';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();
    $brands = $pdo->query('SELECT DISTINCT brand FROM cars ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
    json_ok(['cars' => $cars, 'brands' => $brands]);
}

if ($method === 'POST') {
    require_admin();
    $p = request_json();
    require_fields($p, ['brand', 'model', 'license_plate', 'daily_rate']);
    $status = in_array($p['status'] ?? 'available', ['available', 'rented'], true) ? $p['status'] : 'available';
    $stmt = $pdo->prepare('INSERT INTO cars (brand, model, license_plate, daily_rate, status) VALUES (?,?,?,?,?)');
    $stmt->execute([
        trim((string) $p['brand']),
        trim((string) $p['model']),
        trim((string) $p['license_plate']),
        (float) $p['daily_rate'],
        $status,
    ]);
    json_ok(['car_id' => (int) $pdo->lastInsertId()], 'Saved');
}

if ($method === 'PUT') {
    require_admin();
    $p = request_json();
    require_fields($p, ['car_id', 'brand', 'model', 'license_plate', 'daily_rate', 'status']);
    if (!in_array($p['status'], ['available', 'rented'], true)) {
        json_error('Invalid status');
    }
    $stmt = $pdo->prepare(
        'UPDATE cars SET brand=?, model=?, license_plate=?, daily_rate=?, status=? WHERE car_id=?'
    );
    $stmt->execute([
        trim((string) $p['brand']),
        trim((string) $p['model']),
        trim((string) $p['license_plate']),
        (float) $p['daily_rate'],
        $p['status'],
        (int) $p['car_id'],
    ]);
    json_ok(null, 'Saved');
}

if ($method === 'DELETE') {
    require_admin();
    $p = request_json();
    $id = (int) ($p['car_id'] ?? $_GET['car_id'] ?? 0);
    if ($id <= 0) {
        json_error('car_id required');
    }
    $stmt = $pdo->prepare('DELETE FROM cars WHERE car_id=?');
    try {
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        json_error('Cannot delete: car is used in rentals', 409);
    }
    json_ok(null, 'Deleted');
}

json_error('Method not allowed', 405);
