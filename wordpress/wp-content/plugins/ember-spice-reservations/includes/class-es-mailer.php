<?php
/**
 * Natural Organic Email Notification Engine for Ember Spice Reservations
 * Integrates directly with FluentSMTP via wp_mail()
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Mailer {

    /**
     * Get the designated admin recipient email.
     */
    public static function get_admin_email() {
        $admin = get_option('es_admin_notification_email', '23it011@psr.edu.in');
        if (empty($admin)) {
            $admin = get_option('admin_email');
        }
        return $admin;
    }

    /**
     * Build the natural organic Ember Spice responsive HTML email template.
     */
    public static function build_template($badge, $title, $intro, $details_rows, $notice_box = '', $cta_html = '') {
        $site_url = home_url();
        $year     = date('Y');

        $rows_html = '';
        if (!empty($details_rows)) {
            $rows_html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 24px 0; background-color: #FAF6ED; border: 1px solid rgba(120, 133, 104, 0.25); border-radius: 8px; overflow: hidden;">';
            $i = 0;
            foreach ($details_rows as $label => $val) {
                $bg = ($i % 2 === 0) ? 'background-color: #FAF6ED;' : 'background-color: #F4EEDC;';
                $rows_html .= "<tr style='{$bg} border-bottom: 1px solid rgba(120, 133, 104, 0.15);'>";
                $rows_html .= "<td style='padding: 12px 18px; font-weight: 700; color: #344B35; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; width: 38%; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>" . esc_html($label) . "</td>";
                $rows_html .= "<td style='padding: 12px 18px; color: #2A2C24; font-size: 13.5px; font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>" . wp_kses_post($val) . "</td>";
                $rows_html .= "</tr>";
                $i++;
            }
            $rows_html .= '</table>';
        }

        $notice_section = '';
        if (!empty($notice_box)) {
            $notice_section = '<div style="margin: 20px 0;">' . $notice_box . '</div>';
        }

        $cta_section = '';
        if (!empty($cta_html)) {
            $cta_section = '<div style="margin: 26px 0; text-align: center;">' . $cta_html . '</div>';
        }

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F4EEDC; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #2A2C24; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F4EEDC; padding: 36px 12px;">
        <tr>
            <td align="center">
                <!-- Main Card Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #FFFCF5; border: 1px solid rgba(120, 133, 104, 0.3); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(52, 75, 53, 0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 34px 36px 26px; background-color: #344B35; text-align: center; border-bottom: 3px solid #B99A65;">
                            <div style="font-size: 11px; font-weight: 700; letter-spacing: 2.5px; color: #B99A65; text-transform: uppercase; margin-bottom: 6px;">BOTANICAL HEARTH &bull; ORGANIC DINING</div>
                            <h1 style="margin: 0 0 4px; font-family: Georgia, \'Times New Roman\', serif; font-size: 26px; font-weight: 700; letter-spacing: 1.5px; color: #FFFCF5; text-transform: uppercase;">EMBER SPICE</h1>
                            <div style="font-size: 12px; letter-spacing: 1px; color: #EBF0E6; text-transform: uppercase;">Natural Indian Hearth Cuisine</div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 36px;">
                            ' . ($badge ? '<div style="display: inline-block; background-color: rgba(120, 133, 104, 0.15); border: 1px solid #788568; color: #344B35; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; padding: 5px 12px; border-radius: 4px; margin-bottom: 16px;">' . esc_html($badge) . '</div>' : '') . '
                            <h2 style="margin: 0 0 14px; font-family: Georgia, serif; font-size: 21px; color: #344B35; font-weight: 600; line-height: 1.35;">' . esc_html($title) . '</h2>
                            <div style="font-size: 14.5px; line-height: 1.7; color: #4A4D40;">' . $intro . '</div>

                            ' . $rows_html . '
                            ' . $notice_section . '
                            ' . $cta_section . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 36px; background-color: #EBF0E6; border-top: 1px solid rgba(120, 133, 104, 0.2); text-align: center; font-size: 12px; color: #777568; line-height: 1.6;">
                            <div style="color: #344B35; font-weight: 700; margin-bottom: 4px;">EMBER SPICE RESTAURANT</div>
                            <div>Farm-to-Table Hospitality &bull; Handcrafted Spices &bull; Live Charcoal Hearth</div>
                            <div style="margin-top: 8px; font-size: 11px; color: #8A887A;">
                                Lunch: 12:30 PM &ndash; 3:30 PM &bull; Dinner: 7:00 PM &ndash; 11:30 PM Daily<br>
                                &copy; ' . $year . ' Ember Spice. All rights reserved.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Dispatch email and log to es_notifications.
     */
    private static function send($to, $subject, $html, $recipient_type, $res_id = 0, $reply_to = '') {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        $sent = wp_mail($to, $subject, $html, $headers);

        // Record in notifications table
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'es_notifications';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_notifs'") === $table_notifs) {
            $wpdb->insert($table_notifs, [
                'reservation_id'  => $res_id,
                'recipient_email' => $to,
                'recipient_type'  => $recipient_type,
                'subject'         => $subject,
                'status'          => $sent ? 'sent' : 'failed',
                'sent_at'         => current_time('mysql'),
            ], ['%d', '%s', '%s', '%s', '%s', '%s']);
        }

        return $sent;
    }

    /**
     * Build cancel URL for customer.
     */
    public static function get_cancel_url($reservation) {
        return add_query_arg([
            'es_action' => 'cancel',
            'ref'       => $reservation->reservation_ref,
            'token'     => $reservation->cancellation_token,
        ], home_url('/'));
    }

    /**
     * Customer Notification 1: Request Received (Awaiting Confirmation).
     */
    public static function send_customer_request_received($res) {
        $cancel_url = self::get_cancel_url($res);

        $subject = "Table Request Received (Ref: {$res->reservation_ref}) — Ember Spice";
        $badge   = "RESERVATION RECEIVED &bull; AWAITING HOST CONFIRMATION";
        $title   = "We Have Received Your Table Request, {$res->guest_name}";

        $intro = "Thank you for choosing Ember Spice. We are honored to welcome you to our natural hearth and farm-fresh Indian dining experience. Your table request is currently being reviewed by our dining room hosts.";

        $rows = [
            'Booking Reference' => '<strong style="color:#B86F48; font-size:15px; font-family:Consolas,monospace;">' . esc_html($res->reservation_ref) . '</strong>',
            'Guest Name'        => esc_html($res->guest_name),
            'Reservation Date'  => date('l, F j, Y', strtotime($res->reservation_date)),
            'Requested Time'    => esc_html($res->reservation_time),
            'Number of Guests'  => intval($res->guest_count) . ' Guest(s)',
            'Contact Phone'     => esc_html($res->guest_phone),
            'Special Requests'  => !empty($res->special_requests) ? esc_html($res->special_requests) : 'None specified',
            'Status'            => '<span style="color:#8C6A30; font-weight:700;">Pending Host Confirmation</span>',
        ];

        $notice = '<div style="background-color: rgba(185, 154, 101, 0.16); border: 1px solid #B99A65; border-radius: 8px; padding: 16px 20px; color: #4A3E26; font-size: 13.5px; line-height: 1.65;">
            <div style="color: #8C6A30; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">⏳ Awaiting Host Confirmation</div>
            <div>Please note that your table is <strong>awaiting confirmation</strong>. Our hosts will review dining room capacity and send an official confirmation with your assigned table details shortly.</div>
        </div>';

        $cta = '<div style="font-size: 13px; color: #777568; line-height: 1.6;">Need to release or cancel this request? <a href="' . esc_url($cancel_url) . '" style="color: #B86F48; font-weight: 600; text-decoration: underline;">Cancel Reservation Request</a></div>';

        $html = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($res->guest_email, $subject, $html, 'customer', $res->id);
    }

    /**
     * Customer Notification 2: Confirmed & Table Assigned.
     */
    public static function send_customer_confirmed($res, $table_info = null) {
        $cancel_url = self::get_cancel_url($res);

        $table_str = $table_info ? "Table {$table_info->table_number} ({$table_info->name} &bull; {$table_info->location})" : "Assigned Dining Table";

        $subject = "Your Table is CONFIRMED (Ref: {$res->reservation_ref}) — Ember Spice";
        $badge   = "RESERVATION CONFIRMED &bull; TABLE ASSIGNED";
        $title   = "Delighted to Confirm Your Table, {$res->guest_name}!";

        $intro = "Your table reservation at Ember Spice has been officially confirmed and assigned. We look forward to offering you an unforgettable evening of live hearth culinary traditions and organic hospitality.";

        $rows = [
            'Booking Reference' => '<strong style="color:#344B35; font-size:15px; font-family:Consolas,monospace;">' . esc_html($res->reservation_ref) . '</strong>',
            'Status'            => '<span style="color:#344B35; font-weight:700;">CONFIRMED &amp; SEAT ASSIGNED</span>',
            'Assigned Table'    => '<strong style="color:#B86F48;">' . esc_html($table_str) . '</strong>',
            'Reservation Date'  => date('l, F j, Y', strtotime($res->reservation_date)),
            'Confirmed Time'    => esc_html($res->reservation_time),
            'Party Size'        => intval($res->guest_count) . ' Guest(s)',
            'Special Requests'  => !empty($res->special_requests) ? esc_html($res->special_requests) : 'None specified',
        ];

        $notice = '<div style="background-color: rgba(120, 133, 104, 0.16); border: 1px solid #788568; border-radius: 8px; padding: 16px 20px; color: #243525; font-size: 13.5px; line-height: 1.65;">
            <div style="color: #344B35; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">🌿 Host Guidelines &amp; Policies</div>
            <div>&bull; <strong>Arrival &amp; Grace Period:</strong> Tables are held for up to 15 minutes past your reserved seating time.<br>
            &bull; <strong>Dress Code:</strong> Smart casual to semi-formal attire is welcome.<br>
            &bull; <strong>Directions:</strong> Please check in with the host desk upon arrival.</div>
        </div>';

        $cta = '<div style="font-size: 13px; color: #777568; line-height: 1.6;">If your schedule changes, please release your table in advance: <a href="' . esc_url($cancel_url) . '" style="color: #B86F48; font-weight: 600; text-decoration: underline;">Cancel Reservation</a></div>';

        $html = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($res->guest_email, $subject, $html, 'customer', $res->id);
    }

    /**
     * Customer Notification 3: Rejected with reason.
     */
    public static function send_customer_rejected($res, $reason = '') {
        $subject = "Update on Your Table Request (Ref: {$res->reservation_ref}) — Ember Spice";
        $badge   = "RESERVATION STATUS UPDATE";
        $title   = "Unable to Accommodate Table Request";

        $intro = "Dear {$res->guest_name},<br><br>Thank you for considering Ember Spice. Due to dining room capacity constraints during your requested seating window, we are unfortunately unable to confirm your reservation on <strong>" . date('l, F j, Y', strtotime($res->reservation_date)) . " at {$res->reservation_time}</strong>.";

        $rows = [
            'Booking Reference' => esc_html($res->reservation_ref),
            'Requested Date'    => date('l, F j, Y', strtotime($res->reservation_date)),
            'Requested Time'    => esc_html($res->reservation_time),
            'Requested Guests'  => intval($res->guest_count) . ' Guest(s)',
            'Status'            => '<span style="color:#B86F48; font-weight:700;">Unable to Confirm</span>',
        ];

        $notice = '';
        if (!empty($reason)) {
            $notice = '<div style="background-color: #FDF1EA; border: 1px solid #B86F48; border-radius: 8px; padding: 16px 20px; color: #7A3212; font-size: 13.5px; line-height: 1.65;">
                <div style="color: #B86F48; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">Message from Dining Room Host:</div>
                <div>' . nl2br(esc_html($reason)) . '</div>
            </div>';
        }

        $cta = '<div style="text-align: center;"><a href="' . esc_url(home_url('/reservations/')) . '" style="display: inline-block; background-color: #B86F48; color: #FFFFFF; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 8px rgba(184, 111, 72, 0.3);">Select Another Seating Time</a></div>';

        $html = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($res->guest_email, $subject, $html, 'customer', $res->id);
    }

    /**
     * Customer Notification 4: Cancelled.
     */
    public static function send_customer_cancelled($res, $reason = '') {
        $subject = "Reservation Cancelled (Ref: {$res->reservation_ref}) — Ember Spice";
        $badge   = "RESERVATION CANCELLED";
        $title   = "Your Reservation Has Been Cancelled";

        $intro = "This email confirms that your table reservation (Ref: <strong>{$res->reservation_ref}</strong>) on " . date('l, F j, Y', strtotime($res->reservation_date)) . " at {$res->reservation_time} has been successfully cancelled.";

        $rows = [
            'Booking Reference' => esc_html($res->reservation_ref),
            'Reservation Date'  => date('l, F j, Y', strtotime($res->reservation_date)),
            'Reserved Time'     => esc_html($res->reservation_time),
            'Status'            => '<span style="color:#806B52; font-weight:700;">Cancelled</span>',
        ];

        $notice = '';
        if (!empty($reason)) {
            $notice = '<div style="background-color: rgba(128, 107, 82, 0.12); border: 1px solid #806B52; border-radius: 8px; padding: 14px 18px; color: #5C4B37; font-size: 13px;">
                <strong>Cancellation Note:</strong> ' . esc_html($reason) . '
            </div>';
        }

        $cta = '<div style="text-align: center;"><a href="' . esc_url(home_url('/reservations/')) . '" style="display: inline-block; background-color: #344B35; color: #FFFFFF; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px;">Book a New Reservation</a></div>';

        $html = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($res->guest_email, $subject, $html, 'customer', $res->id);
    }

    /**
     * Customer Notification 5: 24-Hour Reminder.
     */
    public static function send_customer_reminder($res, $table_info = null) {
        $cancel_url = self::get_cancel_url($res);
        $table_str  = $table_info ? "Table {$table_info->table_number} ({$table_info->location})" : "Reserved Dining Table";

        $subject = "Reminder: Your Ember Spice Reservation Tomorrow (Ref: {$res->reservation_ref})";
        $badge   = "UPCOMING DINING REMINDER &bull; 24 HOURS";
        $title   = "We Look Forward to Welcoming You Tomorrow, {$res->guest_name}!";

        $intro = "Your authentic hearth dining experience at Ember Spice is scheduled for tomorrow. Our chefs are curating hand-selected spices and preparing the hardwood charcoal hearths for your arrival.";

        $rows = [
            'Booking Reference' => '<strong style="color:#B86F48; font-family:Consolas,monospace;">' . esc_html($res->reservation_ref) . '</strong>',
            'Reservation Date'  => date('l, F j, Y', strtotime($res->reservation_date)),
            'Reservation Time'  => esc_html($res->reservation_time),
            'Party Size'        => intval($res->guest_count) . ' Guest(s)',
            'Assigned Table'    => esc_html($table_str),
        ];

        $notice = '<div style="background-color: rgba(120, 133, 104, 0.16); border: 1px solid #788568; border-radius: 8px; padding: 16px 20px; color: #243525; font-size: 13.5px; line-height: 1.65;">
            <div style="color: #344B35; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px;">📍 Host Desk Check-In</div>
            <div>Please arrive 5 to 10 minutes prior to your seating time. If you need any special dietary accommodations or assistance, please contact our host desk.</div>
        </div>';

        $cta = '<div style="font-size: 13px; color: #777568; line-height: 1.6;">Cannot make it? Please release your table so other guests can be accommodated: <a href="' . esc_url($cancel_url) . '" style="color: #B86F48; font-weight: 600; text-decoration: underline;">Cancel Reservation</a></div>';

        $html = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($res->guest_email, $subject, $html, 'customer', $res->id);
    }

    /**
     * Admin Notification 1: New Reservation Request.
     */
    public static function send_admin_new_request($res) {
        $admin_email = self::get_admin_email();
        $admin_url   = admin_url('admin.php?page=es-reservations&status=pending');

        $subject = "[New Reservation Request] {$res->guest_name} — {$res->guest_count} Guests on {$res->reservation_date} at {$res->reservation_time}";
        $badge   = "ACTION REQUIRED &bull; NEW PENDING REQUEST";
        $title   = "New Table Request: {$res->guest_name}";

        $intro = "A new table reservation request has been submitted on Ember Spice. The booking is currently <strong>Pending Confirmation</strong> and requires host review and table assignment.";

        $rows = [
            'Reference Code'   => '<strong style="color:#B86F48; font-family:Consolas,monospace;">' . esc_html($res->reservation_ref) . '</strong>',
            'Guest Name'       => esc_html($res->guest_name),
            'Guest Email'      => esc_html($res->guest_email),
            'Guest Phone'      => esc_html($res->guest_phone),
            'Reservation Date' => date('l, F j, Y', strtotime($res->reservation_date)),
            'Requested Time'   => esc_html($res->reservation_time),
            'Party Size'       => intval($res->guest_count) . ' Guest(s)',
            'Special Requests' => !empty($res->special_requests) ? esc_html($res->special_requests) : 'None specified',
            'Submitted At'     => esc_html($res->created_at),
        ];

        $notice = '<div style="background-color: rgba(185, 154, 101, 0.16); border: 1px solid #B99A65; border-radius: 8px; padding: 14px 18px; color: #4A3E26; font-size: 13px; line-height: 1.6;">
            <strong style="color: #8C6A30;">⚡ Host Desk Action:</strong> Open the reservation registry to assign an available table and approve or decline with a reason.
        </div>';

        $cta = '<div style="text-align: center;">
            <a href="' . esc_url($admin_url) . '" style="display: inline-block; background-color: #B86F48; color: #FFFFFF; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; margin-right: 10px; box-shadow: 0 2px 8px rgba(184, 111, 72, 0.3);">Review in Admin Desk</a>
            <a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', $res->guest_phone)) . '" style="display: inline-block; background-color: transparent; border: 1px solid #344B35; color: #344B35; text-decoration: none; padding: 12px 22px; border-radius: 6px; font-weight: 600; font-size: 14px;">Call Guest</a>
        </div>';

        $reply_to = $res->guest_name . ' <' . $res->guest_email . '>';
        $html     = self::build_template($badge, $title, $intro, $rows, $notice, $cta);

        return self::send($admin_email, $subject, $html, 'admin', $res->id, $reply_to);
    }

    /**
     * Admin Notification 2: Customer Cancelled Reservation.
     */
    public static function send_admin_customer_cancelled($res) {
        $admin_email = self::get_admin_email();

        $subject = "[Reservation Cancelled by Guest] {$res->guest_name} — Ref: {$res->reservation_ref}";
        $badge   = "CUSTOMER CANCELLATION NOTICE";
        $title   = "Guest Cancelled Reservation: {$res->guest_name}";

        $intro = "Guest <strong>{$res->guest_name}</strong> has released their table reservation. Any assigned table has been returned to the open pool.";

        $rows = [
            'Reference Code'      => esc_html($res->reservation_ref),
            'Guest Name'          => esc_html($res->guest_name),
            'Reservation Date'    => date('l, F j, Y', strtotime($res->reservation_date)),
            'Reservation Time'    => esc_html($res->reservation_time),
            'Party Size'          => intval($res->guest_count) . ' Guest(s)',
            'Cancellation Reason' => !empty($res->cancellation_reason) ? esc_html($res->cancellation_reason) : 'Customer self-service cancellation via link',
            'Cancelled At'        => current_time('mysql'),
        ];

        $html = self::build_template($badge, $title, $intro, $rows, '', '');

        return self::send($admin_email, $subject, $html, 'admin', $res->id);
    }
}
