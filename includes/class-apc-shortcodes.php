<?php
/**
 * APC_Shortcodes — registers all [aviapages_*] shortcodes.
 */
defined( 'ABSPATH' ) || exit;

class APC_Shortcodes {

    public static function register(): void {
        $map = [
            'aviapages_calculator'     => 'render_calculator',
            'aviapages_aircraft'       => 'render_aircraft',
            'aviapages_empty_legs'     => 'render_empty_legs',
            'aviapages_charter_form'   => 'render_charter_form',
        ];
        foreach ( $map as $tag => $method ) {
            add_shortcode( $tag, [ __CLASS__, $method ] );
        }
    }

    /* Shared attribute defaults */
    private static function atts( array $defaults, $atts ): array {
        return shortcode_atts( $defaults, $atts, '' );
    }

    /* ── [aviapages_calculator] ─────────────────────── */

    public static function render_calculator( $atts ): string {
        $a = self::atts( [
            'title'      => 'Flight & Price Calculator',
            'show_price' => 'yes',
        ], $atts );
        ob_start();
        include APC_DIR . 'templates/calculator.php';
        return ob_get_clean();
    }

    /* ── [aviapages_aircraft] ───────────────────────── */

    public static function render_aircraft( $atts ): string {
        $a = self::atts( [
            'title' => 'Available Jets',
            'limit' => 12,
        ], $atts );
        ob_start();
        include APC_DIR . 'templates/aircraft.php';
        return ob_get_clean();
    }

    /* ── [aviapages_empty_legs] ─────────────────────── */

    public static function render_empty_legs( $atts ): string {
        $a = self::atts( [
            'title' => 'Empty Leg Deals',
            'limit' => 20,
        ], $atts );
        ob_start();
        include APC_DIR . 'templates/empty-legs.php';
        return ob_get_clean();
    }

    /* ── [aviapages_charter_form] ───────────────────── */

    public static function render_charter_form( $atts ): string {
        $a = self::atts( [
            'title' => 'Request a Charter Flight',
        ], $atts );
        ob_start();
        include APC_DIR . 'templates/charter-form.php';
        return ob_get_clean();
    }
}
