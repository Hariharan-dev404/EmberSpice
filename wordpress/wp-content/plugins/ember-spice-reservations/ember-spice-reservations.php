<?php
/**
 * Plugin Name:       Ember Spice Reservation Manager
 * Plugin URI:        https://emberspice.page.gd/
 * Description:       Complete professional reservation management system for Ember Spice restaurant. Admin dashboard, table management, manual approval workflow, email notifications via FluentSMTP, 24-hour reminders, calendar view, and CSV export.
 * Version:           1.1.0
 * Author:            Ember Spice Dev
 * License:           GPL-2.0+
 * Text Domain:       ember-spice-reservations
 * Requires at least: 5.6
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ES_RESERVATIONS_VERSION',    '1.1.0');
define('ES_RESERVATIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ES_RESERVATIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload all classes
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-database.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-table-manager.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-mailer.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-reservation-manager.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-cron.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-exporter.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'includes/class-es-ajax-handler.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'admin/class-es-admin.php';
require_once ES_RESERVATIONS_PLUGIN_DIR . 'public/class-es-public.php';

/**
 * Activation hook — create or update database tables, seed defaults.
 */
function es_reservations_activate() {
    ES_Database::install();
    ES_Cron::init();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'es_reservations_activate');

/**
 * Deactivation hook — clear scheduled cron events.
 * Does NOT delete reservation tables or data.
 */
function es_reservations_deactivate() {
    ES_Cron::clear_schedule();
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'es_reservations_deactivate');

/**
 * Plugin init — register all hooks and handlers.
 */
function es_reservations_init() {
    // Ensure tables exist on each request if db version changed
    $installed_version = get_option('es_reservations_db_version', '0.0.0');
    if (version_compare($installed_version, ES_Database::DB_VERSION, '<')) {
        ES_Database::install();
    }

    // Register cron schedule (safe to call repeatedly)
    ES_Cron::init();

    // Register AJAX handlers (highest priority to override mu-plugin fallback)
    ES_Ajax_Handler::init();

    // Register public endpoints (customer cancellation page)
    ES_Public::init();

    // Register admin menus and assets
    if (is_admin()) {
        ES_Admin::init();
    }
}
add_action('plugins_loaded', 'es_reservations_init', 5);

/**
 * Override the mu-plugin reservation handler with our advanced version.
 * The mu-plugin registers handlers on 'init' with default priority 10.
 * We re-register at priority 1 (higher) on 'wp_ajax_*' so our class-es-ajax-handler.php
 * hooks fire first and call wp_send_json() which halts execution before the mu-plugin response.
 *
 * No code is removed from the mu-plugin — the override is clean and non-destructive.
 */

