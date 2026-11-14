<?php
/**
 * Database Schema and Initialization for Ember Spice Reservations
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Database {

    const DB_VERSION = '1.0.0';

    /**
     * Create or update database tables.
     */
    public static function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_reservations = $wpdb->prefix . 'es_reservations';
        $table_tables       = $wpdb->prefix . 'es_tables';
        $table_activity     = $wpdb->prefix . 'es_activity_log';
        $table_notifs       = $wpdb->prefix . 'es_notifications';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Reservations Table
        $sql_reservations = "CREATE TABLE $table_reservations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reservation_ref varchar(32) NOT NULL,
            guest_name varchar(191) NOT NULL,
            guest_email varchar(191) NOT NULL,
            guest_phone varchar(64) NOT NULL,
            reservation_date date NOT NULL,
            reservation_time varchar(32) NOT NULL,
            guest_count int(11) NOT NULL DEFAULT 2,
            table_id bigint(20) unsigned NULL DEFAULT NULL,
            special_requests text NULL,
            status varchar(32) NOT NULL DEFAULT 'pending',
            rejection_reason text NULL,
            cancellation_token varchar(64) NULL,
            cancellation_reason text NULL,
            reminder_sent tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_ref (reservation_ref),
            KEY idx_date (reservation_date),
            KEY idx_status (status),
            KEY idx_token (cancellation_token),
            KEY idx_table (table_id)
        ) $charset_collate;";
        dbDelta($sql_reservations);

        // 2. Restaurant Tables Table
        $sql_tables = "CREATE TABLE $table_tables (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            table_number varchar(32) NOT NULL,
            name varchar(100) NOT NULL,
            capacity int(11) NOT NULL DEFAULT 4,
            location varchar(100) NOT NULL DEFAULT 'Main Dining Room',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_table_num (table_number),
            KEY idx_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_tables);

        // 3. Activity History Log Table
        $sql_activity = "CREATE TABLE $table_activity (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reservation_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_name varchar(100) NOT NULL DEFAULT 'System',
            action varchar(64) NOT NULL,
            details text NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_res_id (reservation_id),
            KEY idx_action (action)
        ) $charset_collate;";
        dbDelta($sql_activity);

        // 4. Notifications Log Table
        $sql_notifs = "CREATE TABLE $table_notifs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reservation_id bigint(20) unsigned NOT NULL,
            recipient_email varchar(191) NOT NULL,
            recipient_type varchar(32) NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(32) NOT NULL DEFAULT 'sent',
            sent_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_res_id (reservation_id),
            KEY idx_type (recipient_type)
        ) $charset_collate;";
        dbDelta($sql_notifs);

        update_option('es_reservations_db_version', self::DB_VERSION);

        // Seed initial tables if none exist
        self::seed_initial_tables();
    }

    /**
     * Populate standard restaurant tables if the table table is empty.
     */
    public static function seed_initial_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'es_tables';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $now = current_time('mysql');
        $default_tables = [
            ['T-01', 'Window Hearth Table 1', 2, 'Main Dining Room'],
            ['T-02', 'Window Hearth Table 2', 2, 'Main Dining Room'],
            ['T-03', 'Royal Center Table 3', 4, 'Main Dining Room'],
            ['T-04', 'Royal Center Table 4', 4, 'Main Dining Room'],
            ['T-05', 'Royal Center Table 5', 4, 'Main Dining Room'],
            ['T-06', 'Spice Courtyard Table 6', 6, 'Garden Patio'],
            ['T-07', 'Spice Courtyard Table 7', 6, 'Garden Patio'],
            ['T-08', 'Tandoor View Booth 8', 6, 'Hearth Lounge'],
            ['T-09', 'Grand Banquet Table 9', 8, 'Hearth Lounge'],
            ['T-10', 'Maharaja Private Dining Room', 12, 'Private Dining Suite'],
        ];

        foreach ($default_tables as $t) {
            $wpdb->insert($table_name, [
                'table_number' => $t[0],
                'name'         => $t[1],
                'capacity'     => $t[2],
                'location'     => $t[3],
                'is_active'    => 1,
                'created_at'   => $now,
            ], ['%s', '%s', '%d', '%s', '%d', '%s']);
        }
    }
}

