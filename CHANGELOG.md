# Changelog — Arnes S3

All notable changes to this project are documented in this file.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.0.8] — 2026-02-17

### Hotfix — UI Layout & Empty Space Fixes

### Fixed
- Resolved box height and empty space issues in admin tabs
- Content-based sizing applied across all sections; no more forced fixed heights
- Responsive layout improvements throughout the admin interface

---

## [1.0.7] — 2026-02-16

### Hotfix — Endpoint Default & Font Awesome CDN

### Fixed

**Issue #1: Missing `https://` in default endpoint** ⚠️ Critical
- **Problem:** Default value in `settings.php` was `'shramba.arnes.si'` without `https://`
- **Impact:** Even with the placeholder, the field displayed the value without a protocol prefix
- **Fix:** Changed default to `'https://shramba.arnes.si'`
- **File:** `includes/settings.php`, line 22
- **Why it matters:** The S3 endpoint *must* include `https://` or the connection will fail

**Issue #2: Font Awesome Kit restricted to a single domain** ⚠️ Critical
- **Problem:** The Font Awesome Kit `https://kit.fontawesome.com/39890f1c0e.js` was registered only for the developer's domain
- **Impact:** Icons did not display on other domains (staging environments, other users' installations)
- **Fix:** Replaced the domain-locked kit with a universal CDN version:
  ```php
  // Before (single domain only):
  wp_enqueue_script('font-awesome-7',
      'https://kit.fontawesome.com/39890f1c0e.js', [], '7.0.0', false);

  // After (works everywhere):
  wp_enqueue_style('font-awesome-7',
      'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
      [], '6.5.1');
  ```
- **File:** `includes/admin/admin-page.php`, lines 39–44
- **Version:** Font Awesome 6.5.1 — latest stable free version on Cloudflare CDN

### Technical Notes
- The endpoint default must be valid (with `https://`) for the S3 connection to work even when the user hasn't changed the field
- Font Awesome CDN works on all domains without restrictions; the trade-off is a slightly slower first load (mitigated by browser caching)

---

## [1.0.6] — 2026-02-02

### Critical Fixes — First-Install Experience

### Fixed

**Issue #1: Endpoint field missing `https://` placeholder**
- Before: The "S3 Endpoint" field had no placeholder text
- After: Placeholder `https://shramba.arnes.si` visible in the field
- Location: Connection tab, line 273

**Issue #2: Organisation ID placeholder showed developer's own ID**
- Before: Placeholder value `"26"` (developer's organisation ID)
- After: Empty field — no placeholder
- Reason: Each user has their own ID; showing `"26"` was misleading
- Location: Connection tab, line 305

**Issue #3: Statistics tab showed phantom data before configuration** ⚠️ Critical
- Before: The Statistics tab displayed WordPress attachment counts before any S3 connection was configured
- Impact: New users saw numbers that appeared incorrect or fabricated
- After: When credentials are not configured, the tab shows an informational notice instead of statistics:
  ```
  ⓘ Configuration required
  Statistics will be available once you configure your Arnes S3 connection.
  [Go to Connection tab]
  ```
- Logic: Checks whether `access_key`, `secret_key`, and `org_id` are filled in
- Result: Eliminates confusion during first-time setup

### UX Improvements
- Cleaner first-time user experience
- Removed the misleading `"26"` placeholder
- Added `https://` protocol hint in the endpoint field
- Statistics are only shown after the plugin is properly configured

---

## [1.0.5] — 2026-01-24

### Bug Fix — Missing Label for Unknown File Types

### Fixed
- **Statistics tab — file type breakdown:** Added missing "Other" label for unknown file types
  - Before: Only a paperclip icon with no text label
  - After: Paperclip icon + "Other" label displayed correctly

### Added
- **Font file type support in Statistics:**
  - Added Font Awesome icon for fonts: `<i class="fa-solid fa-font arnes-icon-sm"></i>`
  - Added label: "Fonts"
  - Font files now display as a distinct category instead of falling through to "Other"

### Technical
- Before: `ucfirst( $type_data['type'] )` — capitalised raw MIME type (could result in empty output)
- After: `'Other'` — clear fallback label for all unrecognised types
- Added font support to both `$icon_map` and `$type_labels` arrays

---

## [1.0.4] — 2026-01-22

### Full Font Awesome Integration — Statistics Tab

### Changed
- Replaced all emoji characters in the Statistics tab with Font Awesome icons for a consistent, professional appearance

**Section headings:**
- 📊 → `<i class="fa-solid fa-chart-pie">` Media Library Overview
- 📁 → `<i class="fa-solid fa-folder">` File Type Breakdown
- 💾 → `<i class="fa-solid fa-hard-drive">` Storage Size
- ⏱️ → `<i class="fa-solid fa-clock">` Last Bulk Upload
- ⚙️ → `<i class="fa-solid fa-gear">` Current Settings

**File type icons:**
- 🖼️ → `fa-image` Images
- 📄 → `fa-file-pdf` Documents
- 🎥 → `fa-video` Video
- 🎵 → `fa-music` Audio
- 📝 → `fa-file-lines` Text
- 📎 → `fa-paperclip` Other

**Status icons:**
- ✓ → `fa-circle-check` (green) Enabled
- ✗ → `fa-circle-xmark` (red/orange) Disabled

**Delivery icons:**
- ☁️ → `fa-cloud` Arnes S3
- 📡 → `fa-network-wired` CDN

**Notice icons:**
- 💡 → `fa-lightbulb` Tip
- ⚠️ → `fa-triangle-exclamation` Warning

### Result
- Statistics tab is now 100% emoji-free
- Uniform CSS classes (`.arnes-icon`, `.arnes-icon-sm`, `.arnes-icon-success`, `.arnes-icon-error`, `.arnes-icon-warning`) applied consistently across the tab

---

## [1.0.3] — 2026-01-08

### Font Awesome Integration

### Added
- **Font Awesome 7 Free kit** integrated for professional admin icons
- Script loaded only on plugin admin pages — zero frontend impact
- Uniform CSS class system for consistent icon sizing and colour

**Tab navigation icons:**
- Connection: plug icon
- Settings: sliders icon
- Bulk Upload: cloud-arrow-up icon
- Tools: toolbox icon
- Statistics: chart-line icon

**CSS classes introduced:**
- `.arnes-icon` — default icons (blue, 16px)
- `.arnes-icon-success` — green success icons
- `.arnes-icon-error` — red error icons
- `.arnes-icon-warning` — orange warning icons
- `.arnes-icon-info` — blue info icons
- `.arnes-icon-sm` — smaller inline icons (14px)

### Technical
- Font Awesome loaded conditionally on `media_page_arnes-s3` only
- `crossorigin` attribute added for secure script loading
- ~70 KB (gzipped) — no impact on frontend performance

---

## [1.0.2] — 2025-12-22

### Phase 5 — Statistics Tab & Bug Fixes

### Added
**Statistics Tab (Phase 5 complete):**
- Media Library overview: total attachments, files in S3, local-only count
- Visual S3 coverage progress bar
- File type breakdown with per-type S3 coverage and progress bars (images, documents, video, audio)
- Storage size summary: local vs S3, potential disk space savings
- Last bulk upload statistics
- Current plugin settings summary (connection, delivery mode, image quality)

**Visual enhancements:**
- File type icons for each category
- Colour-coded indicators (green = S3, red = local-only, blue = CDN)
- Progress bars for visual coverage representation
- Contextual tips and notices for the user

### Fixed
**JavaScript bug — CDN domain field visibility:**
- CDN domain field failed to show/hide when toggling between "Arnes S3" and "CDN" radio buttons
- Root cause: `getElementById('cdn_domain_row')` used underscores; correct ID uses hyphens (`cdn-domain-row`)
- Field now shows/hides instantly on radio button click without requiring a page save

### Technical
- Statistics calculated directly from the WordPress database
- Uses the `postmeta` table to detect S3 status via `_arnes_s3_object` key
- Optimised SQL queries for fast data retrieval
- Fully compatible with existing bulk upload functionality

---

## [1.0.1] — 2025-12-19

### UI Reorganisation — Settings Tab

### Changed
**Settings tab — new section order:**
1. Automatic upload
2. Keep local files
3. File delivery mode (S3 / CDN)
4. Image quality settings
5. Image format priority

**Reason for change:** The previous order placed advanced settings (image quality, format priority) before basic operational settings. The new order follows a logical flow: basic → delivery → advanced.

**Instruction panel (right column):**
- Re-numbered instructions (1–5) to match the new section order
- Added section "5. Image Format Priority" to the instructions
- Improved explanations for each section

### Fixed
- Removed duplicate sections in the Settings tab that remained from earlier un-numbered versions
- Consistent 1–5 numbering applied across both the left content column and right instruction column

---

## [1.0.0] — 2025-12-12 🎉 Stable Release

### Phase 4.5 — Image Format Priority & UI Polish

This is the feature-complete release covering all planned development phases.

### Added
**Image Format Priority (Phase 4.5):**
- New setting in the Settings tab: "Image Format Priority"
- Two options:
  - **WebP First, AVIF Second** — WordPress default; ~97% browser compatibility
  - **AVIF First, WebP Second** — best compression; ~90% browser compatibility
- Backend filter `arnes_s3_reorder_image_formats()` reorders formats in the `srcset` attribute
- Uses WordPress filter `wp_calculate_image_srcset`
- The browser selects the first format in the `srcset` list that it supports

**How it works:**
- WordPress generates multiple versions of each image: `original.jpg`, `original.jpg.webp`, `original.jpg.avif`
- These are included in the `<img srcset="...">` attribute
- **WebP First:** `srcset="…webp 800w, …avif 800w, …jpg 800w"` — browser picks WebP if supported
- **AVIF First:** `srcset="…avif 800w, …webp 800w, …jpg 800w"` — browser picks AVIF if supported

**AVIF First advantages:**
- Files are 30–50% smaller than WebP at equivalent quality
- Supported by Chrome 85+, Firefox 93+, Safari 16+

**WebP First advantages:**
- Higher compatibility (~97% vs ~90%)
- WordPress default behaviour
- Supported by all modern browsers

### Changed
**UI improvements:**
- Sync & Maintenance section: corrected left border colours for each sub-section
  - Re-sync S3 Metadata: blue (`#2271b1`)
  - Bulk Delete local copies: red (`#d63638`)
  - Integrity Check: green (`#00a32a`)
- WordPress native styling applied consistently across all tabs

**Backend:**
- New setting `arnes_s3_format_priority` registered in `settings.php`
- Setting registered in `admin-settings.php` with sanitize callback
- Filter function added to `image-quality.php`
- `arnes_s3_sanitize_format_priority()` ensures only valid values are stored

### Files changed
- `includes/settings.php`
- `includes/admin/admin-settings.php`
- `includes/admin/admin-page.php`
- `includes/image-quality.php`

---
