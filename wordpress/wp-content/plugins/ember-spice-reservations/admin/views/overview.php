<?php
/**
 * Admin View: Natural Organic Overview Dashboard
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;

$metrics = ES_Reservation_Manager::get_metrics();
$today_res = ES_Reservation_Manager::get_reservations([
    'date'  => current_time('Y-m-d'),
    'limit' => 10,
]);
?>
<div class="es-wrap">
    <!-- Header -->
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem" title="Ember Spice Botanical Hearth">🌿</div>
            <div>
                <span class="es-eyebrow">Ember Spice &bull; Botanical Hearth &amp; Farm-to-Table Hospitality</span>
                <h1>Reservation Overview</h1>
                <p><?php echo date('l, F j, Y', current_time('timestamp')); ?> &bull; Dining Room Status &amp; Guest Seating Overview</p>
            </div>
        </div>
        <div class="es-header-actions">
            <a href="<?php echo admin_url('admin.php?page=es-reservations'); ?>" class="es-btn es-btn-primary">
                <span>📋</span> View All Reservations
            </a>
            <a href="<?php echo admin_url('admin.php?page=es-reservations-calendar'); ?>" class="es-btn es-btn-secondary">
                <span>📅</span> Calendar View
            </a>
        </div>
    </div>

    <!-- Organic Metric Cards -->
    <div class="es-metrics-grid">
        <div class="es-metric-card es-metric-total">
            <div class="es-metric-icon">🌾</div>
            <div class="es-metric-value"><?php echo $metrics['total']; ?></div>
            <div class="es-metric-label">Total Reservations</div>
        </div>
        <div class="es-metric-card es-metric-pending">
            <div class="es-metric-icon">⏳</div>
            <div class="es-metric-value"><?php echo $metrics['pending']; ?></div>
            <div class="es-metric-label">Pending Review</div>
            <?php if ($metrics['pending'] > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=es-reservations&status=pending'); ?>" class="es-metric-link">Review Requests &rarr;</a>
            <?php endif; ?>
        </div>
        <div class="es-metric-card es-metric-confirmed">
            <div class="es-metric-icon">🌿</div>
            <div class="es-metric-value"><?php echo $metrics['confirmed']; ?></div>
            <div class="es-metric-label">Confirmed Bookings</div>
        </div>
        <div class="es-metric-card es-metric-today">
            <div class="es-metric-icon">🍽️</div>
            <div class="es-metric-value"><?php echo $metrics['today']; ?></div>
            <div class="es-metric-label">Today's Covers</div>
        </div>
        <div class="es-metric-card es-metric-upcoming">
            <div class="es-metric-icon">🗓️</div>
            <div class="es-metric-value"><?php echo $metrics['upcoming']; ?></div>
            <div class="es-metric-label">Upcoming Confirmed</div>
        </div>
        <div class="es-metric-card es-metric-rejected">
            <div class="es-metric-icon">🍂</div>
            <div class="es-metric-value"><?php echo $metrics['rejected']; ?></div>
            <div class="es-metric-label">Declined / Full</div>
        </div>
        <div class="es-metric-card es-metric-cancelled">
            <div class="es-metric-icon">🪵</div>
            <div class="es-metric-value"><?php echo $metrics['cancelled']; ?></div>
            <div class="es-metric-label">Cancelled</div>
        </div>
    </div>

    <!-- Today's Reservations Quick View -->
    <div class="es-section-card">
        <div class="es-section-header">
            <h2>Today's Seating Schedule &bull; <?php echo date('l, F j, Y', current_time('timestamp')); ?></h2>
            <span class="es-eyebrow" style="margin: 0;"><?php echo count($today_res['items']); ?> Booking(s)</span>
        </div>

        <?php if (empty($today_res['items'])): ?>
            <div class="es-empty-state">
                <span class="es-empty-icon">🌱</span>
                <p>No reservations currently booked for today. Tables are open and ready for walk-ins.</p>
            </div>
        <?php else: ?>
            <div class="es-table-responsive">
            <table class="es-table">
                <thead>
                    <tr>
                        <th>Seating Time</th>
                        <th>Reference</th>
                        <th>Guest Name</th>
                        <th>Party Size</th>
                        <th>Status</th>
                        <th>Assigned Table</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($today_res['items'] as $r): ?>
                    <tr class="es-row-<?php echo esc_attr($r->status); ?>">
                        <td><strong><?php echo esc_html($r->reservation_time); ?></strong></td>
                        <td><code class="es-ref"><?php echo esc_html($r->reservation_ref); ?></code></td>
                        <td>
                            <div class="es-guest-info">
                                <strong><?php echo esc_html($r->guest_name); ?></strong>
                                <small><?php echo esc_html($r->guest_phone); ?></small>
                            </div>
                        </td>
                        <td><?php echo intval($r->guest_count); ?> Guest(s)</td>
                        <td><span class="es-status es-status-<?php echo esc_attr($r->status); ?>"><?php echo ucfirst(esc_html($r->status)); ?></span></td>
                        <td>
                            <?php if ($r->table_number): ?>
                                <span class="es-table-badge">Table <?php echo esc_html($r->table_number); ?></span>
                            <?php else: ?>
                                <span class="es-unassigned">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="es-btn es-btn-sm es-btn-secondary js-view-res" data-id="<?php echo $r->id; ?>">View Details</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Navigation Grid -->
    <div class="es-quick-nav-grid">
        <a href="<?php echo admin_url('admin.php?page=es-reservations'); ?>" class="es-quick-nav-card">
            <span>📋</span>
            <strong>Reservation Registry</strong>
            <small>Review, approve, and manage customer bookings</small>
        </a>
        <a href="<?php echo admin_url('admin.php?page=es-reservations-calendar'); ?>" class="es-quick-nav-card">
            <span>📅</span>
            <strong>Seating Calendar</strong>
            <small>Visual timeline &amp; table assignment planner</small>
        </a>
        <a href="<?php echo admin_url('admin.php?page=es-reservations-tables'); ?>" class="es-quick-nav-card">
            <span>🪑</span>
            <strong>Dining Rooms &amp; Tables</strong>
            <small>Configure capacities, patios &amp; hearth suites</small>
        </a>
        <a href="<?php echo admin_url('admin.php?page=es-reservations-reports'); ?>" class="es-quick-nav-card">
            <span>📊</span>
            <strong>Dining Reports &amp; Export</strong>
            <small>Guest trends, cover counts &amp; CSV export</small>
        </a>
    </div>
</div>

<!-- Reservation Detail Modal -->
<?php require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/partials/modal-detail.php'; ?>
