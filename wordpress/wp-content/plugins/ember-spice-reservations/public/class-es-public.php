<?php
/**
 * Public Endpoint Handlers (Customer Cancellation)
 * Natural Organic Restaurant Identity
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Public {

    public static function init() {
        add_action('template_redirect', [__CLASS__, 'handle_cancellation_page']);
    }

    /**
     * Intercept and render self-service customer cancellation page.
     */
    public static function handle_cancellation_page() {
        if (!isset($_GET['es_action']) || $_GET['es_action'] !== 'cancel') {
            return;
        }

        $token = sanitize_text_field($_GET['token'] ?? '');
        $ref   = sanitize_text_field($_GET['ref'] ?? '');

        if (empty($token)) {
            wp_die('Invalid or missing reservation cancellation token.', 'Error', ['response' => 400]);
        }

        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';
        $res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_res WHERE cancellation_token = %s", $token));

        $status_message = '';
        $is_error = false;
        $cancelled_success = false;

        if (!$res) {
            $is_error = true;
            $status_message = 'Reservation not found or this cancellation link has expired.';
        } elseif ($res->status === 'cancelled') {
            $is_error = true;
            $status_message = "This reservation ({$res->reservation_ref}) has already been cancelled.";
        } elseif ($res->status === 'rejected') {
            $is_error = true;
            $status_message = "This reservation request was declined by the dining room host.";
        }

        // Process POST confirmation
        if (!$is_error && isset($_POST['es_confirm_cancel']) && check_admin_referer('es_customer_cancel_' . $token)) {
            $reason = sanitize_textarea_field($_POST['cancel_reason'] ?? '');
            $cancelled = ES_Reservation_Manager::cancel_by_token($token, $reason);

            if (is_wp_error($cancelled)) {
                $is_error = true;
                $status_message = $cancelled->get_error_message();
            } else {
                $cancelled_success = true;
                $status_message = "Your reservation (Ref: {$res->reservation_ref}) has been successfully cancelled. A confirmation notice has been sent to {$res->guest_email}.";
                $res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_res WHERE cancellation_token = %s", $token));
            }
        }

        self::render_cancellation_view($res, $token, $is_error, $status_message, $cancelled_success);
        exit;
    }

    /**
     * Render the branded natural organic cancellation page.
     */
    private static function render_cancellation_view($res, $token, $is_error, $status_message, $cancelled_success) {
        $site_name = get_bloginfo('name') ?: 'Ember Spice';
        $home_url  = home_url('/');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cancel Reservation — <?php echo esc_html($site_name); ?></title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    background-color: #F4EEDC;
                    background-image: radial-gradient(circle at 10% 20%, rgba(120, 133, 104, 0.08) 0%, transparent 40%),
                                      radial-gradient(circle at 90% 80%, rgba(184, 111, 72, 0.06) 0%, transparent 50%);
                    color: #2A2C24;
                    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 32px 20px;
                }
                .card {
                    background: #FFFCF5;
                    border: 1px solid rgba(120, 133, 104, 0.28);
                    border-radius: 14px;
                    max-width: 540px;
                    width: 100%;
                    padding: 44px 40px;
                    box-shadow: 0 16px 45px rgba(52, 75, 53, 0.08), 0 2px 8px rgba(128, 107, 82, 0.05);
                    text-align: center;
                    position: relative;
                }
                .emblem {
                    width: 56px;
                    height: 56px;
                    border-radius: 50%;
                    background: #344B35;
                    color: #F4EEDC;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 26px;
                    margin: 0 auto 16px;
                    border: 2px solid #B99A65;
                    box-shadow: 0 4px 14px rgba(52, 75, 53, 0.2);
                }
                .brand-eyebrow {
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 2px;
                    color: #788568;
                    text-transform: uppercase;
                    margin-bottom: 6px;
                }
                h1 {
                    font-family: 'Playfair Display', Georgia, serif;
                    font-size: 26px;
                    color: #344B35;
                    margin-bottom: 12px;
                    font-weight: 700;
                    line-height: 1.25;
                }
                .lead {
                    font-size: 14px;
                    color: #777568;
                    line-height: 1.6;
                    margin-bottom: 22px;
                }
                .info-box {
                    background: #FAF6ED;
                    border: 1px solid rgba(120, 133, 104, 0.2);
                    border-radius: 10px;
                    padding: 18px 22px;
                    margin: 22px 0;
                    text-align: left;
                    font-size: 14px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 9px 0;
                    border-bottom: 1px solid rgba(120, 133, 104, 0.12);
                }
                .info-row:last-child { border-bottom: none; }
                .label {
                    color: #788568;
                    font-weight: 700;
                    font-size: 11.5px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .val {
                    color: #344B35;
                    font-weight: 600;
                }
                .ref-badge {
                    background: rgba(120, 133, 104, 0.15);
                    color: #344B35;
                    padding: 3px 8px;
                    border-radius: 4px;
                    font-family: Consolas, monospace;
                    font-weight: 700;
                }
                .alert-error {
                    background: #FDF1EA;
                    border: 1px solid #B86F48;
                    color: #8C3912;
                    padding: 16px 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-size: 14px;
                    line-height: 1.6;
                    text-align: left;
                }
                .alert-success {
                    background: #EBF0E6;
                    border: 1px solid #788568;
                    color: #243525;
                    padding: 16px 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-size: 14px;
                    line-height: 1.6;
                    text-align: left;
                }
                textarea {
                    width: 100%;
                    padding: 12px 14px;
                    background: #FFFFFF;
                    border: 1px solid rgba(120, 133, 104, 0.35);
                    border-radius: 6px;
                    color: #2A2C24;
                    font-family: inherit;
                    font-size: 13.5px;
                    resize: vertical;
                    min-height: 80px;
                    margin-top: 8px;
                    transition: border-color 0.2s, box-shadow 0.2s;
                }
                textarea:focus {
                    outline: none;
                    border-color: #344B35;
                    box-shadow: 0 0 0 3px rgba(52, 75, 53, 0.1);
                }
                .btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px 26px;
                    border-radius: 6px;
                    font-family: inherit;
                    font-weight: 600;
                    font-size: 13.5px;
                    cursor: pointer;
                    text-decoration: none;
                    transition: all 0.2s ease;
                    border: 1px solid transparent;
                }
                .btn-danger {
                    background: #B86F48;
                    color: #FFFFFF;
                    box-shadow: 0 2px 8px rgba(184, 111, 72, 0.25);
                }
                .btn-danger:hover {
                    background: #9E5B36;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(184, 111, 72, 0.35);
                }
                .btn-secondary {
                    background: #FFFFFF;
                    border: 1px solid rgba(120, 133, 104, 0.35);
                    color: #344B35;
                    margin-left: 10px;
                }
                .btn-secondary:hover {
                    background: #EBF0E6;
                    border-color: #788568;
                }
                .btn-wrap {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    margin-top: 24px;
                    flex-wrap: wrap;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="emblem">🌿</div>
                <div class="brand-eyebrow">Ember Spice &bull; Reservation Desk</div>
                <h1>Reservation Cancellation</h1>

                <?php if ($cancelled_success): ?>
                    <div class="alert-success">
                        <strong>✓ Reservation Released</strong><br>
                        <?php echo esc_html($status_message); ?>
                    </div>
                    <div class="btn-wrap">
                        <a href="<?php echo esc_url($home_url); ?>" class="btn btn-secondary">Return to Ember Spice Home</a>
                    </div>
                <?php elseif ($is_error): ?>
                    <div class="alert-error">
                        <strong>Notice:</strong><br>
                        <?php echo esc_html($status_message); ?>
                    </div>
                    <div class="btn-wrap">
                        <a href="<?php echo esc_url($home_url); ?>" class="btn btn-secondary">Return to Homepage</a>
                    </div>
                <?php else: ?>
                    <p class="lead">
                        Are you sure you wish to release your table at Ember Spice? We will update our host desk and notify our team immediately.
                    </p>

                    <div class="info-box">
                        <div class="info-row">
                            <span class="label">Reference Code</span>
                            <span class="val"><code class="ref-badge"><?php echo esc_html($res->reservation_ref); ?></code></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Guest Name</span>
                            <span class="val"><?php echo esc_html($res->guest_name); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Dining Date</span>
                            <span class="val"><?php echo date('l, F j, Y', strtotime($res->reservation_date)); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Seating Time</span>
                            <span class="val"><?php echo esc_html($res->reservation_time); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Party Size</span>
                            <span class="val"><?php echo intval($res->guest_count); ?> Guest(s)</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Current Status</span>
                            <span class="val" style="text-transform: capitalize; color:#788568;"><?php echo esc_html($res->status); ?></span>
                        </div>
                    </div>

                    <form method="POST">
                        <?php wp_nonce_field('es_customer_cancel_' . $token); ?>
                        <div style="text-align: left; margin: 18px 0;">
                            <label style="font-size: 12px; color: #788568; font-weight: 700; text-transform:uppercase; letter-spacing:0.5px;">Optional Reason for Cancelling:</label>
                            <textarea name="cancel_reason" placeholder="Please let us know if your plans changed or if you need an alternate dining time (optional)…"></textarea>
                        </div>
                        <div class="btn-wrap">
                            <button type="submit" name="es_confirm_cancel" class="btn btn-danger">Yes, Cancel Reservation</button>
                            <a href="<?php echo esc_url($home_url); ?>" class="btn btn-secondary">Keep Reservation</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }
}
