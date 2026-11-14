<?php
/**
 * Admin View: Natural Organic Reports & CSV Export
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;

$date_from  = sanitize_text_field($_GET['date_from'] ?? date('Y-m-01'));
$date_to    = sanitize_text_field($_GET['date_to'] ?? date('Y-m-t'));
$rep_status = sanitize_text_field($_GET['rep_status'] ?? '');

$results = ES_Reservation_Manager::get_reservations([
    'date_from' => $date_from,
    'date_to'   => $date_to,
    'status'    => $rep_status,
    'limit'     => 5000,
]);
$items = $results['items'];
$total = $results['total'];

// Compute breakdown
$breakdown = ['pending' => 0, 'confirmed' => 0, 'rejected' => 0, 'cancelled' => 0];
$total_guests = 0;
$by_date = [];
foreach ($items as $item) {
    if (isset($breakdown[$item->status])) {
        $breakdown[$item->status]++;
    }
    $total_guests += intval($item->guest_count);
    $d = $item->reservation_date;
    $by_date[$d] = ($by_date[$d] ?? 0) + 1;
}
arsort($by_date);
$busiest_days = array_slice($by_date, 0, 5, true);
?>
<div class="es-wrap">
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem">📊</div>
            <div>
                <span class="es-eyebrow">Dining Insights &amp; Analytics</span>
                <h1>Reports &amp; Data Export</h1>
                <p>Analyze guest covers, reservation volumes, and download verified CSV records</p>
            </div>
        </div>
        <div class="es-header-actions">
            <a href="<?php echo admin_url('admin.php?page=es-reservations'); ?>" class="es-btn es-btn-secondary">
                <span>📋</span> Table Registry
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="es-section-card">
        <form method="GET" class="es-filter-form">
            <input type="hidden" name="page" value="es-reservations-reports">
            <div class="es-filter-row">
                <div class="es-form-group">
                    <label class="es-label">Date From</label>
                    <input type="date" name="date_from" class="es-input" value="<?php echo esc_attr($date_from); ?>">
                </div>
                <div class="es-form-group">
                    <label class="es-label">Date To</label>
                    <input type="date" name="date_to" class="es-input" value="<?php echo esc_attr($date_to); ?>">
                </div>
                <div class="es-form-group">
                    <label class="es-label">Reservation Status</label>
                    <select name="rep_status" class="es-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php selected($rep_status, 'pending'); ?>>Pending Review</option>
                        <option value="confirmed" <?php selected($rep_status, 'confirmed'); ?>>Confirmed</option>
                        <option value="rejected" <?php selected($rep_status, 'rejected'); ?>>Declined</option>
                        <option value="cancelled" <?php selected($rep_status, 'cancelled'); ?>>Cancelled</option>
                    </select>
                </div>
                <div class="es-form-group es-form-actions-col">
                    <button type="submit" class="es-btn es-btn-forest">Apply Filter</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="es-metrics-grid">
        <div class="es-metric-card es-metric-total">
            <div class="es-metric-icon">🌾</div>
            <div class="es-metric-value"><?php echo $total; ?></div>
            <div class="es-metric-label">Total Reservations</div>
        </div>
        <div class="es-metric-card es-metric-confirmed">
            <div class="es-metric-icon">🌿</div>
            <div class="es-metric-value"><?php echo $breakdown['confirmed']; ?></div>
            <div class="es-metric-label">Confirmed Seated</div>
        </div>
        <div class="es-metric-card es-metric-pending">
            <div class="es-metric-icon">⏳</div>
            <div class="es-metric-value"><?php echo $breakdown['pending']; ?></div>
            <div class="es-metric-label">Pending Requests</div>
        </div>
        <div class="es-metric-card es-metric-today">
            <div class="es-metric-icon">👥</div>
            <div class="es-metric-value"><?php echo $total_guests; ?></div>
            <div class="es-metric-label">Total Guest Covers</div>
        </div>
        <div class="es-metric-card es-metric-rejected">
            <div class="es-metric-icon">🍂</div>
            <div class="es-metric-value"><?php echo $breakdown['rejected']; ?></div>
            <div class="es-metric-label">Declined Requests</div>
        </div>
        <div class="es-metric-card es-metric-cancelled">
            <div class="es-metric-icon">🪵</div>
            <div class="es-metric-value"><?php echo $breakdown['cancelled']; ?></div>
            <div class="es-metric-label">Cancelled</div>
        </div>
    </div>

    <!-- Busiest Dining Days -->
    <?php if (!empty($busiest_days)): ?>
    <div class="es-section-card">
        <div class="es-section-header">
            <h2>Peak Dining Evenings (Selected Period)</h2>
        </div>
        <table class="es-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Confirmed Bookings</th>
                    <th>Activity Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($busiest_days as $d => $cnt): ?>
                <tr>
                    <td><strong><?php echo date('l, F j, Y', strtotime($d)); ?></strong></td>
                    <td><strong style="color:var(--es-forest);font-size:15px;"><?php echo $cnt; ?></strong> reservations</td>
                    <td><span class="es-status es-status-confirmed">High Demand</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- CSV Export Section -->
    <div class="es-section-card">
        <div class="es-section-header">
            <h2>Export Clean Dining Records</h2>
        </div>
        <p class="es-help" style="margin-bottom:18px;font-size:13.5px;">Export filtered reservation records to CSV format for financial reporting, guest analytics, or catering logs. Includes formula injection protection and UTF-8 encoding for Excel &amp; Google Sheets.</p>
        <form method="POST">
            <?php wp_nonce_field('es_export_reservations_nonce'); ?>
            <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            <input type="hidden" name="rep_status" value="<?php echo esc_attr($rep_status); ?>">
            <button type="submit" name="es_export_csv" class="es-btn es-btn-primary">
                <span>⬇</span> Download CSV Export (<?php echo $total; ?> records)
            </button>
        </form>
    </div>
</div>
