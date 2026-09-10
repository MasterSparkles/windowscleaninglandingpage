<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/google-helpers.php';

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    if (is_array($value)) {
        return '';
    }

    return trim((string) $value);
}

function sanitize_text(?string $value): string
{
    $clean = trim((string) $value);
    return htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_contact_method(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === 'phone' || $value === 'phone calls' || $value === 'phonecall') {
        return 'phone';
    }

    if ($value === 'email') {
        return 'email';
    }

    if ($value === 'either') {
        return 'either';
    }

    return '';
}

function build_business_email(): string
{
    $email = getenv('BUSINESS_EMAIL');
    if (is_string($email) && trim($email) !== '') {
        return trim($email);
    }

    return 'mastersparklescleaning@gmail.com';
}

function build_email_body(array $data): string
{
    $service = $data['service'] !== '' ? $data['service'] : 'Not provided';
    $levels = $data['levels'] !== '' ? $data['levels'] : 'Not provided';
    $timing = $data['timing'] !== '' ? $data['timing'] : 'Not provided';
    $contactMethod = $data['contactMethod'] === 'phone' ? 'Phone Calls' : 'Email';

    $bookingDate = $data['callDate'] !== '' ? $data['callDate'] : 'Not provided';
    $bookingTime = $data['callTime'] !== '' ? $data['callTime'] : 'Not provided';

    $bestTime = $data['contactMethod'] === 'phone' && $data['contactTime'] !== '' ? $data['contactTime'] : 'Not provided';

    return "Hello,\n\n" .
        "A new cleaning service booking has been submitted through the Master Sparkles Cleaning Service booking system.\n\n" .
        "### Booking Details\n" .
        "**Service:** " . $service . "\n\n" .
        "**levels of the property:** " . $levels . "\n\n" .
        "**prefered cleaning completed:** " . $timing . "\n\n" .
        "### Customer Information\n" .
        "**Full Name:** " . $data['name'] . "\n\n" .
        "**Phone:** " . $data['phone'] . "\n\n" .
        "**Email:** " . $data['email'] . "\n\n" .
        "### Service Address\n" .
        "**Address:** " . ($data['address'] !== '' ? $data['address'] : 'Not provided') . "\n\n" .
        "**City:** " . ($data['city'] !== '' ? $data['city'] : 'Not provided') . "\n\n" .
        "**State:** " . ($data['state'] !== '' ? $data['state'] : 'Not provided') . "\n\n" .
        "---\n\n" .
        "### What happens next?\n" .
        "Thank you for choosing **Master Sparkles Cleaning Service**.\n\n" .
        "Our team will review your booking details and contact you regarding confirmation, availability, and any additional information required.\n\n" .
        "If you have any questions or need to make changes to your booking, please reply directly to this email.\n\n" .
        "Kind regards,\n\n" .
        "**Master Sparkles Cleaning Service**\n" .
        "*Professional Cleaning Services*\n\n" .
        "### Additional Booking Details\n" .
        "**Preferred Contact Method:** " . $contactMethod . "\n\n" .
        "**Preferred Booking Date:** " . $bookingDate . "\n\n" .
        "**Preferred Contact Time:** " . $bookingTime . "\n\n" .
        "**Best Time to Contact:** " . $bestTime . "\n\n" .
        "**Message:** " . ($data['message'] !== '' ? $data['message'] : 'Not provided') . "\n";
}

function send_business_email(string $recipient, string $replyTo, string $subject, string $body): bool
{
    $fromEmail = build_business_email();
    $headers = "From: Master Sparkles Cleaning <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $result = mail($recipient, $subject, $body, $headers);
    if ($result) {
        error_log('Business email sent to: ' . $recipient);
        return true;
    }

    error_log('Business email failed to send to: ' . $recipient);
    return false;
}

