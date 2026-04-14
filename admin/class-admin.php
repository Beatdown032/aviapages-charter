<?php
defined( 'ABSPATH' ) || exit;

class AviaPages_Admin {

    public static function init(): void {
        add_action( 'admin_menu',       [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_init',       [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function add_menu(): void {
        add_menu_page(
            'AviaPages Charter Suite',
            'Charter Suite',
            'manage_options',
            'aviapages-charter',
            [ __CLASS__, 'render_settings' ],
            'dashicons-airplane',
            30
        );
    }

    public static function register_settings(): void {
        $fields = [
            'aviapages_api_key'        => 'string',
            'aviapages_commission_pct' => 'number',
            'aviapages_currency'       => 'string',
            'aviapages_lead_email'     => 'string',
        ];
        foreach ( $fields as $key => $type ) {
            register_setting( 'aviapages_settings', $key, [
                'type'              => $type,
                'sanitize_callback' => $type === 'number' ? 'floatval' : 'sanitize_text_field',
            ] );
        }
    }

    public static function admin_assets( string $hook ): void {
        if ( strpos( $hook, 'aviapages-charter' ) === false ) return;
        wp_enqueue_style( 'aviapages-admin', AVIAPAGES_PLUGIN_URL . 'assets/css/admin.css', [], AVIAPAGES_VERSION );
    }

    public static function render_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $saved = isset( $_GET['settings-updated'] );
        include AVIAPAGES_PLUGIN_DIR . 'admin/settings-page.php';
    }
}
