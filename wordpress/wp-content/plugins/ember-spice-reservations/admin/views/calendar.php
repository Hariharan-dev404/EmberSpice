<?php
/**
 * Admin View: Natural Organic Reservation Calendar
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;
?>
<div class="es-wrap">
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem">📅</div>
            <div>
                <span class="es-eyebrow">Ember Spice Dining Schedule</span>
                <h1>Seating &amp; Hearth Calendar</h1>
                <p>Visual reservation timeline — Asia/Kolkata timezone &bull; Live table bookings &amp; guest arrivals</p>
            </div>
        </div>
        <div class="es-header-actions">
            <a href="<?php echo admin_url('admin.php?page=es-reservations'); ?>" class="es-btn es-btn-secondary">
                <span>📋</span> Table Registry
            </a>
            <a href="<?php echo admin_url('admin.php?page=es-reservations-tables'); ?>" class="es-btn es-btn-forest">
                <span>🪑</span> Dining Tables
            </a>
        </div>
    </div>

    <!-- Organic Legend -->
    <div class="es-calendar-legend">
        <span class="es-legend-item"><span class="es-legend-dot" style="background:#788568;"></span>Confirmed Bookings</span>
        <span class="es-legend-item"><span class="es-legend-dot" style="background:#B99A65;"></span>Pending Host Review</span>
        <span class="es-legend-item"><span class="es-legend-dot" style="background:#B86F48;"></span>Declined Requests</span>
        <span class="es-legend-item"><span class="es-legend-dot" style="background:#806B52;"></span>Cancelled</span>
    </div>

    <!-- Calendar Container -->
    <div class="es-section-card">
        <div id="es-calendar-nav" class="es-calendar-nav">
            <button id="es-cal-prev" class="es-btn es-btn-secondary">&larr; Previous Month</button>
            <h2 id="es-cal-title" class="es-cal-title"></h2>
            <button id="es-cal-next" class="es-btn es-btn-secondary">Next Month &rarr;</button>
            <div class="es-cal-view-switcher">
                <button class="es-btn es-btn-ghost js-cal-view" data-view="month">Month</button>
                <button class="es-btn es-btn-ghost js-cal-view" data-view="week">Week</button>
                <button class="es-btn es-btn-ghost js-cal-view active" data-view="today">Today</button>
            </div>
        </div>

        <!-- Month Grid -->
        <div id="es-calendar-month" class="es-calendar-month-view">
            <div class="es-cal-day-headers">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                <div>Thu</div><div>Fri</div><div>Sat</div>
            </div>
            <div id="es-cal-grid" class="es-cal-grid"></div>
        </div>

        <!-- Day/Week List View -->
        <div id="es-calendar-list" class="es-calendar-list-view" style="display:none;">
            <div id="es-cal-list-container"></div>
        </div>
    </div>
</div>

<!-- Reservation popup -->
<div id="es-cal-popup" class="es-cal-popup" style="display:none;">
    <div class="es-cal-popup-inner">
        <button class="es-cal-popup-close" id="es-cal-popup-close">&times;</button>
        <div id="es-cal-popup-content"></div>
    </div>
</div>

<?php require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/partials/modal-detail.php'; ?>
