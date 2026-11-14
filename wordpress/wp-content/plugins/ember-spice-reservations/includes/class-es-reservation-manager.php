<?php
/**
 * Reservation Manager - Core Business Logic and State Machine
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Reservation_Manager {

    /**
     * Generate a unique 8-character reference code (e.g. ES-7K9A2M).
     */
    public static function generate_unique_ref() {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        do {
            $random = strtoupper(wp_generate_password(6, false, false));
            $ref = 'ES-' . $random;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_res WHERE reservation_ref = %s", $ref));
        } while ($exists);

        return $ref;
    }

    /**
     * Generate a cryptographically secure token for cancellation.
     */
    public static function generate_cancellation_token() {
        return wp_generate_password(32, false, false);
    }

    /**
     * Create a new reservation submission.
     */
    public static function create_reservation($data) {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $name     = sanitize_text_field($data['name'] ?? '');
        $email    = sanitize_email($data['email'] ?? '');
        $phone    = sanitize_text_field($data['phone'] ?? '');
        $date     = sanitize_text_field($data['date'] ?? '');
        $time     = sanitize_text_field($data['time'] ?? '');
        $guests   = intval($data['guests'] ?? 2);
        $requests = sanitize_textarea_field($data['requests'] ?? '');

        // Validation
        if (empty($name) || empty($email) || !is_email($email) || empty($phone) || empty($date) || empty($time)) {
            return new WP_Error('missing_fields', 'Please fill in all required fields with valid information.');
        }

        if ($guests < 1 || $guests > 30) {
            return new WP_Error('invalid_guests', 'Party size must be between 1 and 30 guests.');
        }

        // Validate date is not in the past
        $today = current_time('Y-m-d');
        if ($date < $today) {
            return new WP_Error('past_date', 'Reservation date cannot be in the past.');
        }

        // Prevent duplicate / spam submissions within 120 seconds
        $recent_duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_res WHERE guest_email = %s AND reservation_date = %s AND reservation_time = %s AND created_at >= %s",
            $email, $date, $time, date('Y-m-d H:i:s', current_time('timestamp') - 120)
        ));

        if ($recent_duplicate) {
            return new WP_Error('duplicate_submission', 'A reservation request with these details was recently received. Please check your inbox or wait a moment.');
        }

        $ref   = self::generate_unique_ref();
        $token = self::generate_cancellation_token();
        $now   = current_time('mysql');

        $inserted = $wpdb->insert($table_res, [
            'reservation_ref'    => $ref,
            'guest_name'         => $name,
            'guest_email'        => $email,
            'guest_phone'        => $phone,
            'reservation_date'   => $date,
            'reservation_time'   => $time,
            'guest_count'        => $guests,
            'table_id'           => null,
            'special_requests'   => $requests,
            'status'             => 'pending',
            'cancellation_token' => $token,
            'reminder_sent'      => 0,
            'created_at'         => $now,
            'updated_at'         => $now,
        ], [
            '%s', '%s', '%s', '%s', '%s', '%s', '%d',
            null, '%s', '%s', '%s', '%d', '%s', '%s'
        ]);

        if (!$inserted) {
            return new WP_Error('db_insert_error', 'Database could not save reservation.');
        }

        $res_id = $wpdb->insert_id;
        $res = self::get_reservation($res_id);

        // Activity log
        self::log_activity($res_id, 'created', "Guest submitted online reservation request (Ref: $ref).", 0, 'Guest');

        // Dispatch notifications via FluentSMTP
        ES_Mailer::send_customer_request_received($res);
        ES_Mailer::send_admin_new_request($res);

        return $res;
    }

    /**
     * Approve reservation and assign table (Pending -> Confirmed).
     */
    public static function approve_reservation($id, $table_id, $admin_notes = '') {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $res = self::get_reservation($id);
        if (!$res) {
            return new WP_Error('not_found', 'Reservation not found.');
        }

        if ($res->status === 'confirmed') {
            return new WP_Error('already_confirmed', 'This reservation is already confirmed.');
        }

        if (empty($table_id)) {
            return new WP_Error('missing_table', 'Please assign a valid restaurant table to approve this reservation.');
        }

        // Validate table availability and capacity
        $avail_check = ES_Table_Manager::check_table_availability($table_id, $res->reservation_date, $res->reservation_time, $res->guest_count, $res->id);
        if (is_wp_error($avail_check)) {
            return $avail_check;
        }

        $table = ES_Table_Manager::get_table($table_id);
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $user_name = $user->exists() ? $user->display_name : 'Administrator';

        $updated = $wpdb->update($table_res, [
            'status'     => 'confirmed',
            'table_id'   => $table_id,
            'updated_at' => current_time('mysql'),
        ], ['id' => $id], ['%s', '%d', '%s'], ['%d']);

        if ($updated === false) {
            return new WP_Error('db_error', 'Could not update reservation status.');
        }

        // Refresh object
        $res = self::get_reservation($id);

        $log_details = "Approved by {$user_name}. Assigned to Table {$table->table_number} ({$table->name}).";
        if (!empty($admin_notes)) {
            $log_details .= " Note: " . $admin_notes;
        }
        self::log_activity($id, 'approved', $log_details, $user_id, $user_name);

        // Dispatch confirmation email to customer
        ES_Mailer::send_customer_confirmed($res, $table);

        return true;
    }

    /**
     * Reject reservation with reason (Pending -> Rejected).
     */
    public static function reject_reservation($id, $reason = '') {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $res = self::get_reservation($id);
        if (!$res) {
            return new WP_Error('not_found', 'Reservation not found.');
        }

        if ($res->status === 'rejected') {
            return new WP_Error('already_rejected', 'This reservation is already rejected.');
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $user_name = $user->exists() ? $user->display_name : 'Administrator';

        $updated = $wpdb->update($table_res, [
            'status'           => 'rejected',
            'table_id'         => null,
            'rejection_reason' => $reason,
            'updated_at'       => current_time('mysql'),
        ], ['id' => $id], ['%s', null, '%s', '%s'], ['%d']);

        if ($updated === false) {
            return new WP_Error('db_error', 'Could not update reservation status.');
        }

        $res = self::get_reservation($id);

        $log_details = "Rejected by {$user_name}. Reason: " . ($reason ?: 'None provided');
        self::log_activity($id, 'rejected', $log_details, $user_id, $user_name);

        // Dispatch rejection email
        ES_Mailer::send_customer_rejected($res, $reason);

        return true;
    }

    /**
     * Cancel reservation (from Pending or Confirmed -> Cancelled).
     */
    public static function cancel_reservation($id, $reason = '', $by = 'admin') {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $res = self::get_reservation($id);
        if (!$res) {
            return new WP_Error('not_found', 'Reservation not found.');
        }

        if ($res->status === 'cancelled') {
            return new WP_Error('already_cancelled', 'This reservation is already cancelled.');
        }

        $user_id = ($by === 'customer') ? 0 : get_current_user_id();
        $user_name = ($by === 'customer') ? $res->guest_name . ' (Customer)' : (wp_get_current_user()->display_name ?: 'Administrator');

        $updated = $wpdb->update($table_res, [
            'status'              => 'cancelled',
            'table_id'            => null,
            'cancellation_reason' => $reason,
            'updated_at'          => current_time('mysql'),
        ], ['id' => $id], ['%s', null, '%s', '%s'], ['%d']);

        if ($updated === false) {
            return new WP_Error('db_error', 'Could not update reservation status.');
        }

        $res = self::get_reservation($id);

        $log_details = "Cancelled by {$user_name}. Reason: " . ($reason ?: 'No reason stated');
        self::log_activity($id, 'cancelled', $log_details, $user_id, $user_name);

        if ($by === 'customer') {
            // Customer cancelled: notify admin & acknowledge customer
            ES_Mailer::send_admin_customer_cancelled($res);
            ES_Mailer::send_customer_cancelled($res, $reason);
        } else {
            // Admin cancelled: inform customer
            ES_Mailer::send_customer_cancelled($res, $reason);
        }

        return true;
    }

    /**
     * Customer self-service cancellation via secure token.
     */
    public static function cancel_by_token($token, $reason = '') {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        if (empty($token)) {
            return new WP_Error('invalid_token', 'Invalid or missing cancellation token.');
        }

        $res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_res WHERE cancellation_token = %s", $token));
        if (!$res) {
            return new WP_Error('not_found', 'Reservation not found or token has expired.');
        }

        if ($res->status === 'cancelled') {
            return new WP_Error('already_cancelled', 'This reservation has already been cancelled.');
        }

        if ($res->status === 'rejected') {
            return new WP_Error('already_rejected', 'This reservation was already rejected.');
        }

        return self::cancel_reservation($res->id, $reason, 'customer');
    }

    /**
     * Update reservation details (for admin edit).
     */
    public static function update_reservation($id, $data) {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $res = self::get_reservation($id);
        if (!$res) {
            return new WP_Error('not_found', 'Reservation not found.');
        }

        $name     = sanitize_text_field($data['name'] ?? $res->guest_name);
        $email    = sanitize_email($data['email'] ?? $res->guest_email);
        $phone    = sanitize_text_field($data['phone'] ?? $res->guest_phone);
        $date     = sanitize_text_field($data['date'] ?? $res->reservation_date);
        $time     = sanitize_text_field($data['time'] ?? $res->reservation_time);
        $guests   = intval($data['guests'] ?? $res->guest_count);
        $requests = sanitize_textarea_field($data['requests'] ?? $res->special_requests);
        $table_id = isset($data['table_id']) && !empty($data['table_id']) ? intval($data['table_id']) : null;

        if ($table_id && $res->status === 'confirmed') {
            $check = ES_Table_Manager::check_table_availability($table_id, $date, $time, $guests, $id);
            if (is_wp_error($check)) {
                return $check;
            }
        }

        $wpdb->update($table_res, [
            'guest_name'       => $name,
            'guest_email'      => $email,
            'guest_phone'      => $phone,
            'reservation_date' => $date,
            'reservation_time' => $time,
            'guest_count'      => $guests,
            'special_requests' => $requests,
            'table_id'         => $table_id,
            'updated_at'       => current_time('mysql'),
        ], ['id' => $id], ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s'], ['%d']);

        $user_name = wp_get_current_user()->display_name ?: 'Administrator';
        self::log_activity($id, 'updated', "Reservation updated by {$user_name}.", get_current_user_id(), $user_name);

        return true;
    }

    /**
     * Fetch single reservation by ID.
     */
    public static function get_reservation($id) {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';
        $table_tbl = $wpdb->prefix . 'es_tables';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.table_number, t.name as table_name, t.location as table_location, t.capacity as table_capacity 
             FROM $table_res r 
             LEFT JOIN $table_tbl t ON r.table_id = t.id 
             WHERE r.id = %d",
            $id
        ));
    }

    /**
     * Fetch single reservation by Ref.
     */
    public static function get_reservation_by_ref($ref) {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';
        $table_tbl = $wpdb->prefix . 'es_tables';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.table_number, t.name as table_name, t.location as table_location 
             FROM $table_res r 
             LEFT JOIN $table_tbl t ON r.table_id = t.id 
             WHERE r.reservation_ref = %s",
            $ref
        ));
    }

    /**
     * Query reservations with filters, search, and pagination.
     */
    public static function get_reservations($args = []) {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';
        $table_tbl = $wpdb->prefix . 'es_tables';

        $defaults = [
            'status'     => '',
            'search'     => '',
            'date_from'  => '',
            'date_to'    => '',
            'date'       => '',
            'table_id'   => '',
            'orderby'    => 'reservation_date',
            'order'      => 'DESC',
            'limit'      => 20,
            'offset'     => 0,
        ];
        $r = wp_parse_args($args, $defaults);

        $where = ["1=1"];

        if (!empty($r['status']) && in_array($r['status'], ['pending', 'confirmed', 'rejected', 'cancelled'])) {
            $where[] = $wpdb->prepare("r.status = %s", $r['status']);
        }

        if (!empty($r['search'])) {
            $like = '%' . $wpdb->esc_like($r['search']) . '%';
            $where[] = $wpdb->prepare("(r.reservation_ref LIKE %s OR r.guest_name LIKE %s OR r.guest_email LIKE %s OR r.guest_phone LIKE %s)", $like, $like, $like, $like);
        }

        if (!empty($r['date'])) {
            $where[] = $wpdb->prepare("r.reservation_date = %s", $r['date']);
        }

        if (!empty($r['date_from'])) {
            $where[] = $wpdb->prepare("r.reservation_date >= %s", $r['date_from']);
        }

        if (!empty($r['date_to'])) {
            $where[] = $wpdb->prepare("r.reservation_date <= %s", $r['date_to']);
        }

        if (!empty($r['table_id'])) {
            $where[] = $wpdb->prepare("r.table_id = %d", intval($r['table_id']));
        }

        $where_sql = implode(' AND ', $where);

        $allowed_order = ['reservation_date', 'created_at', 'guest_name', 'guest_count', 'id'];
        $orderby = in_array($r['orderby'], $allowed_order) ? 'r.' . $r['orderby'] : 'r.reservation_date';
        $order   = strtoupper($r['order']) === 'ASC' ? 'ASC' : 'DESC';

        $limit_sql = "";
        if ($r['limit'] > 0) {
            $limit_sql = $wpdb->prepare("LIMIT %d OFFSET %d", intval($r['limit']), intval($r['offset']));
        }

        $sql = "SELECT r.*, t.table_number, t.name as table_name, t.location as table_location 
                FROM $table_res r 
                LEFT JOIN $table_tbl t ON r.table_id = t.id 
                WHERE $where_sql 
                ORDER BY $orderby $order, r.reservation_time ASC 
                $limit_sql";

        $count_sql = "SELECT COUNT(*) FROM $table_res r WHERE $where_sql";

        $items = $wpdb->get_results($sql);
        $total = (int) $wpdb->get_var($count_sql);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Compute overview statistics for dashboard cards.
     */
    public static function get_metrics() {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        $today = current_time('Y-m-d');

        $counts = $wpdb->get_results("SELECT status, COUNT(*) as cnt FROM $table_res GROUP BY status", OBJECT_K);

        $pending   = isset($counts['pending'])   ? (int) $counts['pending']->cnt   : 0;
        $confirmed = isset($counts['confirmed']) ? (int) $counts['confirmed']->cnt : 0;
        $rejected  = isset($counts['rejected'])  ? (int) $counts['rejected']->cnt  : 0;
        $cancelled = isset($counts['cancelled']) ? (int) $counts['cancelled']->cnt : 0;
        $total     = $pending + $confirmed + $rejected + $cancelled;

        $today_res = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_res WHERE reservation_date = %s AND status IN ('pending', 'confirmed')",
            $today
        ));

        $upcoming = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_res WHERE reservation_date >= %s AND status = 'confirmed'",
            $today
        ));

        return [
            'total'     => $total,
            'pending'   => $pending,
            'confirmed' => $confirmed,
            'rejected'  => $rejected,
            'cancelled' => $cancelled,
            'today'     => $today_res,
            'upcoming'  => $upcoming,
        ];
    }

    /**
     * Log activity entry.
     */
    public static function log_activity($reservation_id, $action, $details = '', $user_id = 0, $user_name = 'System') {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'es_activity_log';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_activity'") === $table_activity) {
            $wpdb->insert($table_activity, [
                'reservation_id' => $reservation_id,
                'user_id'        => $user_id,
                'user_name'      => $user_name,
                'action'         => $action,
                'details'        => $details,
                'created_at'     => current_time('mysql'),
            ], ['%d', '%d', '%s', '%s', '%s', '%s']);
        }
    }

    /**
     * Get activity history for a reservation.
     */
    public static function get_activity_log($reservation_id) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'es_activity_log';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_activity WHERE reservation_id = %d ORDER BY created_at DESC",
            $reservation_id
        ));
    }

    /**
     * Get sent notifications for a reservation.
     */
    public static function get_notifications($reservation_id) {
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'es_notifications';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_notifs WHERE reservation_id = %d ORDER BY sent_at DESC",
            $reservation_id
        ));
    }
}

