<?php
declare(strict_types=1);

function json_ok(mixed $data = null, string $message = 'OK', int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message, 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $cached = $decoded;
            return $cached;
        }
    }
    $cached = $_POST;
    return $cached;
}

function request_method(): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '';
    if ($method === 'POST' && $override !== '') {
        return strtoupper($override);
    }
    $payload = request_json();
    if ($method === 'POST' && isset($payload['_method'])) {
        return strtoupper((string) $payload['_method']);
    }
    return $method;
}

function rental_days(string $start, string $end): int
{
    $from = DateTimeImmutable::createFromFormat('Y-m-d', $start);
    $to = DateTimeImmutable::createFromFormat('Y-m-d', $end);
    if (!$from || !$to) {
        throw new InvalidArgumentException('Invalid dates');
    }
    if ($to < $from) {
        throw new InvalidArgumentException('End date must be after start date');
    }
    $days = (int) $from->diff($to)->days;
    return max(1, $days);
}

function page_name(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
}
