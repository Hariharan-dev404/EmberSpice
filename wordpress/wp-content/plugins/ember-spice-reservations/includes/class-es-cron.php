<?php
/**
 * Automated 24-Hour Reminder Cron Engine
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Cron {

    const HOOK_NAME = 'ember_spice_daily_reminders';

    /**
     * Register cron hook and action.
     */
    public static function init() {
        add_action(self::HOOK_NAME, [__CLASS__, 'dispatch_reminders']);

        // Schedule event if not already scheduled
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'hourly', self::HOOK_NAME);
        }
    }

    /**
     * Deactivate cron.
     */
    public static function clear_schedule() {
        $timestamp = wp_next_scheduled(self::HOOK_NAME);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK_NAME);
        }
    }

    /**
     * Execute reminder routine for upcoming confirmed reservations ~24 hours ahead.
     */
    public static function dispatch_reminders() {
        global $wpdb;
        $table_res = $wpdb->prefix . 'es_reservations';

        // Target date is tomorrow in Asia/Kolkata / local WordPress time
        $tomorrow = date('Y-m-d', strtotime('+1 day', current_time('timestamp')));

        $reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_res WHERE status = 'confirmed' AND reminder_sent = 0 AND reservation_date = %s",
            $tomorrow
        ));

        if (empty($reservations)) {
            return 0;
        }

        $count = 0;
        foreach ($reservations as $res) {
            $table = !empty($res->table_id) ? ES_Table_Manager::get_table($res->table_id) : null;

            $sent = ES_Mailer::send_customer_reminder($res, $table);
            if ($sent) {
                $wpdb->update($table_res, [
                    'reminder_sent' => 1,
                    'updated_at'    => current_time('mysql'),
                ], ['id' => $res->id], ['%d', '%s'], ['%d']);

                ES_Reservation_Manager::log_activity(
                    $res->id,
                    'reminder_sent',
                    "24-Hour automated dining reminder dispatched to {$res->guest_email}.",
                    0,
                    'Cron Engine'
                );

                $count++;
            }
        }

        return $count;
    }
}

