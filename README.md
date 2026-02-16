# Arnes S3 - WordPress Media Offloading Plugin

**Automatic WordPress media upload to Arnes Shramba (Slovenia's S3-compatible cloud storage)**

[![Version](https://img.shields.io/badge/version-1.0.7-blue.svg)]()
[![WordPress](https://img.shields.io/badge/wordpress-6.5%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

---

## 📖 Table of Contents

- [About](#about)
- [Why This Plugin?](#why-this-plugin)
- [Key Features](#key-features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [CDN Integration](#cdn-integration)
- [FAQ](#faq)
- [Support](#support)
- [License](#license)

---

## About

**Arnes S3** is a WordPress plugin that automatically uploads your media files (images, PDFs, videos, fonts) to **Arnes Shramba**, Slovenia's national S3-compatible object storage service provided by Arnes (Academic and Research Network of Slovenia).

The plugin seamlessly integrates with WordPress Media Library, allowing you to:
- ✅ Offload media files to cloud storage to save disk space
- ✅ Serve files directly from S3 or via CDN for faster delivery
- ✅ Bulk upload existing media library to S3
- ✅ Monitor storage usage with comprehensive statistics
- ✅ Backup and restore your entire media library

---

## Why This Plugin?

Existing WordPress media offloading plugins (like WP Offload Media) only support major cloud providers:
- ❌ AWS S3
- ❌ Google Cloud Storage
- ❌ DigitalOcean Spaces

**They do NOT support custom S3 endpoints** which requires:
- Custom endpoint (`shramba.arnes.si`)
- Organization ID in URL structure
- Folder-based organization (not multiple buckets)

**This plugin was specifically built to support Arnes Shramba's unique infrastructure.**

---

## Key Features

### 🚀 Automatic Upload
- Automatically uploads new media files to Arnes S3 when uploaded via WordPress
- Supports all media types: images, PDFs, videos, audio, fonts
- Uploads all WordPress-generated sizes (thumbnails, WebP, AVIF)

### 📁 Bulk Upload
- Upload ALL existing media library files to S3 with one click
- Real-time progress tracking with visual progress bar
- Filter by date range, file type, or size
- Upload only missing files or overwrite all
- Pause/Resume functionality
- Detailed upload statistics

### 🌐 URL Rewriting & CDN Support
- Serve files directly from Arnes S3
- Optional CDN integration (Cloudflare, etc.)
- Automatic URL rewriting for `<img>`, `<picture>`, and srcset
- Works with page builders and custom themes

### 💾 Storage Management
- Choose to keep or delete local files after upload
- Save up to 90%+ disk space by removing local copies
- Files safely stored in Arnes S3

### 🛠️ Backup & Restore
- Create ZIP backups of entire media library
- Backup from local files or directly from S3
- Download and restore files from S3 to local server
- Essential for disaster recovery

### 🔄 Sync & Maintenance Tools
- Re-sync metadata for files in S3
- Verify file integrity between local and S3
- Bulk delete local copies of files already in S3
- Fix missing metadata automatically

### 📊 Comprehensive Statistics
- Total files in WordPress vs S3
- File type breakdown (images, documents, videos, etc.)
- Storage size calculations
- Coverage percentage with visual progress bars
- Last bulk upload statistics

### 🎨 Image Quality Control
- Adjust JPEG, WebP, and AVIF compression levels
- Choose format priority (WebP-first or AVIF-first)
- WordPress-native image processing
- Balance quality vs file size

### 🌍 Multisite Compatible
- Works with WordPress Multisite networks
- Each site gets its own folder structure
- Separate statistics per site

### 🇸🇮 Slovenian Interface
- Fully translated Slovenian UI
- Professional admin interface with Font Awesome icons
- WordPress-native styling

---

## System Requirements

### Minimum Requirements
- **WordPress:** 6.5 or higher
- **PHP:** 7.4 or higher
- **Arnes Shramba:** Active account with S3 credentials
- **PHP Extensions:** `curl`, `json`, `mbstring`, `xml`

### Recommended
- **WordPress:** 6.9+
- **PHP:** 8.0+
- **Image Library:** Imagick (for WebP/AVIF support)
- **Memory:** 256MB+ PHP memory limit

### Server Requirements
- Write permissions on `/wp-content/uploads/`
- Ability to make external HTTPS connections
- WP-Cron enabled (for bulk operations)

---

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest `arnes-s3-v107.zip`
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**
6. Set it up in WP Admin under Media > **Arnes S3**

### Method 2: Manual Installation

1. Download and unzip the plugin
2. Upload the `arnes-s3` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Find "Arnes S3" and click **Activate**
5. Set it up in WP Admin under Media > **Arnes S3**

### Method 3: WP-CLI

```bash
wp plugin install arnes-s3-v107.zip --activate
```

---

## Configuration

### Step 1: Get Arnes Shramba Credentials

1. Log in to [Arnes Shramba](https://shramba.arnes.si)
2. Go to your organization settings
3. Generate S3 API credentials
4. Note your **Organization ID** (e.g., `73`, `26`, etc.)
5. Copy your **Access Key** and **Secret Key**

### Step 2: Create Bucket/Folder

**Important:** The plugin does NOT create buckets or folders automatically.

1. Using MinIO Client, Cyberduck, or Arnes web interface
2. Navigate to bucket: `arnes-shramba`
3. Create your folder/prefix (e.g., `your-domain`)

### Step 3: Configure Plugin

1. Go to **WordPress Admin → Media → Arnes S3**
2. Go to **Povezava (Connection)** tab
3. Fill in the fields:
   - **S3 Endpoint:** `https://shramba.arnes.si`
   - **Bucket:** `arnes-shramba`
   - **Folder/Prefix:** `your-domain` (the folder you created)
   - **Organization ID:** Your numeric org ID (e.g., `73`)
   - **Access Key:** Your S3 access key
   - **Secret Key:** Your S3 secret key
4. Click **Preveri povezavo (Test Connection)**
5. If successful, click **Shrani spremembe (Save Changes)**

---

## Usage Guide

### Tab 1: Povezava (Connection)

**Purpose:** Configure S3 connection credentials

**Fields:**
- **S3 Endpoint:** Arnes Shramba URL (default: `https://shramba.arnes.si`)
- **Bucket:** Root bucket name (default: `arnes-shramba`)
- **Folder/Prefix:** Your custom folder inside the bucket
- **Organization ID:** Your organization's numeric identifier
- **Access Key:** S3 authentication key
- **Secret Key:** S3 secret key

**Actions:**
- **Test Connection:** Verifies credentials and bucket access
- **Save Changes:** Saves configuration to database

---

### Tab 2: Nastavitve (Settings)

**Purpose:** Control plugin behavior

#### Automatic Upload
- ✅ **Enable:** New files automatically upload to S3
- ⬜ **Disable:** Files stay local (manual upload via Bulk Upload)

#### Keep Local Files
- ✅ **Enable:** Files exist in BOTH S3 and local server (redundancy)
- ⬜ **Disable:** Files ONLY in S3 (saves disk space)

**Recommendation:**
- Enable during migration for safety
- Disable after confirming everything works to save space

#### File Delivery Method
- **From Arnes S3:** Files served directly from `shramba.arnes.si`
- **Via CDN:** Files served through your CDN domain (faster, custom domain)

#### Image Quality
Adjust compression levels:
- **JPEG Quality:** 1-100% (default: 82%)
- **WebP Quality:** 1-100% (default: 82%)
- **AVIF Quality:** 1-100% (default: 82%)

**Guidelines:**
- 90-100%: Excellent quality, large files
- 80-89%: Great quality, reasonable size ✅ **Recommended**
- 70-79%: Good quality, smaller files
- <70%: Visible quality loss

#### Image Format Priority
- **WebP → AVIF:** WordPress default, maximum compatibility (~97% browsers)
- **AVIF → WebP:** Better compression (~90% browsers)

---

### Tab 3: Množično nalaganje (Bulk Upload)

**Purpose:** Upload existing media library files to S3

#### How It Works
1. **Scan Library:** Plugin scans all WordPress media files
2. **Filter Files:** Choose which files to upload
3. **Upload:** Files uploaded in batches (10 at a time)
4. **Track Progress:** Real-time progress bar and statistics

#### Filtering Options
- **Date Range:** Upload files from specific time period
- **File Type:** Images, PDFs, Videos, Audio, etc.
- **File Size:** Min/max size in MB
- **Upload Mode:**
  - **Missing Only:** Upload only files NOT in S3 (recommended)
  - **All Files:** Upload everything (overwrites existing)

#### Dry Run Mode
Test upload without actually uploading files:
- ✅ Shows which files would be uploaded
- ✅ No files actually uploaded
- ✅ Safe way to test filters

#### Progress Features
- Real-time progress bar
- Current file name
- Upload speed (files/second)
- Estimated time remaining
- Success/Error counters
- **Pause/Resume:** Stop and continue later
- **Cancel:** Abort upload

#### Resume Functionality
If interrupted (browser closed, internet drops):
1. Return to Bulk Upload tab
2. Click **Resume** button
3. Upload continues from where it stopped

---

### Tab 4: Orodja (Tools)

**Purpose:** Advanced management tools

#### 1. Backup Media Library

Create ZIP archive of entire media library.

**Options:**
- **Source:**
  - From Local Files
  - From Arnes S3
- **File Types:** Images, Documents, Fonts, Videos, Other

**Process:**
1. Select source and file types
2. Click **Scan Files**
3. Review count and size
4. Click **Create Backup**
5. Download ZIP when ready

**⚠️ Warning:** Backups stored on server (uses disk space). Download externally for true safety.

#### 2. Restore from Arnes S3

Download files from S3 back to local server.

**Modes:**
- **Missing Only:** Download only files that don't exist locally
- **All Files:** Download everything (overwrites existing)

**When to Use:**
- After deleting local files to save space
- Migrating to new server
- Before deactivating plugin
- After accidental file deletion

#### 3. Sync & Maintenance

**Re-sync Metadata:**
- Fixes files in S3 without WordPress metadata
- Updates database with S3 file locations

**Delete Local Copies:**
- Bulk delete local files that exist in S3
- Verifies S3 existence before deleting
- Saves disk space

**Verify Integrity:**
- Check consistency between local and S3
- File existence and size validation
- Identify corrupted files

---

### Tab 5: Statistika (Statistics)

**Purpose:** Monitor storage usage and coverage

**Note:** Statistics only appear AFTER configuring S3 credentials.

#### Overview Section
- Total media files in WordPress
- Files uploaded to S3 (count + percentage)
- Files only on local server
- Visual progress bar showing S3 coverage

#### File Type Breakdown
Table showing statistics per file type:
- Images, Documents, Videos, Audio, Text, Fonts, Other
- Total count, In S3, Local only
- Coverage percentage with color-coded bars:
  - 🟢 Green (≥80%): Excellent coverage
  - 🟡 Yellow (50-79%): Good coverage
  - 🔴 Red (<50%): Poor coverage

#### Storage Size
- Total size of local files
- Approximate size in S3
- Potential space savings (if "keep local" is OFF)

#### Last Bulk Upload
Statistics from most recent bulk upload:
- Date and time
- Total files processed
- Successfully uploaded
- Errors (if any)
- Execution time

#### Current Settings
Quick view of active configuration:
- S3 connection details
- Auto-upload status
- Keep local status
- Delivery method (S3/CDN)
- Image quality settings

---

## CDN Integration

### Why Use CDN?

**Benefits:**
- ⚡ Faster loading (files from nearest location)
- 🌍 Global reach (CDN nodes worldwide)
- 📉 Reduced S3 costs (caching)
- 🔒 DDoS protection
- 🎯 Custom domain (`cdn.yourdomain.com`)

### Cloudflare Setup (Free Plan)

#### Step 1: Add Domain to Cloudflare
1. Sign up at [Cloudflare](https://cloudflare.com)
2. Add your domain
3. Update nameservers at domain registrar
4. Wait for DNS propagation (5-60 minutes)

#### Step 2: Create CDN Subdomain
1. Go to **DNS** in Cloudflare
2. Add **CNAME** record:
   - **Name:** `cdn` (or `assets`, `media`)
   - **Target:** `shramba.arnes.si`
   - **Proxy status:** ✅ **Proxied** (orange cloud)
   - **TTL:** Auto

#### Step 3: Configure Cache Rules
1. Go to **Caching → Cache Rules**
2. Create rule:
   - **Name:** "Cache Media Files"
   - **Match:** `cdn.yourdomain.com/*`
   - **Then:** Cache Level: **Cache Everything**
   - **Edge Cache TTL:** 1 month
   - **Browser Cache TTL:** 1 day

#### Step 4: Configure Plugin
1. Go to **Tab 2 (Nastavitve)**
2. Select **Via CDN**
3. Enter: `https://cdn.yourdomain.com`
4. Save changes

#### Step 5: Test
Upload a test image and verify URLs use `cdn.yourdomain.com`

**Before:**
```html
<img src="https://shramba.arnes.si/73:arnes-shramba/folder/image.jpg">
```

**After:**
```html
<img src="https://cdn.yourdomain.com/folder/image.jpg">
```

---

## FAQ

### General Questions

**Q: Does this work with other cloud providers?**  
A: No. This plugin is specifically designed for Arnes Shramba's unique URL structure. It won't work with AWS S3, Google Cloud, DigitalOcean, etc.

**Q: Will this slow down my site?**  
A: No. Serving files from S3/CDN is usually FASTER than serving from your server, especially for visitors far from your server location.

**Q: What happens if Arnes Shramba goes down?**  
A: If "Keep local files" is enabled, WordPress will serve local copies. If disabled, media won't load until S3 is back online.

**Q: Can I switch back to local storage?**  
A: Yes. Use Tab 4 → Restore from S3 to download all files, then deactivate the plugin.

**Q: Does this work with page builders?**  
A: Yes. Works with Gutenberg, Elementor, Divi, Beaver Builder, etc. It hooks into WordPress core.

**Q: Will it upload my theme images?**  
A: No. Only files uploaded through WordPress Media Library. Theme files and plugin assets are not affected.

**Q: Can I use this on shared hosting?**  
A: Yes, as long as your host allows external connections and meets PHP requirements.

**Q: Does it work with WooCommerce?**  
A: Yes. Product images uploaded through Media Library are handled automatically.

**Q: What about image thumbnails?**  
A: All WordPress-generated sizes (thumbnails, medium, large, WebP, AVIF) are automatically uploaded.

**Q: Can I change S3 credentials after setup?**  
A: Yes. Update Tab 1 fields and save. Existing files remain in S3.

**Q: Is there a file size limit?**  
A: Depends on your PHP settings (`upload_max_filesize`, `post_max_size`) and Arnes Shramba limits.

**Q: Does it support video files?**  
A: Yes. MP4, WebM, and other video formats are supported.

**Q: Can I exclude certain files from upload?**  
A: Not currently. All Media Library files are uploaded (based on auto-upload setting).

### Technical Questions

**Q: How does URL rewriting work?**  
A: Plugin uses WordPress filters (`wp_get_attachment_url`, `wp_calculate_image_srcset`) to rewrite media URLs at PHP level. No nginx configuration needed.

**Q: What's the S3 URL structure?**  
A: `https://shramba.arnes.si/{org_id}:{bucket}/{prefix}/{blog_id}/{year}/{month}/filename.ext`

**Q: How is metadata stored?**  
A: Each uploaded file gets `_arnes_s3_object` post meta with the S3 object key.

**Q: What hook does it use for uploads?**  
A: `wp_generate_attachment_metadata` (priority 999) - fires AFTER all image sizes are generated.

**Q: Can I use this with image optimization plugins?**  
A: Yes, but configure carefully. The upload hook fires LAST to capture optimized files. Some optimization plugins store files in separate directories which won't be uploaded.

---

## Troubleshooting

### Connection Test Failed

**Error:** "Unable to connect to S3"

**Solutions:**
1. Verify endpoint: `https://shramba.arnes.si` (include `https://`)
2. Check access key and secret key
3. Confirm bucket name: `arnes-shramba`
4. Verify folder exists in Arnes Shramba
5. Check Organization ID is correct
6. Ensure server can make HTTPS connections

### Files Not Uploading

**Problem:** Files upload to WordPress but not S3

**Solutions:**
1. Check automatic upload is enabled (Tab 2)
2. Verify connection settings (Tab 1)
3. Check PHP error log
4. Ensure uploads directory is writable
5. Check PHP memory limit (256MB+)

**Debug:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
// Check /wp-content/debug.log
```

### Images Not Displaying

**Problem:** Images show broken on frontend

**Solutions:**
1. Verify files are in S3 (Tab 5 - Statistics)
2. Check URL rewriting is active (Tab 2)
3. Confirm CDN domain is correct (if using CDN)
4. Clear browser cache and CDN cache
5. Test S3 URL directly in browser

### Bulk Upload Stalls

**Problem:** Upload stops or times out

**Solutions:**
1. Increase `max_execution_time` (300+)
2. Increase `memory_limit` (256MB+)
3. Check internet connection
4. Try smaller batch size
5. Use Resume feature

### WebP/AVIF Not Generating

**Problem:** Only JPG/PNG uploaded

**Solutions:**
1. Check Tab 5 → System Diagnostics
2. Install Imagick: `sudo apt-get install php-imagick`
3. Verify WordPress 6.5+

### Font Awesome Icons Not Showing

**Problem:** Icons missing in admin interface

**Solution:**
Icons now load from CDN and work on all domains. If still not showing:
1. Clear browser cache
2. Check browser console for errors
3. Verify internet connection (CDN access)

---

## Support

### Getting Help

- **Documentation:** This README
- **GitHub Issues:** [Report bugs or request features](https://github.com/yourusername/arnes-s3/issues)
- **Email:** info@squarebow.com
- **Arnes Support:** For S3 credential issues, contact Arnes directly

### Before Asking for Help

Please provide:
1. WordPress version
2. PHP version
3. Plugin version (currently 1.0.7)
4. Error messages (from debug.log)
5. Steps to reproduce issue
6. Browser console errors (if frontend issue)

### Known Limitations

- Only works with Arnes Shramba (no other S3 providers)
- Does not create buckets/folders automatically
- No built-in image optimization (uses WordPress native)
- Cannot exclude specific files from upload
- Resume function expires after 24 hours

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

### Latest Version: 1.0.7 (2026-02-13)

**Critical Fixes:**
- ✅ Fixed endpoint default to include `https://`
- ✅ Removed confusing organization ID placeholder
- ✅ Statistics tab now requires configuration before displaying data
- ✅ Font Awesome switched to CDN (works on all domains)

**What's New in v1.0:**
- ✨ Complete admin interface with 5 tabs
- ✨ Bulk upload with progress tracking
- ✨ Backup and restore functionality
- ✨ Comprehensive statistics dashboard
- ✨ Image quality controls
- ✨ CDN support
- ✨ Font Awesome icons
- ✨ Slovenian localization

---

## Credits

- **Author:** Aleš Lednik, SquareBow
- **Contributors:** Community contributors welcome
- **AWS SDK:** Amazon Web Services (optimized for S3 only)
- **Arnes:** Academic and Research Network of Slovenia
- **Font Awesome:** Icon library

---

## License

This plugin is licensed under the **GPL-2.0-or-later** license.

```
Arnes S3 - WordPress Media Offloading Plugin
Copyright (C) 2026 SquareBow

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## Disclaimer

This plugin is not officially affiliated with or endorsed by Arnes. It is a third-party tool designed to work with Arnes Shramba services. Use at your own risk. Always backup your data before using cloud storage plugins.

---

## Roadmap

### Planned Features
- [ ] File exclusion rules (exclude specific file types or sizes)
- [ ] Download statistics from S3 (via CloudFront or analytics)
- [ ] Automatic cleanup of orphaned S3 files
- [ ] Advanced filtering in statistics
- [ ] Export/import configuration
- [ ] Scheduled automatic backups

### Contributions Welcome
We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

---

**Made with ❤️ for the Slovenian WordPress community**

**Version:** 1.0.7  
**Last Updated:** February 16th, 2026  
**Status:** Production Ready ✅
