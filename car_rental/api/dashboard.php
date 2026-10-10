<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

$pdo = db();
$fleet = (int) $pdo->query('SELECT COUNT(*) FROM cars')->fetchColumn();
$active = (int) $pdo->query("SELECT COUNT(*) FROM rentals WHERE status='active'")->fetchColumn();
$revenue = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments')->fetchColumn();
$available = (int) $pdo->query("SELECT COUNT(*) FROM cars WHERE status='available'")->fetchColumn();
$clients = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();

json_ok([
    'fleet' => $fleet,
    'active_rentals' => $active,
    'revenue' => $revenue,
    'available' => $available,
    'clients' => $clients,
]);
