<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = getPdo();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$body = read_json_body();
if ($action === '' && isset($body['action'])) {
    $action = (string) $body['action'];
}

$adminOnly = [
    'cars.create', 'cars.update', 'cars.delete',
    'clients.update', 'clients.delete',
    'employees.create', 'employees.update', 'employees.delete',
    'rentals.update', 'rentals.delete',
    'payments.update', 'payments.delete',
    'admins.list', 'admins.create', 'admins.update', 'admins.delete',
];
if (in_array($action, $adminOnly, true)) {
    require_admin_api();
}

try {
    match ($action) {
        'auth.login' => auth_login($pdo, $body),
        'auth.logout' => auth_logout(),
        'auth.check' => json_ok(['admin' => is_admin(), 'username' => $_SESSION['username'] ?? null]),
        'dashboard.metrics' => dashboard_metrics($pdo),
        'cars.list' => cars_list($pdo),
        'cars.create' => cars_save($pdo, $body, null),
        'cars.update' => cars_save($pdo, $body, (int) ($body['car_id'] ?? 0)),
        'cars.delete' => delete_row($pdo, 'cars', 'car_id', (int) ($body['car_id'] ?? 0)),
        'clients.list' => clients_list($pdo),
        'clients.create' => clients_save($pdo, $body, null),
        'clients.update' => clients_save($pdo, $body, (int) ($body['client_id'] ?? 0)),
        'clients.delete' => delete_row($pdo, 'clients', 'client_id', (int) ($body['client_id'] ?? 0)),
        'clients.history' => clients_history($pdo, (int) ($_GET['client_id'] ?? $body['client_id'] ?? 0)),
        'employees.list' => employees_list($pdo),
        'employees.create' => employees_save($pdo, $body, null),
        'employees.update' => employees_save($pdo, $body, (int) ($body['employee_id'] ?? 0)),
        'employees.delete' => delete_row($pdo, 'employees', 'employee_id', (int) ($body['employee_id'] ?? 0)),
        'rentals.list' => rentals_list($pdo),
        'rentals.create' => rentals_create($pdo, $body),
        'rentals.update' => rentals_update($pdo, $body),
        'rentals.status' => rentals_status($pdo, $body),
        'rentals.delete' => delete_row($pdo, 'rentals', 'rental_id', (int) ($body['rental_id'] ?? 0)),
        'payments.list' => payments_list($pdo),
        'payments.create' => payments_create($pdo, $body),
        'payments.update' => payments_save($pdo, $body, (int) ($body['payment_id'] ?? 0)),
        'payments.delete' => delete_row($pdo, 'payments', 'payment_id', (int) ($body['payment_id'] ?? 0)),
        'admins.list' => admins_list($pdo),
        'admins.create' => admins_save($pdo, $body, null),
        'admins.update' => admins_save($pdo, $body, (int) ($body['admin_id'] ?? 0)),
        'admins.delete' => admins_delete($pdo, (int) ($body['admin_id'] ?? 0)),
        default => json_error('Unknown action', 404),
    };
} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}

function auth_login(PDO $pdo, array $body): void
{
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    if ($username === '' || $password === '') {
        json_error('Username and password are required');
    }
    $stmt = $pdo->prepare('SELECT admin_id, username, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        json_error('Invalid credentials', 401);
    }
    $_SESSION['admin_id'] = (int) $admin['admin_id'];
    $_SESSION['username'] = $admin['username'];
    json_ok(['username' => $admin['username']]);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    json_ok();
}

