<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    if (!empty($_GET['payment_id'])) {
        $stmt = $pdo->prepare(
            'SELECT p.*, r.start_date, r.end_date, r.total_amount AS rental_total, r.status AS rental_status,
                    CONCAT(cl.first_name, " ", cl.last_name) AS client_name,
                    cl.phone, cl.driver_license,
                    CONCAT(c.brand, " ", c.model) AS car_name, c.license_plate
             FROM payments p
             JOIN rentals r ON r.rental_id = p.rental_id
             JOIN clients cl ON cl.client_id = r.client_id
             JOIN cars c ON c.car_id = r.car_id
             WHERE p.payment_id=?'
        );
        $stmt->execute([(int) $_GET['payment_id']]);
        $row = $stmt->fetch();
        if (!$row) {
            json_error('Payment not found', 404);
        }
        json_ok($row);
    }
    $rows = $pdo->query(
        'SELECT p.*, CONCAT(cl.first_name, " ", cl.last_name) AS client_name,
                CONCAT(c.brand, " ", c.model) AS car_name
         FROM payments p
         JOIN rentals r ON r.rental_id = p.rental_id
         JOIN clients cl ON cl.client_id = r.client_id
         JOIN cars c ON c.car_id = r.car_id
         ORDER BY p.payment_id DESC'
    )->fetchAll();
    json_ok($rows);
}

if ($method === 'POST') {
    $p = request_json();
    require_fields($p, ['rental_id', 'amount', 'payment_method']);
    $methodPay = $p['payment_method'];
    if (!in_array($methodPay, ['card', 'cash', 'transfer'], true)) {
        json_error('Invalid payment method');
    }
    $amount = (float) $p['amount'];
    if ($amount <= 0) {
        json_error('Amount must be positive');
    }
    $exists = $pdo->prepare('SELECT rental_id FROM rentals WHERE rental_id=?');
    $exists->execute([(int) $p['rental_id']]);
    if (!$exists->fetch()) {
        json_error('Rental not found', 404);
    }
    $date = $p['payment_date'] ?? date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        'INSERT INTO payments (rental_id, payment_date, amount, payment_method) VALUES (?,?,?,?)'
    );
    $stmt->execute([(int) $p['rental_id'], $date, $amount, $methodPay]);
    json_ok(['payment_id' => (int) $pdo->lastInsertId()], 'Paid');
}

if ($method === 'PUT') {
    require_admin();
    $p = request_json();
    require_fields($p, ['payment_id', 'rental_id', 'amount', 'payment_method', 'payment_date']);
    if (!in_array($p['payment_method'], ['card', 'cash', 'transfer'], true)) {
        json_error('Invalid payment method');
    }
    $stmt = $pdo->prepare(
        'UPDATE payments SET rental_id=?, payment_date=?, amount=?, payment_method=? WHERE payment_id=?'
    );
    $stmt->execute([
        (int) $p['rental_id'],
        $p['payment_date'],
        (float) $p['amount'],
        $p['payment_method'],
        (int) $p['payment_id'],
    ]);
    json_ok(null, 'Saved');
}

if ($method === 'DELETE') {
    require_admin();
    $p = request_json();
    $id = (int) ($p['payment_id'] ?? $_GET['payment_id'] ?? 0);
    if ($id <= 0) {
        json_error('payment_id required');
    }
    $pdo->prepare('DELETE FROM payments WHERE payment_id=?')->execute([$id]);
    json_ok(null, 'Deleted');
}

json_error('Method not allowed', 405);
