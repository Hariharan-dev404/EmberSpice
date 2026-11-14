<?php
/**
 * Admin View: Natural Organic Reservation Settings
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;

$admin_email      = get_option('es_admin_notification_email', '23it011@psr.edu.in');
$seating_duration = get_option('es_seating_duration', 90);
$cron_next        = wp_next_scheduled(ES_Cron::HOOK_NAME);
?>
<div class="es-wrap">
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem">⚙️</div>
            <div>
                <span class="es-eyebrow">Restaurant Configuration</span>
                <h1>Reservation Settings</h1>
                <p>Manage notification recipients, dining turn times, and automated reminder schedules</p>
            </div>
        </div>
        <div class="es-header-actions">
            <a href="<?php echo admin_url('admin.php?page=es-reservations'); ?>" class="es-btn es-btn-secondary">
                <span>📋</span> View Registry
            </a>
        </div>
    </div>

    <?php if (isset($_GET['saved']) && $_GET['saved'] == 1): ?>
        <div class="es-alert es-alert-success">✓ Settings updated successfully.</div>
    <?php endif; ?>

    <form method="POST">
        <?php wp_nonce_field('es_save_settings_nonce'); ?>

        <!-- Email Notification Settings -->
        <div class="es-section-card">
            <div class="es-section-header">
                <h2>Email Notification &amp; Delivery</h2>
            </div>
            <div class="es-form-group">
                <label class="es-label" for="es_admin_notification_email">Host Desk Notification Email</label>
                <input type="email" id="es_admin_notification_email" name="es_admin_notification_email"
                       class="es-input" style="max-width: 420px;" value="<?php echo esc_attr($admin_email); ?>">
                <small class="es-help">All newly submitted table requests and guest self-service cancellations will be delivered to this address.</small>
            </div>
            <div class="es-form-group">
                <label class="es-label">Outgoing Mail Integration</label>
                <div class="es-info-row">
                    <span class="es-status es-status-confirmed">✓ FluentSMTP Connected &amp; Operational</span>
                    <small style="color:var(--es-text-muted);">Emails use your configured FluentSMTP sender (<code>hariharan.moct@gmail.com</code>). No secondary SMTP setup required.</small>
                </div>
            </div>
        </div>

        <!-- Seating & Dining Room Settings -->
        <div class="es-section-card">
            <div class="es-section-header">
                <h2>Seating Turn Times &amp; Conflict Window</h2>
            </div>
            <div class="es-form-group">
                <label class="es-label" for="es_seating_duration">Standard Table Duration (Minutes)</label>
                <input type="number" id="es_seating_duration" name="es_seating_duration"
                       class="es-input" style="max-width: 180px;" min="30" max="240" value="<?php echo esc_attr($seating_duration); ?>">
                <small class="es-help">The conflict prevention engine blocks overlapping bookings for this duration around each confirmed seating.</small>
            </div>
        </div>

        <!-- Automated 24-Hour Reminder Engine -->
        <div class="es-section-card">
            <div class="es-section-header">
                <h2>Automated 24-Hour Guest Reminders</h2>
            </div>
            <div class="es-info-row">
                <span class="es-label" style="margin: 0;">Cron Status:</span>
                <?php if ($cron_next): ?>
                    <span class="es-status es-status-confirmed">✓ Scheduled &bull; Next Dispatch: <?php echo date('M j, Y g:i A', $cron_next); ?></span>
                <?php else: ?>
                    <span class="es-status es-status-pending">⏳ Initializing schedule</span>
                <?php endif; ?>
            </div>
            <div class="es-form-group" style="margin-top:16px;">
                <button type="button" id="btn-test-cron" class="es-btn es-btn-secondary">Test Reminder Dispatch Cycle</button>
                <small class="es-help">Dispatches reminders to guests with confirmed reservations tomorrow who haven't yet received their notification.</small>
            </div>
            <div id="cron-test-result" style="margin-top:12px;display:none;" class="es-alert"></div>
        </div>

        <div class="es-section-card" style="background:transparent;border:none;padding:0;box-shadow:none;">
            <button type="submit" name="es_save_settings" class="es-btn es-btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<script>
jQuery(function($){
    $('#btn-test-cron').on('click', function(){
        var $btn = $(this);
        $btn.text('Dispatching…').prop('disabled', true);
        $.post(esAdmin.ajaxurl, {
            action: 'es_admin_test_reminder_cron',
            nonce: esAdmin.nonce
        }, function(resp){
            var $res = $('#cron-test-result');
            $res.show().removeClass('es-alert-error').addClass('es-alert-success');
            $res.text(resp.success ? resp.data.message : (resp.data.message || 'Error occurred'));
            $btn.text('Test Reminder Dispatch Cycle').prop('disabled', false);
        });
    });
});
</script>
