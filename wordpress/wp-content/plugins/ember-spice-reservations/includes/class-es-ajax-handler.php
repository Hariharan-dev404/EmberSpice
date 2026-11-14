<?php
/**
 * AJAX Request Handlers (Public Form & Admin Operations)
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Ajax_Handler {

    public static function init() {
        // Public Reservation Form (Priority 1 to take precedence over any mu-plugin fallback)
        add_action('wp_ajax_ember_spice_reservation', [__CLASS__, 'handle_public_reservation'], 1);
        add_action('wp_ajax_nopriv_ember_spice_reservation', [__CLASS__, 'handle_public_reservation'], 1);

        // Admin AJAX Actions
        add_action('wp_ajax_es_admin_approve_reservation', [__CLASS__, 'admin_approve_reservation']);
        add_action('wp_ajax_es_admin_reject_reservation', [__CLASS__, 'admin_reject_reservation']);
        add_action('wp_ajax_es_admin_cancel_reservation', [__CLASS__, 'admin_cancel_reservation']);
        add_action('wp_ajax_es_admin_get_details', [__CLASS__, 'admin_get_details']);
        add_action('wp_ajax_es_admin_get_available_tables', [__CLASS__, 'admin_get_available_tables']);
        add_action('wp_ajax_es_admin_save_table', [__CLASS__, 'admin_save_table']);
        add_action('wp_ajax_es_admin_delete_table', [__CLASS__, 'admin_delete_table']);
        add_action('wp_ajax_es_admin_calendar_events', [__CLASS__, 'admin_calendar_events']);
        add_action('wp_ajax_es_admin_test_reminder_cron', [__CLASS__, 'admin_test_reminder_cron']);
    }

    /**
     * Handle public reservation form submission.
     */
    public static function handle_public_reservation() {
        $res = ES_Reservation_Manager::create_reservation($_POST);

        if (is_wp_error($res)) {
            wp_send_json_error([
                'message' => $res->get_error_message()
            ]);
        }

        wp_send_json_success([
            'message' => "Thank you, {$res->guest_name}! Your table request has been received (Ref: {$res->reservation_ref}). Our dining hosts will review availability and send an official confirmation to your email shortly.",
            'ref'     => $res->reservation_ref,
            'status'  => $res->status
        ]);
    }

    /**
     * Admin: Approve Reservation.
     */
    public static function admin_approve_reservation() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id       = intval($_POST['id'] ?? 0);
        $table_id = intval($_POST['table_id'] ?? 0);
        $notes    = sanitize_text_field($_POST['notes'] ?? '');

        $result = ES_Reservation_Manager::approve_reservation($id, $table_id, $notes);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Reservation approved and confirmation email sent to guest!']);
    }

    /**
     * Admin: Reject Reservation.
     */
    public static function admin_reject_reservation() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id     = intval($_POST['id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        $result = ES_Reservation_Manager::reject_reservation($id, $reason);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Reservation rejected and notification email sent to guest.']);
    }

    /**
     * Admin: Cancel Reservation.
     */
    public static function admin_cancel_reservation() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id     = intval($_POST['id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        $result = ES_Reservation_Manager::cancel_reservation($id, $reason, 'admin');
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Reservation cancelled and cancellation email sent.']);
    }

    /**
     * Admin: Fetch details for view/action modal.
     */
    public static function admin_get_details() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = intval($_GET['id'] ?? 0);
        $res = ES_Reservation_Manager::get_reservation($id);
        if (!$res) {
            wp_send_json_error(['message' => 'Reservation not found.']);
        }

        $activity = ES_Reservation_Manager::get_activity_log($id);
        $notifs   = ES_Reservation_Manager::get_notifications($id);
        $tables   = ES_Table_Manager::get_available_tables_for_reservation($res->reservation_date, $res->reservation_time, $res->guest_count, $res->id);

        wp_send_json_success([
            'reservation'      => $res,
            'activity_log'     => $activity,
            'notifications'    => $notifs,
            'available_tables' => $tables,
        ]);
    }

    /**
     * Admin: Available tables for a specific date/time.
     */
    public static function admin_get_available_tables() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $date   = sanitize_text_field($_GET['date'] ?? '');
        $time   = sanitize_text_field($_GET['time'] ?? '');
        $guests = intval($_GET['guests'] ?? 2);
        $res_id = intval($_GET['res_id'] ?? 0);

        $tables = ES_Table_Manager::get_available_tables_for_reservation($date, $time, $guests, $res_id);

        wp_send_json_success(['tables' => $tables]);
    }

    /**
     * Admin: Save Table.
     */
    public static function admin_save_table() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = intval($_POST['id'] ?? 0);
        $data = [
            'table_number' => sanitize_text_field($_POST['table_number'] ?? ''),
            'name'         => sanitize_text_field($_POST['name'] ?? ''),
            'capacity'     => intval($_POST['capacity'] ?? 4),
            'location'     => sanitize_text_field($_POST['location'] ?? 'Main Dining Room'),
            'is_active'    => isset($_POST['is_active']) ? intval($_POST['is_active']) : 1,
        ];

        if ($id > 0) {
            $res = ES_Table_Manager::update_table($id, $data);
        } else {
            $res = ES_Table_Manager::create_table($data);
        }

        if (is_wp_error($res)) {
            wp_send_json_error(['message' => $res->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Table saved successfully!']);
    }

    /**
     * Admin: Delete Table.
     */
    public static function admin_delete_table() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $id = intval($_POST['id'] ?? 0);
        $res = ES_Table_Manager::delete_table($id);

        if (is_wp_error($res)) {
            wp_send_json_error(['message' => $res->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Table deleted successfully.']);
    }

    /**
     * Admin: Calendar Events feed.
     */
    public static function admin_calendar_events() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $start = sanitize_text_field($_GET['start'] ?? date('Y-m-01'));
        $end   = sanitize_text_field($_GET['end'] ?? date('Y-m-t'));

        $query = ES_Reservation_Manager::get_reservations([
            'date_from' => $start,
            'date_to'   => $end,
            'limit'     => 500,
        ]);

        $events = [];
        foreach ($query['items'] as $item) {
            $color = '#B99A65'; // pending wheat gold
            if ($item->status === 'confirmed') $color = '#788568'; // sage green
            elseif ($item->status === 'rejected') $color = '#B86F48'; // earthy terracotta
            elseif ($item->status === 'cancelled') $color = '#806B52'; // natural brown

            $events[] = [
                'id'          => $item->id,
                'ref'         => $item->reservation_ref,
                'title'       => $item->reservation_time . ' - ' . $item->guest_name . ' (' . $item->guest_count . 'p)',
                'guest_name'  => $item->guest_name,
                'guest_email' => $item->guest_email,
                'guest_phone' => $item->guest_phone,
                'date'        => $item->reservation_date,
                'time'        => $item->reservation_time,
                'guests'      => $item->guest_count,
                'status'      => $item->status,
                'table'       => !empty($item->table_number) ? "Table {$item->table_number}" : 'Unassigned',
                'color'       => $color,
            ];
        }

        wp_send_json_success(['events' => $events]);
    }

    /**
     * Admin: Trigger reminder cron test manually.
     */
    public static function admin_test_reminder_cron() {
        check_ajax_referer('es_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $dispatched = ES_Cron::dispatch_reminders();
        wp_send_json_success([
            'message' => "Cron cycle executed. Dispatched {$dispatched} reminder email(s) for tomorrow's confirmed bookings."
        ]);
    }
}

