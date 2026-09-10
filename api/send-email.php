<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Helper functions
function post_string(string $key): string {
    $value = $_POST[$key] ?? '';
    if (is_array($value)) return '';
    return trim((string)$value);
}

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Get form data
$name = post_string('name');
$phone = post_string('phone');
$email = post_string('email');
$service = post_string('service');
$message = post_string('message');
$address = post_string('address');
$contactMethod = post_string('contactMethod');
$contactTime = post_string('contactTime');
$callDate = post_string('callDate');
$callTime = post_string('callTime');

// Validate required fields
if ($name === '' || $phone === '' || $email === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit();
}

// Service names mapping
$serviceNames = [
    'residential' => 'Residential Window Cleaning',
    'highreach' => 'Residential High Reach Cleaning',
    'commercial' => 'Commercial Window Cleaning',
    'solar' => 'Solar Panel Cleaning',
    'domestic' => 'Domestic Cleaning',
    'window' => 'Window Cleaning',
    'endoflease' => 'End of Lease Cleaning',
    'oven' => 'Oven Cleaning',
    'carpet' => 'Carpet Cleaning',
    'builders' => 'Builders Cleaning'
];

$serviceLabel = $serviceNames[$service] ?? $service;

$submittedAt = gmdate('Y-m-d H:i:s') . ' UTC';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$subject = 'New Window Cleaning Quote Request - ' . $serviceLabel;

// Format contact preferences
$contactMethodLabels = [
    'phone' => 'Phone Call',
    'email' => 'Email',
    'either' => 'Either Phone or Email'
];
$contactTimeLabels = [
    'morning' => 'Morning (8am - 12pm)',
    'afternoon' => 'Afternoon (12pm - 5pm)',
    'evening' => 'Evening (5pm - 8pm)',
    'anytime' => 'Anytime'
];

$contactMethodText = $contactMethodLabels[$contactMethod] ?? $contactMethod;
$contactTimeText = $contactTimeLabels[$contactTime] ?? $contactTime;

// Plain text email
$plainText = "New Window Cleaning Quote Request\n\n" .
    "Name: $name\n" .
    "Phone: $phone\n" .
    "Email: $email\n" .
    "Service: $serviceLabel\n" .
    "Address: " . ($address ?: 'Not provided') . "\n\n" .
    "CONTACT PREFERENCES:\n" .
    "Preferred Method: $contactMethodText\n" .
    "Best Time: $contactTimeText\n\n" .
    "Message: " . ($message ?: 'No additional details') . "\n\n" .
    "Submitted: $submittedAt\n" .
    "IP: $ip\n\n" .
    "Master Sparkle's Cleaning Service";

try {
    // Use hosting email for better deliverability
    $fromEmail = 'noreply@mastersparkles.com.au';
    $adminEmails = [
        'admin@mastersparkles.com.au',
        'jenniferricana21@gmail.com',
        'leads@mastersparkles.com.au',
        'mastersparklescleaning@gmail.com'
    ];
    
    // Email headers
    $headers = "From: Master Sparkle's Cleaning <$fromEmail>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send to all admin emails
    $sent = false;
    foreach ($adminEmails as $adminEmail) {
        $result = mail($adminEmail, $subject, $plainText, $headers);
        if ($result) {
            $sent = true;
            error_log("Email sent successfully to: $adminEmail");
        } else {
            error_log("Email failed to send to: $adminEmail");
        }
    }
 
    if (!$sent) {
        error_log('Email send failed - returning false');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send your request. Please call us at 0424 262 102.'
        ]);
        exit();
    }

    // Send customer confirmation email
    $customerSubject = 'We received your quote request - Master Sparkle\'s';
    $customerText = "Thanks $name,\n\n" .
        "We received your request for: $serviceLabel\n" .
        "We will contact you shortly.\n\n" .
        "Your details:\n" .
        "Phone: $phone\n" .
        "Email: $email\n\n" .
        "Message:\n$message\n\n" .
        "Master Sparkle's Cleaning Service\n" .
        "0424 262 102";

    try {
        if ($email !== '') {
            $customerHeaders = "From: Master Sparkle's Cleaning <$fromEmail>\r\n";
            $customerHeaders .= "Reply-To: noreply@mastersparkles.com.au\r\n";
            $customerHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $customerHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            $customerResult = mail($email, $customerSubject, $customerText, $customerHeaders);
            if ($customerResult) {
                error_log("Customer confirmation email sent to: $email");
            } else {
                error_log("Customer confirmation email failed to send to: $email");
            }
        }
    } catch (Throwable $e) {
        error_log('Customer confirmation email failed: ' . $e->getMessage());
    }

    // Log to Google Sheets Daily Tracker
    try {
        $sheetsData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'service' => $service,
            'contactMethod' => $contactMethod,
            'contactTime' => $contactTime,
            'message' => $message,
            'callDate' => $callDate,
            'callTime' => $callTime
        ];
        
        // Call Google Sheets integration
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'send-to-sheets.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($sheetsData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $sheetsResponse = curl_exec($ch);
        curl_close($ch);
        
        error_log("Google Sheets response: " . $sheetsResponse);
    } catch (Throwable $e) {
        error_log('Google Sheets logging failed: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your request has been sent. We will contact you shortly.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('EmailControl error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error while sending your request.'
    ]);
}
