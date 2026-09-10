<?php

declare(strict_types=1);

require_once __DIR__ . '/google-helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$date = $_GET['date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

$bookedSlots = getGoogleBookedSlots($date);

echo json_encode([
    'success' => true,
    'date' => $date,
    'bookedSlots' => $bookedSlots,
]);

