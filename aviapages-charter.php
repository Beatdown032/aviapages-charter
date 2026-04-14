<?php
/**
 * Plugin Name: AviaPages Charter Suite
 * Plugin URI:  https://aviapages.com/aviapages_api/
 * Description: Full-featured private jet charter booking suite powered by the AviaPages API. Includes flight calculator, price estimator, aircraft search, empty legs, and charter request forms.
 * Version:     1.0.0
 * Author:      AviaPages Charter Suite
 * License:     GPL-2.0-or-later
 * Text Domain: aviapages-charter
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'AVIAPAGES_VERSION',    '1.0.0' );
define( 'AVIAPAGES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AVIAPAGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AVIAPAGES_API_BASE',   'https://api.aviapages.com/v3' );

// ---------- Autoload includes ----------
require_once AVIAPAGES_PLUGIN_DIR . 'includes/class-api.php';
require_once AVIAPAGES_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once AVIAPAGES_PLUGIN_DIR . 'includes/class-ajax.php';
require_once AVIAPAGES_PLUGIN_DIR . 'admin/class-admin.php';

// ---------- Boot ----------
add_action( 'plugins_loaded', function () {
    AviaPages_Shortcodes::init();
    AviaPages_Ajax::init();
    AviaPages_Admin::init();
} );

// ---------- Assets ----------
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'aviapages-charter',
        AVIAPAGES_PLUGIN_URL . 'assets/css/charter.css',
        [],
        AVIAPAGES_VERSION
    );
    wp_enqueue_script(
        'aviapages-charter',
        AVIAPAGES_PLUGIN_URL . 'assets/js/charter.js',
        [ 'jquery' ],
        AVIAPAGES_VERSION,
        true
    );
    wp_localize_script( 'aviapages-charter', 'aviaConfig', [
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'aviapages_nonce' ),
        'currency'  => get_option( 'aviapages_currency', 'USD' ),
        'commission'=> (float) get_option( 'aviapages_commission_pct', 15 ),
    ] );
} );

// ---------- Activation ----------
register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'aviapages_api_key' ) ) {
        add_option( 'aviapages_api_key', '' );
    }
    add_option( 'aviapages_commission_pct', 15 );
    add_option( 'aviapages_currency', 'USD' );
    add_option( 'aviapages_lead_email', get_option( 'admin_email' ) );
} );
