<?php
/**
 * Admin View: Natural Organic Reservations Registry
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;

$current_status = sanitize_text_field($_GET['status'] ?? '');
$search         = sanitize_text_field($_GET['es_search'] ?? '');
$paged          = max(1, intval($_GET['paged'] ?? 1));
$per_page       = 20;

$results = ES_Reservation_Manager::get_reservations([
    'status'  => $current_status,
    'search'  => $search,
    'limit'   => $per_page,
    'offset'  => ($paged - 1) * $per_page,
    'orderby' => sanitize_text_field($_GET['orderby'] ?? 'reservation_date'),
    'order'   => sanitize_text_field($_GET['order'] ?? 'DESC'),
]);

$reservations = $results['items'];
$total        = $results['total'];
$total_pages  = ceil($total / $per_page);

$metrics = ES_Reservation_Manager::get_metrics();
$status_tabs = [
    ''          => ['label' => 'All Bookings', 'count' => $metrics['total']],
    'pending'   => ['label' => 'Pending Review', 'count' => $metrics['pending']],
    'confirmed' => ['label' => 'Confirmed', 'count' => $metrics['confirmed']],
    'rejected'  => ['label' => 'Declined', 'count' => $metrics['rejected']],
    'cancelled' => ['label' => 'Cancelled', 'count' => $metrics['cancelled']],
];
?>
<div class="es-wrap">
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem">📋</div>
            <div>
                <span class="es-eyebrow">Ember Spice Dining Registry</span>
                <h1>Guest Reservations</h1>
                <p>Review incoming requests, verify table allocations, and manage dining room seating</p>
            </div>
        </div>
        <div class="es-header-actions">
            <a href="<?php echo admin_url('admin.php?page=es-reservations-tables'); ?>" class="es-btn es-btn-secondary">
                <span>🪑</span> Table Availability
            </a>
            <a href="<?php echo admin_url('admin.php?page=es-reservations-calendar'); ?>" class="es-btn es-btn-forest">
                <span>📅</span> Calendar View
            </a>
        </div>
    </div>

    <!-- Organic Status Tabs -->
    <div class="es-status-tabs">
        <?php foreach ($status_tabs as $status_key => $tab): ?>
            <a href="<?php echo admin_url('admin.php?page=es-reservations&status=' . $status_key . ($search ? '&es_search=' . urlencode($search) : '')); ?>"
               class="es-status-tab <?php echo $current_status === $status_key ? 'es-tab-active' : ''; ?>">
                <?php echo esc_html($tab['label']); ?>
                <span class="es-tab-count"><?php echo $tab['count']; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Natural Toolbar -->
    <div class="es-toolbar">
        <form method="GET" class="es-search-form">
            <input type="hidden" name="page" value="es-reservations">
            <?php if ($current_status): ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($current_status); ?>">
            <?php endif; ?>
            <input type="text" name="es_search" value="<?php echo esc_attr($search); ?>"
                   placeholder="Search by guest name, email, phone, or reference code…" class="es-search-input">
            <button type="submit" class="es-btn es-btn-forest">Search</button>
            <?php if ($search): ?>
                <a href="<?php echo admin_url('admin.php?page=es-reservations&status=' . $current_status); ?>" class="es-btn es-btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
        <span class="es-result-count"><?php echo $total; ?> reservation<?php echo $total !== 1 ? 's' : ''; ?> recorded</span>
    </div>

    <!-- Reservations Table -->
    <div class="es-section-card" style="padding: 0; overflow: hidden;">
        <?php if (empty($reservations)): ?>
            <div class="es-empty-state">
                <span class="es-empty-icon">🌿</span>
                <p>No reservations found<?php echo $search ? " matching «{$search}»" : ''; ?> in this view.</p>
            </div>
        <?php else: ?>
        <div class="es-table-responsive">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest Information</th>
                    <th>Dining Date &amp; Time</th>
                    <th>Covers</th>
                    <th>Assigned Table</th>
                    <th>Status</th>
                    <th>Request Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $r): ?>
                <tr class="es-row-<?php echo esc_attr($r->status); ?>">
                    <td><code class="es-ref"><?php echo esc_html($r->reservation_ref); ?></code></td>
                    <td>
                        <div class="es-guest-info">
                            <strong><?php echo esc_html($r->guest_name); ?></strong>
                            <small><?php echo esc_html($r->guest_email); ?> &bull; <?php echo esc_html($r->guest_phone); ?></small>
                        </div>
                    </td>
                    <td>
                        <div class="es-datetime-info">
                            <strong><?php echo date('M j, Y', strtotime($r->reservation_date)); ?></strong>
                            <small>Seating: <?php echo esc_html($r->reservation_time); ?></small>
                        </div>
                    </td>
                    <td><strong><?php echo intval($r->guest_count); ?></strong> <span style="font-size:11.5px;color:var(--es-text-muted);">Guests</span></td>
                    <td>
                        <?php if ($r->table_number): ?>
                            <span class="es-table-badge">Table <?php echo esc_html($r->table_number); ?></span>
                            <?php if (!empty($r->table_location)): ?>
                                <small style="display:block;color:var(--es-text-muted);font-size:11px;margin-top:2px;"><?php echo esc_html($r->table_location); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="es-unassigned">— Unassigned —</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="es-status es-status-<?php echo esc_attr($r->status); ?>"><?php echo ucfirst(esc_html($r->status)); ?></span></td>
                    <td><small><?php echo date('M j, Y', strtotime($r->created_at)); ?></small></td>
                    <td>
                        <div class="es-action-btns">
                            <button class="es-btn es-btn-sm es-btn-secondary js-view-res" data-id="<?php echo $r->id; ?>" title="View Details">View</button>
                            <?php if ($r->status === 'pending'): ?>
                                <button class="es-btn es-btn-sm es-btn-primary js-approve-res" data-id="<?php echo $r->id; ?>"
                                        data-date="<?php echo esc_attr($r->reservation_date); ?>"
                                        data-time="<?php echo esc_attr($r->reservation_time); ?>"
                                        data-guests="<?php echo $r->guest_count; ?>"
                                        title="Approve &amp; Assign Table">✓ Approve</button>
                                <button class="es-btn es-btn-sm es-btn-danger js-reject-res" data-id="<?php echo $r->id; ?>" title="Decline Request">✗ Decline</button>
                            <?php elseif ($r->status === 'confirmed'): ?>
                                <button class="es-btn es-btn-sm es-btn-ghost js-cancel-res" data-id="<?php echo $r->id; ?>" title="Cancel Booking">Cancel</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="es-pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="<?php echo admin_url("admin.php?page=es-reservations&status={$current_status}&paged={$p}" . ($search ? "&es_search=" . urlencode($search) : '')); ?>"
                   class="es-page-btn <?php echo $p === $paged ? 'es-page-current' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modals -->
<?php require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/partials/modal-detail.php'; ?>
