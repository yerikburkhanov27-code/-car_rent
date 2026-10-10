<?php
declare(strict_types=1);

/**
 * PDO connection for car_rental_db with bootstrap + schema seed.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'car_rental_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $root = new PDO(
            "mysql:host={$host};port={$port};charset={$charset}",
            $user,
            $pass,
            $options
        );
        $root->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbname}`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $root = null;

        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
            $user,
            $pass,
            $options
        );
        ensure_schema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        $safe = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "Database error: {$e->getMessage()}\n");
            exit(1);
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="kk"><head><meta charset="UTF-8"><title>DB</title></head><body>';
        echo '<h1>MySQL қосылымы сәтсіз</h1>';
        echo '<p>Тексеріңіз: MySQL іске қосылған, пайдаланушы <code>root</code>, құпиясөз бос, хост 127.0.0.1.</p>';
        echo '<pre>' . $safe . '</pre></body></html>';
        exit;
    }
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cars (
            car_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            brand VARCHAR(80) NOT NULL,
            model VARCHAR(80) NOT NULL,
            license_plate VARCHAR(32) NOT NULL UNIQUE,
            daily_rate DECIMAL(10,2) NOT NULL,
            status ENUM('available','rented') NOT NULL DEFAULT 'available'
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            client_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            phone VARCHAR(40) NOT NULL,
            driver_license VARCHAR(60) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS employees (
            employee_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            position VARCHAR(80) NOT NULL,
            phone VARCHAR(40) NOT NULL
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rentals (
            rental_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id INT UNSIGNED NOT NULL,
            car_id INT UNSIGNED NOT NULL,
            employee_id INT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
            CONSTRAINT fk_rentals_client FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE RESTRICT,
            CONSTRAINT fk_rentals_car FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE RESTRICT,
            CONSTRAINT fk_rentals_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rental_id INT UNSIGNED NOT NULL,
            payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('card','cash','transfer') NOT NULL,
            CONSTRAINT fk_payments_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB
    ");

    $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count === 0) {
        seed_database($pdo);
    }
}

function seed_database(PDO $pdo): void
{
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $admin = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
    $admin->execute(['admin', $hash]);

    $emp = $pdo->prepare('INSERT INTO employees (first_name, last_name, position, phone) VALUES (?,?,?,?)');
    $employees = [
        ['Aigerim', 'Nurgaliyeva', 'Manager', '+7 701 111 2233'],
        ['Daniyar', 'Sultanov', 'Agent', '+7 702 444 5566'],
        ['Miras', 'Bekzat', 'Support', '+7 705 777 8899'],
    ];
    foreach ($employees as $row) {
        $emp->execute($row);
    }

    $car = $pdo->prepare('INSERT INTO cars (brand, model, license_plate, daily_rate, status) VALUES (?,?,?,?,?)');
    $cars = [
        ['Toyota', 'Camry', '01KG 123 ABC', '18000.00', 'available'],
        ['Hyundai', 'Sonata', '02AL 456 DEF', '16500.00', 'available'],
        ['Kia', 'K5', '05AST 789 GHI', '17000.00', 'available'],
        ['BMW', '320i', '01NS 321 JKL', '32000.00', 'available'],
        ['Mercedes-Benz', 'E200', '02NS 654 MNO', '38000.00', 'rented'],
        ['Volkswagen', 'Polo', '03SHY 987 PQR', '12000.00', 'available'],
        ['Chevrolet', 'Cobalt', '04ATR 147 STU', '11000.00', 'available'],
        ['Lexus', 'RX 350', '01AST 258 VWX', '45000.00', 'available'],
    ];
    foreach ($cars as $row) {
        $car->execute($row);
    }

    $cli = $pdo->prepare('INSERT INTO clients (first_name, last_name, phone, driver_license) VALUES (?,?,?,?)');
    $clients = [
        ['Alina', 'Serikova', '+7 777 100 2001', 'KZ-DL-100001'],
        ['Yerbol', 'Tolegenov', '+7 776 200 3002', 'KZ-DL-100002'],
        ['Dana', 'Omarova', '+7 701 300 4003', 'KZ-DL-100003'],
        ['Nurlan', 'Ibrayev', '+7 702 400 5004', 'KZ-DL-100004'],
    ];
    foreach ($clients as $row) {
        $cli->execute($row);
    }

    $ren = $pdo->prepare(
        'INSERT INTO rentals (client_id, car_id, employee_id, start_date, end_date, total_amount, status)
         VALUES (?,?,?,?,?,?,?)'
    );
    $start = (new DateTimeImmutable('-3 days'))->format('Y-m-d');
    $end = (new DateTimeImmutable('+2 days'))->format('Y-m-d');
    $ren->execute([1, 5, 1, $start, $end, '190000.00', 'active']);

    $pay = $pdo->prepare(
        'INSERT INTO payments (rental_id, payment_date, amount, payment_method) VALUES (?,?,?,?)'
    );
    $pay->execute([1, (new DateTimeImmutable('-3 days'))->format('Y-m-d H:i:s'), '95000.00', 'card']);
}
