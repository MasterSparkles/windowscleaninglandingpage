# Window Cleaning Landing Page

Professional window cleaning landing page for Master Sparkle's Cleaning Service.

## 🎨 Features

### Design
- ✅ Modern, professional design
- ✅ Responsive mobile-first layout
- ✅ Auto-playing service videos
- ✅ Real project photos & before/after galleries
- ✅ Interactive hover effects
- ✅ Clean typography & color scheme

### Form & Booking
- ✅ Simplified quote form with dropdowns
- ✅ Calendar time slot booking (15-min intervals, 11am-2pm)
- ✅ "Today" or "Tomorrow" booking options
- ✅ Dynamic contact method selection
- ✅ Real-time slot availability

### Integrations
- ✅ **Google Ads** conversion tracking
- ✅ **Meta Pixel** (Facebook/Instagram) tracking
- ✅ **Email notifications** (admin + customer)
- ✅ **Google Calendar** booking integration
- ✅ **Google Sheets** inquiry tracking

---

## 📁 File Structure

```
landingpageforwindows/
├── index.php                          # Main landing page
├── api/
│   ├── send-email.php                 # Email handler
│   ├── get-calendar-slots.php         # Calendar availability API
│   └── send-to-sheets.php             # Google Sheets logger
├── MS_MEDIA/                          # Images & videos
│   ├── MS_LOGO.png                    # Logo (transparent)
│   ├── windows_inside_view.jpg        # Hero background
│   ├── rod_windows.mp4                # Residential video
│   ├── highreach.mp4                  # High-reach video
│   ├── windowscleaning.mp4            # Commercial video
│   └── [69 total media files]
├── GOOGLE-CALENDAR-SETUP.md           # Calendar integration guide
├── GOOGLE-SHEETS-SETUP.md             # Sheets integration guide
└── README.md                          # This file
```

---

## 🚀 Quick Start

### 1. Upload Files
Upload the entire `landingpageforwindows` folder to your web server.

### 2. Configure Tracking
Edit `index.php` and replace placeholders:
- Line 10-15: Replace `AW-XXXXXXXXX` with your Google Ads ID
- Line 18-29: Meta Pixel ID already set to `26295760630015746`

### 3. Test Form
- Visit your landing page
- Submit a test form
- Check admin emails
- Verify tracking fires

### 4. Setup Integrations (Optional)
Follow the setup guides:
- **Google Calendar:** See `GOOGLE-CALENDAR-SETUP.md`
- **Google Sheets:** See `GOOGLE-SHEETS-SETUP.md`

---

## 📧 Email Configuration

### Admin Emails
Form submissions send to:
- `admin@mastersparkles.com.au`
- `jenniferricana21@gmail.com`
- `leads@mastersparkles.com.au`
- `mastersparklescleaning@gmail.com`

### From Email
- `noreply@mastersparkles.com.au`

To change these, edit `api/send-email.php` lines 60-65.

---

## 📅 Calendar Booking

### Time Slots
- **Interval:** 15 minutes
- **Hours:** 11:00 AM - 2:00 PM
- **Days:** Today or Tomorrow only

### How It Works
1. User selects "Phone Call" as contact method
2. Dropdown shows "Today" or "Tomorrow"
3. System fetches available slots from API
4. User picks a time slot
5. Booking sent to Google Calendar (when configured)

---

## 📊 Google Sheets Tracking

All form submissions automatically log to:
- **Spreadsheet:** Daily Inquiry Audit Tracker
- **Sheet:** Current month (e.g., "September")

### Data Logged
- Timestamp
- Name, Phone, Email
- Address
- Service selected
- Contact method & time
- Call date/time (if phone booking)
- Status (defaults to "New")

---

## 🎯 Tracking Events

### Google Ads
Fires on form submission:
```javascript
gtag('event', 'conversion', {
  'send_to': 'AW-XXXXXXXXX/CONVERSION_LABEL'
});
```

### Meta Pixel
Fires on form submission:
```javascript
fbq('track', 'Lead', {
  content_name: 'Window Cleaning Quote',
  content_category: 'Window Cleaning',
  value: 149.00,
  currency: 'AUD'
});
```

---

## 🎨 Design Colors

```css
--navy: #1e3a5f      /* Primary buttons, headings */
--orange: #ff6b35    /* Call-to-action buttons */
--aqua: #20b7c9      /* Accents, focus states */
--cream: #f3f8fc     /* Background sections */
```

---

## 📱 Mobile Optimization

- Sticky header with logo & CTA
- Responsive grid layouts
- Touch-friendly form elements
- Fixed bottom bar with quote & call buttons
- Auto-playing videos (muted)

---

## 🔧 Customization

### Update Logo
Replace `MS_MEDIA/MS_LOGO.png` with your logo (transparent PNG recommended).

### Change Colors
Edit CSS variables in `index.php` lines 38-50.

### Update Phone Number
Find & replace `0424 262 102` throughout `index.php`.

### Modify Services
Edit dropdown options in `index.php` lines 470-476.

### Change Time Slots
Edit `index.php` lines 795-798:
```javascript
const startHour = 11;      // Start time
const endHour = 14;        // End time
const interval = 15;       // Minutes
```

---

## 📞 Support

### Contact
- **Phone:** 0424 262 102
- **Email:** admin@mastersparkles.com.au

### Documentation
- Google Calendar Setup: `GOOGLE-CALENDAR-SETUP.md`
- Google Sheets Setup: `GOOGLE-SHEETS-SETUP.md`

---

## ✅ Checklist

Before going live:

- [ ] Upload all files to server
- [ ] Replace Google Ads tracking ID
- [ ] Verify Meta Pixel ID
- [ ] Test form submission
- [ ] Check admin emails received
- [ ] Verify tracking events fire
- [ ] Test mobile responsiveness
- [ ] Setup Google Calendar (optional)
- [ ] Setup Google Sheets (optional)
- [ ] Test booking flow end-to-end

---

## 🎯 Version

**Version:** 1.0  
**Last Updated:** September 10, 2026  
**Built For:** Master Sparkle's Window Cleaning Service
