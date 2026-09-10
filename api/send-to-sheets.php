<?php

declare(strict_types=1);

require_once __DIR__ . '/google-helpers.php';

function normalize_sheet_input(array $data): array
{
    $row = [
        $data['timestamp'] ?? date('Y-m-d H:i:s'),
        $data['name'] ?? '',
        $data['phone'] ?? '',
        $data['email'] ?? '',
        $data['address'] ?? '',
        $data['callDate'] ?? '',
        $data['service'] ?? '',
        $data['source'] ?? 'Email',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
    ];

    return $row;
}

function appendLeadToGoogleSheets(array $data): bool
{
    $row = normalize_sheet_input($data);
    return appendGoogleSheetRow($row);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }

    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'name' => $_POST['name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'callDate' => $_POST['callDate'] ?? '',
        'service' => $_POST['service'] ?? '',
        'source' => ($_POST['contactMethod'] ?? '') === 'phone' ? 'Call' : 'Email',
    ];

    try {
        $result = appendLeadToGoogleSheets($data);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Data logged to tracking sheet' : 'Error logging to tracking sheet',
        ]);
    } catch (Throwable $e) {
        error_log('Google Sheets Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error logging to tracking sheet',
        ]);
    }
}

