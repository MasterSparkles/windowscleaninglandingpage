<?php

declare(strict_types=1);

function getBusinessTimeZone(): string
{
    $timezone = getenv('BUSINESS_TIMEZONE');
    return $timezone !== false && $timezone !== '' ? $timezone : 'Australia/Perth';
}

function getBusinessNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(getBusinessTimeZone()));
}

function getGoogleServiceAccountConfig(): ?array
{
    $json = getenv('GOOGLE_SERVICE_ACCOUNT_JSON');

    if (is_string($json) && trim($json) !== '') {
        $decoded = json_decode(trim($json), true);
        if (is_array($decoded) && !empty($decoded['private_key']) && !empty($decoded['client_email'])) {
            return $decoded;
        }
    }

    $filePath = getenv('GOOGLE_SERVICE_ACCOUNT_JSON_FILE');
    if (is_string($filePath) && trim($filePath) !== '' && is_file($filePath)) {
        $json = file_get_contents($filePath);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && !empty($decoded['private_key']) && !empty($decoded['client_email'])) {
                return $decoded;
            }
        }
    }

    return null;
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getGoogleAccessToken(): ?string
{
    $config = getGoogleServiceAccountConfig();
    if (!$config) {
        return null;
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $payload = [
        'iss' => $config['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/calendar',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $encodedHeader = base64UrlEncode((string) json_encode($header));
    $encodedPayload = base64UrlEncode((string) json_encode($payload));
    $signingInput = $encodedHeader . '.' . $encodedPayload;

    $privateKey = $config['private_key'];
    $signature = '';
    $success = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    if ($success === false) {
        error_log('Failed to sign Google service account JWT.');
        return null;
    }

    $jwt = $signingInput . '.' . base64UrlEncode($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        error_log('Google token request failed: ' . $curlError);
        return null;
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 400 || !is_array($decoded) || empty($decoded['access_token'])) {
        error_log('Google token request failed with status ' . $httpCode . ': ' . $response);
        return null;
    }

    return (string) $decoded['access_token'];
}

function googleApiRequest(string $method, string $url, array $payload = [], ?string $accessToken = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
    ]);

    if ($accessToken !== null && $accessToken !== '') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
    }

    if ($method !== 'GET' && !empty($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return ['success' => false, 'httpCode' => $httpCode, 'error' => $curlError ?: 'Unknown curl error'];
    }

    $decoded = json_decode($response, true);
    if ($decoded === null && $response !== '') {
        $decoded = ['raw' => $response];
    }

    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'httpCode' => $httpCode,
        'body' => $decoded ?? [],
        'raw' => $response,
    ];
}

function appendGoogleSheetRow(array $rowData): bool
{
    $spreadsheetId = getenv('GOOGLE_SHEETS_SPREADSHEET_ID') ?: '18OWCLCQ6Jrtw5cFrVVKyr2grNLg2RTIgdiXL4jWluFI';
    $worksheetName = getenv('GOOGLE_SHEETS_WORKSHEET_NAME') ?: 'landing_page';
    $accessToken = getGoogleAccessToken();

    if ($accessToken === null) {
        error_log('Google Sheets integration is not configured. Skipping sheets append.');
        return false;
    }

    $range = $worksheetName;
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($spreadsheetId) . '/values/' . rawurlencode($range) . ':append?valueInputOption=RAW&insertDataOption=INSERT_ROWS';
    $result = googleApiRequest('POST', $url, ['values' => [$rowData]], $accessToken);

    if (!$result['success']) {
        error_log('Google Sheets append failed: ' . ($result['raw'] ?: json_encode($result['body'])));
        return false;
    }

    return true;
}

function createGoogleCalendarEvent(array $eventData): bool
{
    $accessToken = getGoogleAccessToken();
    if ($accessToken === null) {
        error_log('Google Calendar integration is not configured. Skipping calendar event creation.');
        return false;
    }

    $calendarId = getenv('GOOGLE_CALENDAR_ID') ?: getenv('BUSINESS_EMAIL') ?: 'mastersparklescleaning@gmail.com';
    $tz = getBusinessTimeZone();
    $startDateTime = new DateTimeImmutable($eventData['callDate'] . ' ' . $eventData['callTime'], new DateTimeZone($tz));
    $endDateTime = $startDateTime->modify('+15 minutes');

    $event = [
        'summary' => 'Phone Call - ' . $eventData['name'] . ' - Master Sparkles Cleaning',
        'description' => buildCalendarDescription($eventData),
        'start' => [
            'dateTime' => $startDateTime->format(DATE_ATOM),
            'timeZone' => $tz,
        ],
        'end' => [
            'dateTime' => $endDateTime->format(DATE_ATOM),
            'timeZone' => $tz,
        ],
        'attendees' => [
            ['email' => $eventData['email']],
        ],
    ];

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events';
    $result = googleApiRequest('POST', $url, $event, $accessToken);

    if (!$result['success']) {
        error_log('Google Calendar event creation failed: ' . ($result['raw'] ?: json_encode($result['body'])));
        return false;
    }

    return true;
}

function buildCalendarDescription(array $eventData): string
{
    $lines = [
        'Customer Information',
        'Full Name: ' . ($eventData['name'] ?? ''),
        'Phone: ' . ($eventData['phone'] ?? ''),
        'Email: ' . ($eventData['email'] ?? ''),
        '',
        'Booking Information',
        'Service: ' . ($eventData['service'] ?? ''),
        'Property Level: ' . ($eventData['levels'] ?? ''),
        'Preferred Cleaning Completion: ' . ($eventData['timing'] ?? ''),
        'Preferred Booking Date: ' . ($eventData['callDateLabel'] ?? ''),
        'Preferred Contact Method: ' . ($eventData['contactMethodLabel'] ?? ''),
        'Preferred Contact Time: ' . ($eventData['callTimeLabel'] ?? ''),
        '',
        'Service Address',
        'Address: ' . ($eventData['address'] ?? ''),
        'Message: ' . ($eventData['message'] ?? ''),
    ];

    return implode("\n", $lines);
}

function getGoogleBookedSlots(string $date): array
{
    $accessToken = getGoogleAccessToken();
    if ($accessToken === null) {
        return [];
    }

    $calendarId = getenv('GOOGLE_CALENDAR_ID') ?: getenv('BUSINESS_EMAIL') ?: 'mastersparklescleaning@gmail.com';
    $tz = new DateTimeZone(getBusinessTimeZone());
    $dateTime = new DateTimeImmutable($date . ' 00:00:00', $tz);
    $timeMin = $dateTime->format(DATE_ATOM);
    $timeMax = $dateTime->modify('+1 day')->format(DATE_ATOM);

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events?singleEvents=true&orderBy=startTime&timeMin=' . rawurlencode($timeMin) . '&timeMax=' . rawurlencode($timeMax);
    $result = googleApiRequest('GET', $url, [], $accessToken);

    if (!$result['success']) {
        error_log('Failed to load Google Calendar booked slots: ' . ($result['raw'] ?: json_encode($result['body'])));
        return [];
    }

    $items = $result['body']['items'] ?? [];
    $bookedSlots = [];

    foreach ($items as $item) {
        $start = $item['start']['dateTime'] ?? null;
        if (!$start) {
            continue;
        }

        $bookedSlots[] = date('H:i:s', strtotime($start));
    }

    return $bookedSlots;
}