function dashboard_metrics(PDO $pdo): void
{
    json_ok([
        'fleet' => (int) $pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn(),
        'active_rentals' => (int) $pdo->query("SELECT COUNT(*) FROM rentals WHERE status = 'active'")->fetchColumn(),
        'revenue' => (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn(),
        'clients' => (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn(),
    ]);
}

function cars_list(PDO $pdo): void
{
    $sql = 'SELECT * FROM cars WHERE 1=1';
    $params = [];
    $status = $_GET['status'] ?? '';
    $brand = $_GET['brand'] ?? '';
    $price = $_GET['price'] ?? '';
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($status === 'available' || $status === 'rented') {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    if ($brand !== '') {
        $sql .= ' AND brand = ?';
        $params[] = $brand;
    }
    if ($price === 'low') {
        $sql .= ' AND daily_rate <= 15000';
    } elseif ($price === 'mid') {
        $sql .= ' AND daily_rate > 15000 AND daily_rate <= 30000';
    } elseif ($price === 'high') {
        $sql .= ' AND daily_rate > 30000';
    }
    if ($q !== '') {
        $sql .= ' AND (brand LIKE ? OR model LIKE ? OR license_plate LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    $sql .= ' ORDER BY brand, model';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $brands = $pdo->query('SELECT DISTINCT brand FROM cars ORDER BY brand')->fetchAll(PDO::FETCH_COLUMN);
    json_ok(['rows' => $stmt->fetchAll(), 'brands' => $brands]);
}

function cars_save(PDO $pdo, array $body, ?int $id): void
{
    $brand = trim((string) ($body['brand'] ?? ''));
    $model = trim((string) ($body['model'] ?? ''));
    $plate = trim((string) ($body['license_plate'] ?? ''));
    $rate = (float) ($body['daily_rate'] ?? 0);
    $status = ($body['status'] ?? 'available') === 'rented' ? 'rented' : 'available';
    if ($brand === '' || $model === '' || $plate === '' || $rate <= 0) {
        json_error('Invalid car data');
    }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE cars SET brand=?, model=?, license_plate=?, daily_rate=?, status=? WHERE car_id=?');
        $stmt->execute([$brand, $model, $plate, $rate, $status, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cars (brand, model, license_plate, daily_rate, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$brand, $model, $plate, $rate, $status]);
        $id = (int) $pdo->lastInsertId();
    }
    json_ok(['id' => $id]);
}

function clients_list(PDO $pdo): void
{
    $rows = $pdo->query('SELECT * FROM clients ORDER BY created_at DESC')->fetchAll();
    json_ok(['rows' => $rows]);
}

function clients_save(PDO $pdo, array $body, ?int $id): void
{
    $first = trim((string) ($body['first_name'] ?? ''));
    $last = trim((string) ($body['last_name'] ?? ''));
    $phone = trim((string) ($body['phone'] ?? ''));
    $license = trim((string) ($body['driver_license'] ?? ''));
    if ($first === '' || $last === '' || $phone === '' || $license === '') {
        json_error('All client fields are required');
    }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE clients SET first_name=?, last_name=?, phone=?, driver_license=? WHERE client_id=?');
        $stmt->execute([$first, $last, $phone, $license, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO clients (first_name, last_name, phone, driver_license) VALUES (?,?,?,?)');
        $stmt->execute([$first, $last, $phone, $license]);
        $id = (int) $pdo->lastInsertId();
    }
    json_ok(['id' => $id]);
}

function clients_history(PDO $pdo, int $clientId): void
{
    if ($clientId <= 0) {
        json_error('client_id required');
    }
    $stmt = $pdo->prepare('
        SELECT r.*, c.brand, c.model, c.license_plate,
               (SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.rental_id = r.rental_id) AS paid
        FROM rentals r
        JOIN cars c ON c.car_id = r.car_id
        WHERE r.client_id = ?
        ORDER BY r.start_date DESC
    ');
    $stmt->execute([$clientId]);
    $client = $pdo->prepare('SELECT * FROM clients WHERE client_id = ?');
    $client->execute([$clientId]);
    json_ok(['client' => $client->fetch(), 'rows' => $stmt->fetchAll()]);
}

function employees_list(PDO $pdo): void
{
    json_ok(['rows' => $pdo->query('SELECT * FROM employees ORDER BY last_name')->fetchAll()]);
}

function employees_save(PDO $pdo, array $body, ?int $id): void
{
    $first = trim((string) ($body['first_name'] ?? ''));
    $last = trim((string) ($body['last_name'] ?? ''));
    $position = trim((string) ($body['position'] ?? ''));
    $phone = trim((string) ($body['phone'] ?? ''));
    if ($first === '' || $last === '' || $position === '' || $phone === '') {
        json_error('All employee fields are required');
    }
    if ($id) {
        $stmt = $pdo->prepare('UPDATE employees SET first_name=?, last_name=?, position=?, phone=? WHERE employee_id=?');
        $stmt->execute([$first, $last, $position, $phone, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO employees (first_name, last_name, position, phone) VALUES (?,?,?,?)');
        $stmt->execute([$first, $last, $position, $phone]);
        $id = (int) $pdo->lastInsertId();
    }
    json_ok(['id' => $id]);
}

function rentals_list(PDO $pdo): void
{
    $sql = '
        SELECT r.*,
               cl.first_name, cl.last_name, cl.phone,
               c.brand, c.model, c.license_plate, c.daily_rate,
               e.first_name AS emp_first, e.last_name AS emp_last,
               (SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.rental_id = r.rental_id) AS paid
        FROM rentals r
        JOIN clients cl ON cl.client_id = r.client_id
        JOIN cars c ON c.car_id = r.car_id
        JOIN employees e ON e.employee_id = r.employee_id
        ORDER BY r.rental_id DESC
    ';
    json_ok(['rows' => $pdo->query($sql)->fetchAll()]);
}

function rentals_create(PDO $pdo, array $body): void
{
    $carId = (int) ($body['car_id'] ?? 0);
    $clientId = (int) ($body['client_id'] ?? 0);
    $employeeId = (int) ($body['employee_id'] ?? 0);
    $start = (string) ($body['start_date'] ?? '');
    $end = (string) ($body['end_date'] ?? '');

    if (!empty($body['new_client']) && is_array($body['new_client'])) {
        $nc = $body['new_client'];
        $ins = $pdo->prepare('INSERT INTO clients (first_name, last_name, phone, driver_license) VALUES (?,?,?,?)');
        $ins->execute([
            trim((string) ($nc['first_name'] ?? '')),
            trim((string) ($nc['last_name'] ?? '')),
            trim((string) ($nc['phone'] ?? '')),
            trim((string) ($nc['driver_license'] ?? '')),
        ]);
        $clientId = (int) $pdo->lastInsertId();
    }

    if ($carId <= 0 || $clientId <= 0 || $employeeId <= 0 || $start === '' || $end === '') {
        json_error('Car, client, employee and dates are required');
    }
    if (strtotime($end) < strtotime($start)) {
        json_error('End date must be on or after start date');
    }

    $carStmt = $pdo->prepare('SELECT * FROM cars WHERE car_id = ? FOR UPDATE');
    $pdo->beginTransaction();
    $carStmt->execute([$carId]);
    $car = $carStmt->fetch();
    if (!$car) {
        $pdo->rollBack();
        json_error('Car not found');
    }
    if ($car['status'] !== 'available') {
        $pdo->rollBack();
        json_error('Car is not available');
    }
    $days = rental_days($start, $end);
    $total = round((float) $car['daily_rate'] * $days, 2);
    $insR = $pdo->prepare('INSERT INTO rentals (client_id, car_id, employee_id, start_date, end_date, total_amount, status) VALUES (?,?,?,?,?,?,?)');
    $insR->execute([$clientId, $carId, $employeeId, $start, $end, $total, 'active']);
    $rentalId = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE cars SET status = 'rented' WHERE car_id = ?")->execute([$carId]);
    $pdo->commit();
    json_ok(['id' => $rentalId, 'total_amount' => $total, 'days' => $days, 'client_id' => $clientId]);
}

function rentals_update(PDO $pdo, array $body): void
{
    $id = (int) ($body['rental_id'] ?? 0);
    if ($id <= 0) {
        json_error('rental_id required');
    }
    $fields = ['client_id', 'car_id', 'employee_id', 'start_date', 'end_date', 'total_amount', 'status'];
    $set = [];
    $params = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $body) && $body[$f] !== '') {
            $set[] = "$f = ?";
            $params[] = $body[$f];
        }
    }
    if (!$set) {
        json_error('No fields to update');
    }
    $params[] = $id;
    $pdo->prepare('UPDATE rentals SET ' . implode(',', $set) . ' WHERE rental_id = ?')->execute($params);
    json_ok(['id' => $id]);
}

function rentals_status(PDO $pdo, array $body): void
{
    $id = (int) ($body['rental_id'] ?? 0);
    $status = (string) ($body['status'] ?? '');
    if (!in_array($status, ['active', 'completed', 'cancelled'], true) || $id <= 0) {
        json_error('Invalid rental status');
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM rentals WHERE rental_id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $rental = $stmt->fetch();
    if (!$rental) {
        $pdo->rollBack();
        json_error('Rental not found');
    }
    $pdo->prepare('UPDATE rentals SET status = ? WHERE rental_id = ?')->execute([$status, $id]);
    if ($status === 'completed' || $status === 'cancelled') {
        $pdo->prepare("UPDATE cars SET status = 'available' WHERE car_id = ?")->execute([$rental['car_id']]);
    } elseif ($status === 'active') {
        $pdo->prepare("UPDATE cars SET status = 'rented' WHERE car_id = ?")->execute([$rental['car_id']]);
    }
    $pdo->commit();
    json_ok(['id' => $id, 'status' => $status]);
}

function payments_list(PDO $pdo): void
{
    $sql = '
        SELECT p.*, r.total_amount, r.status AS rental_status,
               cl.first_name, cl.last_name, c.brand, c.model, c.license_plate
        FROM payments p
        JOIN rentals r ON r.rental_id = p.rental_id
        JOIN clients cl ON cl.client_id = r.client_id
        JOIN cars c ON c.car_id = r.car_id
        ORDER BY p.payment_id DESC
    ';
    json_ok(['rows' => $pdo->query($sql)->fetchAll()]);
}

function payments_create(PDO $pdo, array $body): void
{
    $rentalId = (int) ($body['rental_id'] ?? 0);
    $amount = (float) ($body['amount'] ?? 0);
    $method = (string) ($body['payment_method'] ?? '');
    $date = (string) ($body['payment_date'] ?? date('Y-m-d'));
    if ($rentalId <= 0 || $amount <= 0 || !in_array($method, ['card', 'cash', 'transfer'], true)) {
        json_error('Invalid payment data');
    }
    $r = $pdo->prepare('SELECT * FROM rentals WHERE rental_id = ?');
    $r->execute([$rentalId]);
    $rental = $r->fetch();
    if (!$rental) {
        json_error('Rental not found');
    }
    $stmt = $pdo->prepare('INSERT INTO payments (rental_id, payment_date, amount, payment_method) VALUES (?,?,?,?)');
    $stmt->execute([$rentalId, $date, $amount, $method]);
    $paymentId = (int) $pdo->lastInsertId();
    json_ok(['id' => $paymentId, 'receipt' => payment_receipt($pdo, $paymentId)]);
}

function payments_save(PDO $pdo, array $body, int $id): void
{
    if ($id <= 0) {
        json_error('payment_id required');
    }
    $stmt = $pdo->prepare('UPDATE payments SET rental_id=?, payment_date=?, amount=?, payment_method=? WHERE payment_id=?');
    $stmt->execute([
        (int) ($body['rental_id'] ?? 0),
        (string) ($body['payment_date'] ?? date('Y-m-d')),
        (float) ($body['amount'] ?? 0),
        (string) ($body['payment_method'] ?? 'cash'),
        $id,
    ]);
    json_ok(['id' => $id]);
}

function payment_receipt(PDO $pdo, int $paymentId): array
{
    $stmt = $pdo->prepare('
        SELECT p.*, r.total_amount, r.start_date, r.end_date, r.status AS rental_status,
               cl.first_name, cl.last_name, cl.phone,
               c.brand, c.model, c.license_plate
        FROM payments p
        JOIN rentals r ON r.rental_id = p.rental_id
        JOIN clients cl ON cl.client_id = r.client_id
        JOIN cars c ON c.car_id = r.car_id
        WHERE p.payment_id = ?
    ');
    $stmt->execute([$paymentId]);
    $row = $stmt->fetch();
    return $row ?: [];
}

function admins_list(PDO $pdo): void
{
    json_ok(['rows' => $pdo->query('SELECT admin_id, username, created_at FROM admins ORDER BY admin_id')->fetchAll()]);
}

function admins_save(PDO $pdo, array $body, ?int $id): void
{
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? $body['password_hash'] ?? '');
    if ($username === '') {
        json_error('Username required');
    }
    if ($id) {
        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE admins SET username=?, password_hash=? WHERE admin_id=?');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE admins SET username=? WHERE admin_id=?');
            $stmt->execute([$username, $id]);
        }
    } else {
        if ($password === '') {
            json_error('Password required');
        }
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int) $pdo->lastInsertId();
    }
    json_ok(['id' => $id]);
}

function admins_delete(PDO $pdo, int $id): void
{
    if ($id <= 0) {
        json_error('admin_id required');
    }
    if (isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] === $id) {
        json_error('Cannot delete the signed-in admin');
    }
    delete_row($pdo, 'admins', 'admin_id', $id);
}

function delete_row(PDO $pdo, string $table, string $pk, int $id): void
{
    $allowed = [
        'admins' => 'admin_id',
        'cars' => 'car_id',
        'clients' => 'client_id',
        'employees' => 'employee_id',
        'payments' => 'payment_id',
        'rentals' => 'rental_id',
    ];
    if (!isset($allowed[$table]) || $allowed[$table] !== $pk || $id <= 0) {
        json_error('Invalid delete');
    }
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `{$pk}` = ?");
    $stmt->execute([$id]);
    json_ok(['id' => $id]);
}
