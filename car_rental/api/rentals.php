<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$method = request_method();
$pdo = db();

if ($method === 'GET') {
    $sql = 'SELECT r.*,
                   CONCAT(cl.first_name, " ", cl.last_name) AS client_name,
                   CONCAT(c.brand, " ", c.model) AS car_name,
                   c.license_plate,
                   c.daily_rate,
                   CONCAT(e.first_name, " ", e.last_name) AS employee_name
            FROM rentals r
            JOIN clients cl ON cl.client_id = r.client_id
            JOIN cars c ON c.car_id = r.car_id
            JOIN employees e ON e.employee_id = r.employee_id
            WHERE 1=1';
    $params = [];
    if (!empty($_GET['status'])) {
        $sql .= ' AND r.status = ?';
        $params[] = $_GET['status'];
    }
    $sql .= ' ORDER BY r.rental_id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_ok($stmt->fetchAll());
}

if ($method === 'POST') {
    $p = request_json();
    require_fields($p, ['car_id', 'start_date', 'end_date']);

    $pdo->beginTransaction();
    try {
        $clientId = (int) ($p['client_id'] ?? 0);
        if ($clientId <= 0) {
            require_fields($p, ['first_name', 'last_name', 'phone', 'driver_license']);
            $ins = $pdo->prepare(
                'INSERT INTO clients (first_name, last_name, phone, driver_license) VALUES (?,?,?,?)'
            );
            $ins->execute([
                trim((string) $p['first_name']),
                trim((string) $p['last_name']),
                trim((string) $p['phone']),
                trim((string) $p['driver_license']),
            ]);
            $clientId = (int) $pdo->lastInsertId();
        }

        $employeeId = (int) ($p['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            $employeeId = (int) $pdo->query('SELECT employee_id FROM employees ORDER BY employee_id LIMIT 1')->fetchColumn();
        }
        if ($employeeId <= 0) {
            throw new RuntimeException('No employee available');
        }

        $carStmt = $pdo->prepare('SELECT * FROM cars WHERE car_id=? FOR UPDATE');
        $carStmt->execute([(int) $p['car_id']]);
        $car = $carStmt->fetch();
        if (!$car) {
            throw new RuntimeException('Car not found');
        }
        if ($car['status'] !== 'available') {
            throw new RuntimeException('Car is not available');
        }

        $days = rental_days((string) $p['start_date'], (string) $p['end_date']);
        $total = round($days * (float) $car['daily_rate'], 2);

        $insR = $pdo->prepare(
            'INSERT INTO rentals (client_id, car_id, employee_id, start_date, end_date, total_amount, status)
             VALUES (?,?,?,?,?,?,?)'
        );
        $insR->execute([
            $clientId,
            (int) $car['car_id'],
            $employeeId,
            $p['start_date'],
            $p['end_date'],
            $total,
            'active',
        ]);
        $rentalId = (int) $pdo->lastInsertId();
        $pdo->prepare("UPDATE cars SET status='rented' WHERE car_id=?")->execute([(int) $car['car_id']]);
        $pdo->commit();
        json_ok(['rental_id' => $rentalId, 'total_amount' => $total, 'days' => $days], 'Booked');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_error($e->getMessage(), 400);
    }
}

if ($method === 'PUT') {
    $p = request_json();
    require_fields($p, ['rental_id']);
    $id = (int) $p['rental_id'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM rentals WHERE rental_id=? FOR UPDATE');
        $stmt->execute([$id]);
        $rental = $stmt->fetch();
        if (!$rental) {
            throw new RuntimeException('Rental not found');
        }

        if (!empty($p['status']) && in_array($p['status'], ['active', 'completed', 'cancelled'], true)) {
            $pdo->prepare('UPDATE rentals SET status=? WHERE rental_id=?')->execute([$p['status'], $id]);
            if (in_array($p['status'], ['completed', 'cancelled'], true)) {
                $pdo->prepare("UPDATE cars SET status='available' WHERE car_id=?")->execute([(int) $rental['car_id']]);
            }
            if ($p['status'] === 'active') {
                $pdo->prepare("UPDATE cars SET status='rented' WHERE car_id=?")->execute([(int) $rental['car_id']]);
            }
        }

        if (isset($p['client_id'], $p['car_id'], $p['employee_id'], $p['start_date'], $p['end_date'], $p['total_amount'])) {
            require_admin();
            $pdo->prepare(
                'UPDATE rentals SET client_id=?, car_id=?, employee_id=?, start_date=?, end_date=?, total_amount=?, status=?
                 WHERE rental_id=?'
            )->execute([
                (int) $p['client_id'],
                (int) $p['car_id'],
                (int) $p['employee_id'],
                $p['start_date'],
                $p['end_date'],
                (float) $p['total_amount'],
                $p['status'] ?? $rental['status'],
                $id,
            ]);
        }
        $pdo->commit();
        json_ok(null, 'Saved');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_error($e->getMessage());
    }
}

if ($method === 'DELETE') {
    require_admin();
    $p = request_json();
    $id = (int) ($p['rental_id'] ?? $_GET['rental_id'] ?? 0);
    if ($id <= 0) {
        json_error('rental_id required');
    }
    $pdo->beginTransaction();
    try {
        $row = $pdo->prepare('SELECT car_id FROM rentals WHERE rental_id=?');
        $row->execute([$id]);
        $carId = $row->fetchColumn();
        $pdo->prepare('DELETE FROM payments WHERE rental_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM rentals WHERE rental_id=?')->execute([$id]);
        if ($carId) {
            $active = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE car_id=? AND status='active'");
            $active->execute([(int) $carId]);
            if ((int) $active->fetchColumn() === 0) {
                $pdo->prepare("UPDATE cars SET status='available' WHERE car_id=?")->execute([(int) $carId]);
            }
        }
        $pdo->commit();
        json_ok(null, 'Deleted');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_error($e->getMessage());
    }
}

json_error('Method not allowed', 405);
