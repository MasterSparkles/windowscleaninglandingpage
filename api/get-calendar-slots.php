<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Get the requested date
$date = $_GET['date'] ?? date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

// TODO: Replace with actual Google Calendar API integration
// For now, return simulated booked slots
$bookedSlots = getBookedSlots($date);

echo json_encode([
    'success' => true,
    'date' => $date,
    'bookedSlots' => $bookedSlots
]);

/**
 * Get booked time slots from Google Calendar
 * 
 * To integrate with Google Calendar:
 * 1. Create a Google Cloud Project
 * 2. Enable Google Calendar API
 * 3. Create Service Account credentials
 * 4. Share your calendar with the service account email
 * 5. Use Google Calendar API PHP client library
 */
function getBookedSlots($date) {
    // Simulated booked slots for demo
    // In production, this would query Google Calendar API
    
    $simulatedBookings = [
        '2026-09-09' => ['09:00:00', '09:05:00', '10:30:00', '14:00:00', '16:15:00'],
        '2026-09-10' => ['08:00:00', '11:00:00', '15:30:00'],
    ];
    
    return $simulatedBookings[$date] ?? [];
    
    /* 
    // Example Google Calendar API integration (uncomment when ready):
    
    require_once 'vendor/autoload.php';
    
    $client = new Google_Client();
    $client->setAuthConfig('path/to/service-account-key.json');
    $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
    
    $service = new Google_Service_Calendar($client);
    $calendarId = 'your-calendar-id@group.calendar.google.com';
    
    $timeMin = $date . 'T08:00:00+08:00'; // Perth timezone
    $timeMax = $date . 'T20:00:00+08:00';
    
    $optParams = array(
        'timeMin' => $timeMin,
        'timeMax' => $timeMax,
        'singleEvents' => true,
        'orderBy' => 'startTime',
    );
    
    $results = $service->events->listEvents($calendarId, $optParams);
    $bookedSlots = [];
    
    foreach ($results->getItems() as $event) {
        $start = $event->start->dateTime;
        if ($start) {
            $bookedSlots[] = date('H:i:s', strtotime($start));
        }
    }
    
    return $bookedSlots;
    */
}
?>
