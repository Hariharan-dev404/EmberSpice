# 🌿 Ember Spice — Restaurant & Reservation Management System

**Live Website:** [https://emberspice.page.gd/](https://emberspice.page.gd/)  
**Design Identity:** Natural Food, Organic Farm-to-Table, Botanical Countryside Dining  
**Stack:** WordPress, Elementor, MySQL, PHP 8+, JavaScript, CSS3 Design System  

---

## 🍽️ Overview

**Ember Spice** is an authentic Indian culinary restaurant platform featuring an artisanal, farm-to-table botanical dining identity. It includes an autonomous, full-featured reservation management and table allocation engine.

---

## 🎨 Visual Design System (Natural Organic Palette)

The entire visual identity (both the public restaurant frontend and private admin dashboard) is governed by an earthy, organic color palette:

- **Deep Forest Green** (`#344B35`): Primary headings, brand emblems, confirmed statuses
- **Sage Green** (`#788568`): Secondary surfaces, table headers, subtle borders
- **Natural Cream** (`#F4EEDC`): Primary canvas foundation & page backgrounds
- **Warm White** (`#FFFCF5`): Card surfaces, modal dialogs, dining panels
- **Earthy Terracotta** (`#B86F48`): Primary action buttons, CTA highlights, decline alerts
- **Wheat Gold** (`#B99A65`): Pending status indicators, eyebrow titles, heritage accents
- **Natural Brown** (`#806B52`): Wood-inspired details, secondary accents
- **Muted Text** (`#777568`): Subtitles, descriptions, timestamps
- **Body Text** (`#2A2C24`): High-contrast readable typography

---

## 🏗️ Architecture & Key Components

### 1. Ember Spice Reservations Plugin (`wordpress/wp-content/plugins/ember-spice-reservations/`)
A custom, zero-dependency WordPress reservation manager:
- **Dining Dashboard (`admin/views/overview.php`):** Botanical stat cards, seating schedule, and quick actions.
- **Reservation Listing (`admin/views/reservations.php`):** Real-time booking table, filtering by status, search, and detail modal.
- **Table Allocation & Approval Workflow (`admin/views/partials/modal-detail.php`):** Interactive dining table assignment with double-booking prevention (90-minute conflict buffer).
- **Interactive Seating Calendar (`admin/views/calendar.php` & `assets/js/calendar.js`):** Monthly and daily grid with color-coded reservation states.
- **Table Management (`admin/views/tables.php`):** Dining room section management (Main Dining, Garden Patio, Hearth Lounge, Private Dining).
- **Reports & Analytics (`admin/views/reports.php`):** Cover metrics, peak evenings, and CSV export.
- **Public Cancellation Page (`public/class-es-public.php`):** Tokenized, one-click guest cancellation with instant table release.
- **Email Notification Engine (`includes/class-es-mailer.php`):** HTML transactional emails for confirmations, pending alerts, approvals, and cancellations.

### 2. Ember Spice Core MU-Plugin (`wordpress/wp-content/mu-plugins/`)
- **`ember-spice-core.php`:** Master engine for page templates, Canvas integration, critical layout overrides, and AJAX endpoints (`wp_ajax_ember_spice_reservation`, `wp_ajax_ember_spice_contact`).
- **`assets/css/ember-spice.css`:** Complete public frontend CSS design system (frosted warm-white navbar, natural cream backgrounds, organic card components, and forest green footer).
- **`assets/js/ember-spice.js`:** Client-side AJAX submission and mobile menu handling.

---

## 🔒 Security & Safe Deployment

- **Database Credentials:** `wp-config.php` is strictly git-ignored. A sanitized sample is provided in `wordpress/wp-config-sample.php`.
- **FTP Credentials:** `.vscode/sftp.json` is strictly git-ignored. A template is provided in `.vscode/sftp.json.example`.
- **Customer Privacy:** No database dumps, personal guest details, or server logs are tracked in this repository.

---

## 🚀 Local Development Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Hariharan-dev404/EmberSpice.git
   ```

2. **Configure WordPress:**
   - Copy `wordpress/wp-config-sample.php` to `wordpress/wp-config.php`.
   - Update `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `DB_HOST` to match your local MySQL configuration.

3. **Configure Remote FTP (Optional):**
   - Copy `.vscode/sftp.json.example` to `.vscode/sftp.json`.
   - Fill in your FTP credentials.

---

## 📄 License
Crafted with passion for Ember Spice Restaurant. All rights reserved.
