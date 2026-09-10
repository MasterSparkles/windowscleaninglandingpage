# Google Calendar Integration Setup Guide

## Overview
This guide will help you integrate Google Calendar with your booking system to show real-time availability and automatically block booked time slots.

---

## 🎯 What This Does

- **Shows real-time availability** - Customers see only available time slots
- **Prevents double-booking** - Booked slots are automatically marked as unavailable
- **5-minute intervals** - Precise scheduling from 8am to 8pm
- **Automatic calendar updates** - When customer books, it's added to Google Calendar
- **Email notifications** - Both you and customer get calendar invites

---

## 📋 Setup Steps

### **Step 1: Create Google Cloud Project**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Create Project"**
3. Name it: `Master Sparkles Booking`
4. Click **"Create"**

### **Step 2: Enable Google Calendar API**

1. In your project, go to **"APIs & Services"** → **"Library"**
2. Search for **"Google Calendar API"**
3. Click on it and press **"Enable"**

### **Step 3: Create Service Account**

1. Go to **"APIs & Services"** → **"Credentials"**
2. Click **"Create Credentials"** → **"Service Account"**
3. Fill in:
   - **Name:** `booking-system`
   - **Description:** `Service account for booking calendar integration`
4. Click **"Create and Continue"**
5. **Role:** Select `Editor` or `Calendar Editor`
6. Click **"Done"**

### **Step 4: Generate Service Account Key**

1. Click on the service account you just created
2. Go to **"Keys"** tab
3. Click **"Add Key"** → **"Create new key"**
4. Choose **JSON** format
5. Click **"Create"**
6. **Save the JSON file** - You'll need this!

### **Step 5: Share Calendar with Service Account**

