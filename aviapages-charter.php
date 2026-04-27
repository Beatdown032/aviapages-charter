<?php
/**
 * Plugin Name:       AviaPages Charter Suite
 * Plugin URI:        https://aviapages.com/aviapages_api/
 * Description:       Production-ready private jet charter suite. Secure server-side proxy to AviaPages APIs — chained Flight & Price Calculator, Airport Autocomplete, Aircraft Search, Empty Legs board, and Charter Request form with lead capture.
 * Version:           2.0.0
 * Author:            AviaPages Charter Suite
 * Author URI:        https://aviapages.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aviapages-charter
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

/* ── Constants ───────────────────────────────────────────── */
define( 'APC_VERSION',  '2.0.0' );
define( 'APC_DIR',      plugin_dir_path( __FILE__ ) );
define( 'APC_URL',      plugin_dir_url( __FILE__ ) );
define( 'APC_API_BASE', 'https://api.aviapages.com/v3' );

/* ── Autoload ────────────────────────────────────────────── */
foreach ( [
    'includes/class-apc-api-proxy.php',
    'includes/class-apc-validator.php',
    'includes/class-apc-endpoints.php',
    'includes/class-apc-shortcodes.php',
    'admin/class-apc-admin.php',
] as $file ) {
    require_once APC_DIR . $file;
}

/* ── Bootstrap ───────────────────────────────────────────── */
add_action( 'plugins_loaded', static function () {
    APC_Endpoints::register();
    APC_Shortcodes::register();
    APC_Admin::boot();

    // Load translations
    load_plugin_textdomain(
        'aviapages-charter',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

/* ── Front-end assets ────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', static function () {
    wp_enqueue_style(
        'apc-styles',
        APC_URL . 'assets/css/charter.css',
        [],
        APC_VERSION
    );

    wp_enqueue_script(
        'apc-scripts',
        APC_URL . 'assets/js/charter.js',
        [ 'jquery' ],
        APC_VERSION,
        true   // load in footer
    );

    wp_localize_script( 'apc-scripts', 'APC', [
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'apc_nonce' ),
        'currency'   => get_option( 'apc_currency', 'USD' ),
        'commission' => (float) get_option( 'apc_commission', 15 ),
    ] );
} );

/* ── Activation ──────────────────────────────────────────── */
register_activation_hook( __FILE__, static function () {
    // Add defaults only if not already set (safe for re-activation)
    $defaults = [
        'apc_api_key'    => '',
        'apc_commission' => 15,
        'apc_currency'   => 'USD',
        'apc_lead_email' => get_option( 'admin_email' ),
        'apc_cache_ttl'  => 300,
    ];
    foreach ( $defaults as $key => $val ) {
        if ( get_option( $key ) === false ) {
            add_option( $key, $val );
        }
    }
} );

/* ── Deactivation: flush transient cache ─────────────────── */
register_deactivation_hook( __FILE__, static function () {
    APC_API_Proxy::flush_cache();
} );

/* ── Flush cache when settings are saved ─────────────────── */
add_action( 'update_option_apc_api_key',    [ 'APC_API_Proxy', 'flush_cache' ] );
add_action( 'update_option_apc_cache_ttl',  [ 'APC_API_Proxy', 'flush_cache' ] );
