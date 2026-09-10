# Before & After Images Upload Instructions

## Images to Upload

You provided 5 before/after images. Please save them with these filenames in the `MS_MEDIA` folder:

### Image 1 - Dirty Glass Door (BEFORE)
**Filename:** `window-door-before.jpg`
- Shows dirty glass door with visible dirt and marks
- Use for "Glass Door Cleaning" BEFORE image

### Image 2 - Window Track Cleaning
**Filename:** `window-track-cleaning.jpg`
- Shows window track being cleaned
- Use for "Window Tracks & Frames" image

### Image 3 - Clean Window View
**Filename:** `window-clean-view.jpg`
- Shows clean window with clear view to backyard
- Use for gallery or after image

### Image 4 - Clean Glass Door (AFTER)
**Filename:** `window-door-after.jpg`
- Shows clean glass door with clear visibility
- Use for "Glass Door Cleaning" AFTER image

### Image 5 - Bathroom Window Before/After
**Filename:** `bathroom-window-before.jpg` (left side)
**Filename:** `bathroom-window-after.jpg` (right side)
- Shows dirty vs clean bathroom window
- Use for "Window & Bathroom View" before/after

---

## How to Upload

1. Save each image from the chat with the filenames above
2. Upload them to: `d:\2025 Desktop\calculator\landingpageforwindows\MS_MEDIA\`
3. Then update the index.php file with the correct paths

---

## Update Code

After uploading, replace the placeholder URLs in `index.php` line 639-647:

```php
// Glass Door Cleaning
<div class="before" style="background-image:url('MS_MEDIA/window-door-before.jpg')">BEFORE</div>
<div class="after" style="background-image:url('MS_MEDIA/window-door-after.jpg')">AFTER</div>

// Window & Bathroom View
<div class="before" style="background-image:url('MS_MEDIA/bathroom-window-before.jpg')">BEFORE</div>
<div class="after" style="background-image:url('MS_MEDIA/bathroom-window-after.jpg')">AFTER</div>
```

---

## Alternative: Use Video Frames

You also mentioned using frames from:
`MS_MEDIA/WhatsApp Video 2026-08-29 at 12.06.04 PM.mp4`

You can extract frames from this video and use them as before/after images.
