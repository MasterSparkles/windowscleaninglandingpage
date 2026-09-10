<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Send form data to Google Sheets - Daily Inquiry Audit Tracker
 * 
 * This script sends lead information to Google Sheets for tracking
 * Sheet: Daily Inquiry Audit Tracker
 * Tab: September (or current month)
 */

function sendToGoogleSheets($data) {
    // TODO: Set up Google Sheets API credentials
    // For now, this is a placeholder that logs the data
    
    /*
    SETUP INSTRUCTIONS:
    
    1. Go to Google Cloud Console (console.cloud.google.com)
    2. Enable Google Sheets API
    3. Create Service Account credentials
    4. Download JSON key file
    5. Share your Google Sheet with the service account email
    6. Install Google Sheets PHP library: composer require google/apiclient
    
    Example implementation:
    
    require_once 'vendor/autoload.php';
    
    $client = new Google_Client();
    $client->setAuthConfig('path/to/service-account-key.json');
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = 'YOUR_SPREADSHEET_ID'; // From the URL
    
    // Get current month for sheet name
    $currentMonth = date('F'); // e.g., "September"
    $range = $currentMonth . '!A:Z'; // Adjust columns as needed
    
    // Prepare row data
    $values = [
        [
            date('Y-m-d H:i:s'),        // Timestamp
            $data['name'],               // Name
            $data['phone'],              // Phone
            $data['email'],              // Email
            $data['address'],            // Address
            $data['service'],            // Service
            $data['contactMethod'],      // Contact Method
            $data['contactTime'],        // Best Time
            $data['message'],            // Message
            $data['callDate'] ?? '',     // Call Date (if phone call)
            $data['callTime'] ?? '',     // Call Time (if phone call)
            'New',                       // Status
            '',                          // Notes
        ]
    ];
    
    $body = new Google_Service_Sheets_ValueRange([
        'values' => $values
    ]);
    
    $params = [
        'valueInputOption' => 'RAW'
    ];
    
    $result = $service->spreadsheets_values->append(
        $spreadsheetId,
        $range,
        $body,
        $params
    );
    
    return $result;
    */
    
    // For now, log the data
    error_log("Google Sheets Data: " . json_encode($data));
    
    return true;
}

// Get POST data
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$service = $_POST['service'] ?? '';
$contactMethod = $_POST['contactMethod'] ?? '';
$contactTime = $_POST['contactTime'] ?? '';
$message = $_POST['message'] ?? '';
$callDate = $_POST['callDate'] ?? '';
$callTime = $_POST['callTime'] ?? '';

$data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'address' => $address,
    'service' => $service,
    'contactMethod' => $contactMethod,
    'contactTime' => $contactTime,
    'message' => $message,
    'callDate' => $callDate,
    'callTime' => $callTime,
    'status' => 'New',
    'source' => 'Window Cleaning Landing Page'
];

try {
    $result = sendToGoogleSheets($data);
    
    echo json_encode([
        'success' => true,
        'message' => 'Data logged to tracking sheet'
    ]);
} catch (Exception $e) {
    error_log("Google Sheets Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error logging to tracking sheet: ' . $e->getMessage()
    ]);
}
?>
