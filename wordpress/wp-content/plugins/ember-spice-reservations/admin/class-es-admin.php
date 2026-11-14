<?php
/**
 * Admin Controller and Menu System
 *
 * @package EmberSpiceReservations
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menus']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_init', [__CLASS__, 'handle_post_actions']);
    }

    /**
     * Register administration menus.
     */
    public static function register_menus() {
        $cap = 'manage_options';

        // Main Menu: Overview
        add_menu_page(
            'Ember Spice Reservations',
            'Reservations',
            $cap,
            'es-reservations-overview',
            [__CLASS__, 'render_overview_page'],
            'dashicons-food',
            26
        );

        // Submenu: Overview
        add_submenu_page(
            'es-reservations-overview',
            'Overview — Ember Spice',
            'Overview',
            $cap,
            'es-reservations-overview',
            [__CLASS__, 'render_overview_page']
        );

        // Submenu: All Reservations
        add_submenu_page(
            'es-reservations-overview',
            'All Reservations — Ember Spice',
            'All Reservations',
            $cap,
            'es-reservations',
            [__CLASS__, 'render_reservations_page']
        );

        // Submenu: Calendar
        add_submenu_page(
            'es-reservations-overview',
            'Reservation Calendar — Ember Spice',
            'Calendar',
            $cap,
            'es-reservations-calendar',
            [__CLASS__, 'render_calendar_page']
        );

        // Submenu: Table Management
        add_submenu_page(
            'es-reservations-overview',
            'Table Management — Ember Spice',
            'Tables',
            $cap,
            'es-reservations-tables',
            [__CLASS__, 'render_tables_page']
        );

        // Submenu: Reports & Export
        add_submenu_page(
            'es-reservations-overview',
            'Reports & CSV Export — Ember Spice',
            'Reports & Export',
            $cap,
            'es-reservations-reports',
            [__CLASS__, 'render_reports_page']
        );

        // Submenu: Settings
        add_submenu_page(
            'es-reservations-overview',
            'Reservation Settings — Ember Spice',
            'Settings',
            $cap,
            'es-reservations-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Enqueue styles and scripts on reservation admin screens.
     */
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'es-reservations') === false) {
            return;
        }

        wp_enqueue_style(
            'es-admin-css',
            ES_RESERVATIONS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ES_RESERVATIONS_VERSION
        );

        wp_enqueue_script(
            'es-admin-js',
            ES_RESERVATIONS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            ES_RESERVATIONS_VERSION,
            true
        );

        if (strpos($hook, 'es-reservations-calendar') !== false) {
            wp_enqueue_script(
                'es-calendar-js',
                ES_RESERVATIONS_PLUGIN_URL . 'assets/js/calendar.js',
                ['jquery'],
                ES_RESERVATIONS_VERSION,
                true
            );
        }

        wp_localize_script('es-admin-js', 'esAdmin', [
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('es_admin_nonce'),
            'today'     => current_time('Y-m-d'),
            'pluginUrl' => ES_RESERVATIONS_PLUGIN_URL,
        ]);
    }

    /**
     * Handle CSV export or settings POST actions.
     */
    public static function handle_post_actions() {
        if (isset($_POST['es_export_csv']) && check_admin_referer('es_export_reservations_nonce')) {
            ES_Exporter::export_csv($_POST);
        }

        if (isset($_POST['es_save_settings']) && check_admin_referer('es_save_settings_nonce')) {
            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            $admin_email = sanitize_email($_POST['es_admin_notification_email'] ?? '');
            if (!empty($admin_email) && is_email($admin_email)) {
                update_option('es_admin_notification_email', $admin_email);
            }

            $seating_duration = intval($_POST['es_seating_duration'] ?? 90);
            update_option('es_seating_duration', max(30, min(240, $seating_duration)));

            wp_safe_redirect(add_query_arg(['page' => 'es-reservations-settings', 'saved' => 1], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Page renderers
     */
    public static function render_overview_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/overview.php';
    }

    public static function render_reservations_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/reservations.php';
    }

    public static function render_calendar_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    public static function render_tables_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/tables.php';
    }

    public static function render_reports_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/reports.php';
    }

    public static function render_settings_page() {
        require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/views/settings.php';
    }
}