function validate_phone_request(array $data): array
{
    $errors = [];

    $callDate = $data['callDate'];
    $callTime = $data['callTime'];
    $businessNow = getBusinessNow();
    $today = $businessNow->format('Y-m-d');
    $tomorrow = $businessNow->modify('+1 day')->format('Y-m-d');

    if ($callDate === '' || $callTime === '') {
        $errors[] = 'Phone Calls requires a valid date and time selection.';
        return $errors;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $callDate)) {
        $errors[] = 'Invalid phone-call date selection.';
        return $errors;
    }

    if ($callDate !== $today && $callDate !== $tomorrow) {
        $errors[] = 'Phone Calls must be scheduled for Today or Tomorrow only.';
        return $errors;
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $callTime)) {
        $errors[] = 'Invalid phone-call time selection.';
        return $errors;
    }

    $allowedTimes = [
        '08:00', '08:15', '08:30', '08:45', '09:00', '09:15', '09:30', '09:45',
        '10:00', '10:15', '10:30', '10:45', '14:00', '14:15', '14:30', '14:45',
        '15:00', '15:15', '15:30', '15:45', '16:00', '16:15', '16:30', '16:45', '17:00'
    ];

    if (!in_array($callTime, $allowedTimes, true)) {
        $errors[] = 'Phone Calls must use one of the available 15-minute time slots.';
        return $errors;
    }

    $slotDateTime = new DateTimeImmutable($callDate . ' ' . $callTime, new DateTimeZone(getBusinessTimeZone()));
    if ($callDate === $today && $slotDateTime <= $businessNow) {
        $errors[] = 'Past phone-call times are not available.';
    }

    return $errors;
}

$name = sanitize_text(post_string('name'));
$phone = sanitize_text(post_string('phone'));
$email = sanitize_text(post_string('email'));
$address = sanitize_text(post_string('address'));
$city = sanitize_text(post_string('city'));
$state = sanitize_text(post_string('state'));
$service = sanitize_text(post_string('service'));
$levels = sanitize_text(post_string('levels'));
$timing = sanitize_text(post_string('timing'));
$contactMethod = normalize_contact_method(post_string('contactMethod'));
$contactTime = sanitize_text(post_string('contactTime'));
$callDate = sanitize_text(post_string('callDate'));
$callTime = sanitize_text(post_string('callTime'));
$message = sanitize_text(post_string('message'));

if ($name === '' || $phone === '' || $email === '' || $service === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if ($contactMethod === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please choose a Preferred Contact Method.']);
    exit();
}

if ($contactMethod === 'phone') {
    $validationErrors = validate_phone_request([
        'callDate' => $callDate,
        'callTime' => $callTime,
    ]);

    if ($validationErrors !== []) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => implode(' ', $validationErrors),
        ]);
        exit();
    }
}

$submittedAt = getBusinessNow()->format('Y-m-d H:i:s');
$subject = 'New Lead From - "Master Sparkles Cleaning"';
$businessEmail = build_business_email();

$data = [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'service' => $service,
    'levels' => $levels,
    'timing' => $timing,
    'contactMethod' => $contactMethod,
    'contactTime' => $contactTime,
    'callDate' => $callDate,
    'callTime' => $callTime,
    'message' => $message,
    'submittedAt' => $submittedAt,
];

$body = build_email_body($data);

try {
    if (!send_business_email($businessEmail, $email, $subject, $body)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'We could not send your request right now. Please try again or call us directly.',
        ]);
        exit();
    }

    $sheetRow = [
        $submittedAt,
        $name,
        $phone,
        $email,
        $address,
        $callDate,
        $service,
        $contactMethod === 'phone' ? 'Call' : 'Email',
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

    $sheetStored = appendGoogleSheetRow($sheetRow);
    if ($sheetStored === false) {
        error_log('Google Sheets append returned false for lead submission.');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Your request was sent, but one of the tracking systems could not be updated. Please call us to confirm your booking.',
        ]);
        exit();
    }

    if ($contactMethod === 'phone') {
        $calendarResult = createGoogleCalendarEvent([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'service' => $service,
            'levels' => $levels,
            'timing' => $timing,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'message' => $message,
            'callDate' => $callDate,
            'callTime' => $callTime,
            'contactMethodLabel' => 'Phone Calls',
            'callDateLabel' => $callDate,
            'callTimeLabel' => $callTime,
            'contactTime' => $contactTime,
        ]);

        if ($calendarResult === false) {
            error_log('Google Calendar event creation failed for lead submission.');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Your request was sent, but the calendar booking could not be created. Please call us to confirm your appointment.',
            ]);
            exit();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your request has been sent. We will contact you shortly.',
    ]);
} catch (Throwable $e) {
    error_log('send-email.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error while sending your request.',
    ]);
}
