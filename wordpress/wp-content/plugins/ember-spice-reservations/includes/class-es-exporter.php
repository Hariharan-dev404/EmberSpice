<?php
/**
 * Reservation Data Exporter with CSV Injection Protection
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Exporter {

    /**
     * Sanitize field value to prevent CSV Formula Injection (CWE-1236).
     */
    public static function sanitize_csv_field($value) {
        $value = (string) $value;
        // If the first character is =, +, -, @, \t, or \r, prepend a single quote
        if (preg_match('/^[=\+\-@\t\r]/', $value)) {
            $value = "'" . $value;
        }
        return $value;
    }

    /**
     * Generate and stream CSV download.
     */
    public static function export_csv($filters = []) {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access.');
        }

        check_admin_referer('es_export_reservations_nonce');

        $query_args = [
            'status'    => sanitize_text_field($filters['status'] ?? ''),
            'date_from' => sanitize_text_field($filters['date_from'] ?? ''),
            'date_to'   => sanitize_text_field($filters['date_to'] ?? ''),
            'limit'     => 10000,
            'offset'    => 0,
            'orderby'   => 'reservation_date',
            'order'     => 'ASC',
        ];

        $results = ES_Reservation_Manager::get_reservations($query_args);
        $items = $results['items'];

        $filename = 'ember-spice-reservations-' . date('Y-m-d-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Output UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Header
        fputcsv($output, [
            'Reference',
            'Status',
            'Guest Name',
            'Guest Email',
            'Guest Phone',
            'Reservation Date',
            'Seating Time',
            'Guests',
            'Assigned Table',
            'Table Location',
            'Special Requests',
            'Rejection Reason',
            'Cancellation Reason',
            'Submitted At',
            'Last Updated'
        ]);

        foreach ($items as $item) {
            $table_label = !empty($item->table_number) ? "Table {$item->table_number} ({$item->table_name})" : 'Unassigned';

            fputcsv($output, [
                self::sanitize_csv_field($item->reservation_ref),
                self::sanitize_csv_field(ucfirst($item->status)),
                self::sanitize_csv_field($item->guest_name),
                self::sanitize_csv_field($item->guest_email),
                self::sanitize_csv_field($item->guest_phone),
                self::sanitize_csv_field($item->reservation_date),
                self::sanitize_csv_field($item->reservation_time),
                self::sanitize_csv_field($item->guest_count),
                self::sanitize_csv_field($table_label),
                self::sanitize_csv_field($item->table_location ?: 'N/A'),
                self::sanitize_csv_field($item->special_requests),
                self::sanitize_csv_field($item->rejection_reason),
                self::sanitize_csv_field($item->cancellation_reason),
                self::sanitize_csv_field($item->created_at),
                self::sanitize_csv_field($item->updated_at),
            ]);
        }

        fclose($output);
        exit;
    }
}