1. Open [Google Calendar](https://calendar.google.com/)
2. Create a new calendar (or use existing):
   - Click **"+"** next to **"Other calendars"**
   - Select **"Create new calendar"**
   - Name: `Master Sparkles Bookings`
   - Click **"Create calendar"**
3. Find your new calendar in the list
4. Click the **3 dots** → **"Settings and sharing"**
5. Scroll to **"Share with specific people"**
6. Click **"Add people"**
7. **Paste the service account email** from the JSON file
   - It looks like: `booking-system@master-sparkles-booking.iam.gserviceaccount.com`
8. Set permission to **"Make changes to events"**
9. Click **"Send"**

### **Step 6: Get Calendar ID**

1. In Calendar settings, scroll to **"Integrate calendar"**
2. Copy the **Calendar ID**
   - It looks like: `abc123@group.calendar.google.com`
3. Save this - you'll need it!

---

## 💻 Code Integration

### **Step 1: Install Google Client Library**

Upload these files to your server in the `api` folder:

```bash
# If you have composer access on your server:
composer require google/apiclient:"^2.0"

# Otherwise, download the library manually:
# https://github.com/googleapis/google-api-php-client/releases
```

### **Step 2: Upload Service Account Key**

1. Upload the JSON key file to your server
2. Place it in: `/api/credentials/service-account-key.json`
3. **Important:** Make sure this folder is NOT publicly accessible!

### **Step 3: Update get-calendar-slots.php**

Replace the file content with the actual Google Calendar integration:

```php
<?php
require_once 'vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$date = $_GET['date'] ?? date('Y-m-d');

try {
    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/credentials/service-account-key.json');
    $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
    
    $service = new Google_Service_Calendar($client);
    $calendarId = 'YOUR_CALENDAR_ID@group.calendar.google.com'; // Replace with your calendar ID
    
    $timeMin = $date . 'T08:00:00+08:00'; // Perth timezone (UTC+8)
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
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'bookedSlots' => $bookedSlots
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Calendar error: ' . $e->getMessage()
    ]);
}
?>
```

### **Step 4: Create Calendar Event When Booking**

Add this to your `send-email.php` after successful email send:

```php
// Add to Google Calendar
if ($contactMethod === 'phone' && !empty($contactTime)) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        $client = new Google_Client();
        $client->setAuthConfig(__DIR__ . '/credentials/service-account-key.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        
        $service = new Google_Service_Calendar($client);
        $calendarId = 'YOUR_CALENDAR_ID@group.calendar.google.com';
        
        // Parse the datetime
        $callDateTime = new DateTime($contactTime, new DateTimeZone('Australia/Perth'));
        $endDateTime = clone $callDateTime;
        $endDateTime->modify('+5 minutes'); // 5-minute call slot
        
        $event = new Google_Service_Calendar_Event([
            'summary' => "Call: $name - $serviceLabel",
            'description' => "Phone: $phone\nEmail: $email\nService: $serviceLabel\nAddress: $address\nMessage: $message",
            'start' => [
                'dateTime' => $callDateTime->format('c'),
                'timeZone' => 'Australia/Perth',
            ],
            'end' => [
                'dateTime' => $endDateTime->format('c'),
                'timeZone' => 'Australia/Perth',
            ],
            'attendees' => [
                ['email' => $email, 'displayName' => $name],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 60],
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ]);
        
        $event = $service->events->insert($calendarId, $event, ['sendUpdates' => 'all']);
        error_log("Calendar event created: " . $event->getId());
        
    } catch (Exception $e) {
        error_log("Calendar error: " . $e->getMessage());
        // Don't fail the booking if calendar fails
    }
}
```

---

## 🧪 Testing

### **Test 1: Check API Connection**

Visit: `https://yoursite.com/api/get-calendar-slots.php?date=2026-09-10`

Expected response:
```json
{
  "success": true,
  "date": "2026-09-10",
  "bookedSlots": []
}
```

### **Test 2: Create Test Event**

1. Go to your Google Calendar
2. Create an event on tomorrow's date at 10:00 AM
3. Refresh the booking form
4. Select tomorrow's date
5. The 10:00 AM slot should show as "booked"

### **Test 3: Book a Call**

1. Fill out the form
2. Select "Phone Call" as contact method
3. Choose a date and time slot
4. Submit
5. Check your Google Calendar - event should appear
6. Check customer's email - should receive calendar invite

---

## 🔒 Security Best Practices

1. **Never commit the JSON key file to Git**
   - Add to `.gitignore`: `api/credentials/*.json`

2. **Restrict file permissions**
   ```bash
   chmod 600 api/credentials/service-account-key.json
   ```

3. **Use environment variables** (optional but recommended)
   ```php
   $client->setAuthConfig(getenv('GOOGLE_SERVICE_ACCOUNT_KEY'));
   ```

4. **Limit API access**
   - Only enable Calendar API
   - Use least privilege (Calendar Editor, not Owner)

---

## 📊 Calendar Management

### **View All Bookings**

1. Open Google Calendar
2. Select "Master Sparkles Bookings" calendar
3. All phone call bookings will appear here

### **Manually Block Time**

1. Create an event in the calendar
2. Title it "BLOCKED" or "Unavailable"
3. That time slot will automatically be unavailable on the website

### **Sync with Your Phone**

1. Install Google Calendar app
2. Sign in with your account
3. Enable "Master Sparkles Bookings" calendar
4. Get notifications for upcoming calls

---

## 🎯 Benefits

✅ **No double-booking** - Real-time availability  
✅ **Automatic reminders** - Email + popup notifications  
✅ **Professional** - Customers get calendar invites  
✅ **Organized** - All bookings in one place  
✅ **Mobile access** - Check schedule anywhere  
✅ **Team sync** - Share calendar with staff  

---

## 🆘 Troubleshooting

### **"Calendar not found" error**
- Check calendar ID is correct
- Verify service account has access to calendar

### **"Permission denied" error**
- Make sure you shared calendar with service account email
- Check service account has "Make changes to events" permission

### **Slots not showing as booked**
- Verify timezone is set to `Australia/Perth`
- Check event times are between 8am-8pm
- Ensure events are on the correct calendar

### **Can't install Google Client Library**
- Download manually from GitHub
- Upload to `api/vendor` folder
- Update `require_once` path

---

## 📞 Need Help?

If you encounter issues:
1. Check error logs: `/api/error_log`
2. Test API endpoint directly
3. Verify service account permissions
4. Check calendar sharing settings

---

**Once set up, your booking system will be fully automated with Google Calendar!** 🎉📅
