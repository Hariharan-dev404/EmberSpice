<?php
/**
 * Restaurant Table Management
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Table_Manager {

    /**
     * Get all tables.
     */
    public static function get_tables($active_only = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'es_tables';

        $where = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY table_number ASC");
    }

    /**
     * Get a single table by ID.
     */
    public static function get_table($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'es_tables';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Create a new table.
     */
    public static function create_table($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'es_tables';

        $number   = sanitize_text_field($data['table_number'] ?? '');
        $name     = sanitize_text_field($data['name'] ?? '');
        $capacity = intval($data['capacity'] ?? 4);
        $location = sanitize_text_field($data['location'] ?? 'Main Dining Room');

        if (empty($number) || empty($name) || $capacity <= 0) {
            return new WP_Error('invalid_data', 'Table number, name, and a positive seating capacity are required.');
        }

        // Check uniqueness
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE table_number = %s", $number));
        if ($existing) {
            return new WP_Error('duplicate_number', 'A table with this number/code already exists.');
        }

        $inserted = $wpdb->insert($table_name, [
            'table_number' => $number,
            'name'         => $name,
            'capacity'     => $capacity,
            'location'     => $location,
            'is_active'    => 1,
            'created_at'   => current_time('mysql'),
        ], ['%s', '%s', '%d', '%s', '%d', '%s']);

        if (!$inserted) {
            return new WP_Error('db_error', 'Could not create table in database.');
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing table.
     */
    public static function update_table($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'es_tables';

        $number    = sanitize_text_field($data['table_number'] ?? '');
        $name      = sanitize_text_field($data['name'] ?? '');
        $capacity  = intval($data['capacity'] ?? 4);
        $location  = sanitize_text_field($data['location'] ?? 'Main Dining Room');
        $is_active = isset($data['is_active']) ? (intval($data['is_active']) ? 1 : 0) : 1;

        if (empty($number) || empty($name) || $capacity <= 0) {
            return new WP_Error('invalid_data', 'Table number, name, and a positive seating capacity are required.');
        }

        // Check uniqueness excluding self
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE table_number = %s AND id != %d",
            $number, $id
        ));
        if ($existing) {
            return new WP_Error('duplicate_number', 'Another table already uses this table number.');
        }

        $updated = $wpdb->update($table_name, [
            'table_number' => $number,
            'name'         => $name,
            'capacity'     => $capacity,
            'location'     => $location,
            'is_active'    => $is_active,
        ], ['id' => $id], ['%s', '%s', '%d', '%s', '%d'], ['%d']);

        return $updated !== false;
    }

    /**
     * Safely delete a table.
     */
    public static function delete_table($id) {
        global $wpdb;
        $table_tables = $wpdb->prefix . 'es_tables';
        $table_res    = $wpdb->prefix . 'es_reservations';

        // Check if assigned to any active (confirmed or pending) reservations
        $active_assignments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_res WHERE table_id = %d AND status IN ('confirmed', 'pending') AND reservation_date >= %s",
            $id, current_time('Y-m-d')
        ));

        if ($active_assignments > 0) {
            return new WP_Error('table_in_use', "Cannot delete table: It is assigned to {$active_assignments} upcoming confirmed or pending reservation(s). Reassign them first or mark the table inactive.");
        }

        return $wpdb->delete($table_tables, ['id' => $id], ['%d']);
    }

    /**
     * Check if a table is available at a given date and time for a specific duration.
     * Overlap window: ±90 minutes (5400 seconds).
     *
     * @param int    $table_id
     * @param string $date Y-m-d
     * @param string $time e.g. "19:00" or "7:00 PM"
     * @param int    $guest_count
     * @param int    $exclude_res_id Reservation to exclude from check (for editing/reassignment)
     * @return true|WP_Error
     */
    public static function check_table_availability($table_id, $date, $time, $guest_count, $exclude_res_id = 0) {
        global $wpdb;
        $table_tables = $wpdb->prefix . 'es_tables';
        $table_res    = $wpdb->prefix . 'es_reservations';

        $table = self::get_table($table_id);
        if (!$table || !$table->is_active) {
            return new WP_Error('table_inactive', 'This table is inactive or does not exist.');
        }

        if ($table->capacity < $guest_count) {
            return new WP_Error('capacity_insufficient', "Table {$table->table_number} seats {$table->capacity} guests, but {$guest_count} guests were requested.");
        }

        // Standardize target time to minutes from midnight
        $target_minutes = self::parse_time_to_minutes($time);
        if ($target_minutes === false) {
            return new WP_Error('invalid_time', 'Invalid reservation time format.');
        }

        // Query confirmed reservations for this table on this date
        $exclude_sql = $exclude_res_id > 0 ? $wpdb->prepare("AND id != %d", $exclude_res_id) : "";
        $existing = $wpdb->get_results($wpdb->prepare(
            "SELECT id, reservation_ref, reservation_time FROM $table_res WHERE table_id = %d AND reservation_date = %s AND status = 'confirmed' $exclude_sql",
            $table_id, $date
        ));

        $seating_duration = 90; // 90 minutes standard seating

        foreach ($existing as $res) {
            $existing_minutes = self::parse_time_to_minutes($res->reservation_time);
            if ($existing_minutes !== false) {
                // If difference is less than seating duration, there is an overlap
                if (abs($target_minutes - $existing_minutes) < $seating_duration) {
                    return new WP_Error('table_conflict', "Conflict: Table {$table->table_number} is already reserved for {$res->reservation_ref} at {$res->reservation_time} (90 min window).");
                }
            }
        }

        return true;
    }

    /**
     * Get list of tables available for a specific reservation.
     */
    public static function get_available_tables_for_reservation($date, $time, $guest_count, $exclude_res_id = 0) {
        $all_tables = self::get_tables(true); // active only
        $available = [];

        foreach ($all_tables as $t) {
            $check = self::check_table_availability($t->id, $date, $time, $guest_count, $exclude_res_id);
            if ($check === true) {
                $available[] = $t;
            }
        }

        return $available;
    }

    /**
     * Helper to parse time string ("19:00", "7:00 PM", "19:30") to minutes from midnight.
     */
    public static function parse_time_to_minutes($time_str) {
        $timestamp = strtotime($time_str);
        if ($timestamp === false) {
            return false;
        }
        $h = (int) date('H', $timestamp);
        $m = (int) date('i', $timestamp);
        return ($h * 60) + $m;
    }
}

