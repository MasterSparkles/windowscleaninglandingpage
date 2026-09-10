# Google Sheets Integration Setup Guide

## Overview
This guide will help you integrate your window cleaning form with Google Sheets to automatically log all inquiries to your "Daily Inquiry Audit Tracker" spreadsheet.

---

## 🎯 What This Does

- **Automatically logs all form submissions** to Google Sheets
- **Organizes by month** - Data goes to the current month's tab (e.g., "September")
- **Tracks all details** - Name, phone, email, address, service, contact preferences, call date/time
- **Real-time updates** - Data appears immediately after form submission
- **Works alongside email** - Emails still send, sheets is an additional log

---

## 📋 Setup Steps

### **Step 1: Prepare Your Google Sheet**

1. Open your **Daily Inquiry Audit Tracker** spreadsheet
2. Make sure you have tabs for each month (January, February, March, etc.)
3. Ensure the **September** tab (or current month) has these column headers in Row 1:
   - A: **Timestamp**
   - B: **Name**
   - C: **Phone**
   - D: **Email**
   - E: **Address**
   - F: **Service**
   - G: **Contact Method**
   - H: **Best Time**
   - I: **Message**
   - J: **Call Date**
   - K: **Call Time**
   - L: **Status**
   - M: **Notes**

4. Note your **Spreadsheet ID** from the URL:
   ```
   https://docs.google.com/spreadsheets/d/SPREADSHEET_ID_HERE/edit
   ```

---

### **Step 2: Enable Google Sheets API**

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Select your existing project (or create a new one)
3. Go to **"APIs & Services"** → **"Library"**
4. Search for **"Google Sheets API"**
5. Click on it and press **"Enable"**

---

### **Step 3: Use Existing Service Account**

Since you already have a service account for Google Calendar:

1. Go to **"APIs & Services"** → **"Credentials"**
2. Find your existing service account (e.g., `booking-system@...`)
3. Click on it to view details
4. You can use the same JSON key file you downloaded for Calendar

**OR** create a new service account specifically for Sheets:

1. Click **"Create Credentials"** → **"Service Account"**
2. Fill in:
   - **Name:** `sheets-tracker`
   - **Description:** `Service account for Daily Inquiry Tracker`
3. Click **"Create and Continue"**
4. **Role:** Select `Editor`
5. Click **"Done"**

---

### **Step 4: Download Service Account Key**

If using existing service account:
- You already have the JSON key file

If creating new service account:
1. Click on the service account
2. Go to **"Keys"** tab
3. Click **"Add Key"** → **"Create new key"**
4. Choose **JSON** format
5. Click **"Create"**
6. Save the file as `sheets-service-account.json`

---

### **Step 5: Share Google Sheet with Service Account**

1. Open your **Daily Inquiry Audit Tracker** spreadsheet
2. Click **"Share"** button (top right)
3. Paste the service account email:
   - Format: `booking-system@your-project.iam.gserviceaccount.com`
   - Or: `sheets-tracker@your-project.iam.gserviceaccount.com`
4. Give it **Editor** access
5. Uncheck **"Notify people"**
6. Click **"Share"**

---

### **Step 6: Install Google API PHP Client**

On your server, navigate to the API directory and install the library:

```bash
cd /path/to/LandingPage/build/deploy/windowcleaning/api
composer require google/apiclient:"^2.0"
```

This will create a `vendor` folder with the Google API libraries.

---

### **Step 7: Upload Service Account Key**

1. Upload your `sheets-service-account.json` file to:
   ```
   /path/to/LandingPage/build/deploy/windowcleaning/api/sheets-service-account.json
   ```

2. **IMPORTANT:** Protect this file by adding to `.htaccess`:
   ```apache
   <Files "sheets-service-account.json">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

---

### **Step 8: Update send-to-sheets.php**

Edit `api/send-to-sheets.php` and update these values:

```php
// Line ~30: Uncomment and configure
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig('sheets-service-account.json'); // Your JSON key file
$client->addScope(Google_Service_Sheets::SPREADSHEETS);

$service = new Google_Service_Sheets($client);
$spreadsheetId = 'YOUR_SPREADSHEET_ID_HERE'; // From Step 1

// Get current month for sheet name
$currentMonth = date('F'); // e.g., "September"
$range = $currentMonth . '!A:M';

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
        $data['callDate'] ?? '',     // Call Date
        $data['callTime'] ?? '',     // Call Time
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
```

---

### **Step 9: Test the Integration**

1. Submit a test form on your window cleaning page
2. Check your Google Sheet's current month tab
3. You should see a new row with all the form data
4. Check server logs for any errors:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

---

## 📊 Data Flow

```
User submits form
    ↓
send-email.php receives data
    ↓
Sends admin & customer emails
    ↓
Calls send-to-sheets.php
    ↓
Authenticates with Google Sheets API
    ↓
Appends row to current month's tab
    ↓
Data appears in spreadsheet
```

---

## 🔧 Troubleshooting

### **Error: "The caller does not have permission"**
- Make sure you shared the spreadsheet with the service account email
- Check that the service account has Editor access

### **Error: "Unable to parse range"**
- Verify the month tab name matches exactly (e.g., "September" not "september")
- Create the month tab if it doesn't exist

### **Error: "Failed to load credentials"**
- Check the path to `sheets-service-account.json`
- Verify the JSON file is valid
- Ensure file permissions allow PHP to read it

### **No data appearing in sheet**
- Check server error logs
- Verify the spreadsheet ID is correct
- Make sure the API is enabled in Google Cloud Console

### **Data goes to wrong month**
- The script uses `date('F')` which gets the current month
- Make sure your server timezone is correct
- You can hardcode the month temporarily for testing

---

## 🔒 Security Best Practices

1. **Protect JSON key file:**
   ```apache
   <Files "*.json">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

2. **Don't commit credentials to Git:**
   Add to `.gitignore`:
   ```
   *.json
   vendor/
   ```

3. **Use environment variables** (optional):
   ```php
   $spreadsheetId = getenv('GOOGLE_SHEETS_ID');
   ```

4. **Limit service account permissions:**
   - Only give access to specific spreadsheet
   - Use Editor role, not Owner

---

## 💰 Cost

**FREE** for typical usage:
- Google Sheets API: 500 requests per 100 seconds per project
- Your form submissions will be well under this limit
- No charges for API usage at this scale

---

## 📝 Example Row in Google Sheets

| Timestamp | Name | Phone | Email | Address | Service | Contact Method | Best Time | Message | Call Date | Call Time | Status | Notes |
|-----------|------|-------|-------|---------|---------|----------------|-----------|---------|-----------|-----------|--------|-------|
| 2026-09-10 14:30:15 | John Smith | 0424 262 102 | john@email.com | 123 Main St, Perth | Residential Window Cleaning | Phone Call | 11:30 AM Today | Need all windows cleaned | 2026-09-10 | 11:30 | New | |

---

## 🎯 Next Steps

After setup is complete:
1. Test with multiple form submissions
2. Verify data appears in correct month tab
3. Set up monthly tabs in advance (October, November, etc.)
4. Consider adding data validation or conditional formatting in sheets
5. Create charts/reports from the tracked data

---

## 📞 Support

If you encounter issues:
1. Check server error logs
2. Verify all credentials are correct
3. Test API connection separately
4. Review Google Cloud Console for API errors
