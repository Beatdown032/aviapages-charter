<?php
defined( 'ABSPATH' ) || exit;

class APC_Admin {

    public static function boot(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'menu' ] );
        add_action( 'admin_init',            [ __CLASS__, 'settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
        add_action( 'admin_post_apc_flush_cache', [ __CLASS__, 'handle_flush' ] );
    }

    public static function menu(): void {
        add_menu_page(
            __( 'Charter Suite Settings', 'aviapages-charter' ),
            __( 'Charter Suite', 'aviapages-charter' ),
            'manage_options',
            'apc-settings',
            [ __CLASS__, 'page' ],
            'dashicons-airplane',
            58
        );
    }

    public static function settings(): void {
        $group = 'apc_group';
        register_setting( $group, 'apc_api_key',    [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( $group, 'apc_commission', [ 'sanitize_callback' => 'floatval' ] );
        register_setting( $group, 'apc_currency',   [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( $group, 'apc_lead_email', [ 'sanitize_callback' => 'sanitize_email' ] );
        register_setting( $group, 'apc_cache_ttl',  [ 'sanitize_callback' => 'absint' ] );
    }

    public static function assets( string $hook ): void {
        if ( strpos( $hook, 'apc-settings' ) === false ) return;
        wp_enqueue_style( 'apc-admin', APC_URL . 'assets/css/admin.css', [], APC_VERSION );
    }

    public static function handle_flush(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden', 403 );
        check_admin_referer( 'apc_flush_cache' );
        APC_API_Proxy::flush_cache();
        wp_redirect( add_query_arg( [ 'page' => 'apc-settings', 'flushed' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $saved  = isset( $_GET['settings-updated'] );
        $flushed = isset( $_GET['flushed'] );

        // Live connection test
        $connection = null;
        if ( get_option( 'apc_api_key' ) ) {
            try {
                $connection = APC_API_Proxy::test_connection();
            } catch ( RuntimeException $e ) {
                $connection = [ 'ok' => false, 'error' => $e->getMessage() ];
            }
        }

        include APC_DIR . 'admin/settings-page.php';
    }
}
