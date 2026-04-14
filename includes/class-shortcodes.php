<?php
defined( 'ABSPATH' ) || exit;

class AviaPages_Shortcodes {

    public static function init(): void {
        $shortcodes = [
            'aviapages_flight_calculator' => 'render_flight_calculator',
            'aviapages_price_calculator'  => 'render_price_calculator',
            'aviapages_aircraft_search'   => 'render_aircraft_search',
            'aviapages_empty_legs'        => 'render_empty_legs',
            'aviapages_charter_request'   => 'render_charter_request',
        ];
        foreach ( $shortcodes as $tag => $method ) {
            add_shortcode( $tag, [ __CLASS__, $method ] );
        }
    }

    /* ───────────────────────────────────────────────
     *  FLIGHT CALCULATOR
     * ─────────────────────────────────────────────── */
    public static function render_flight_calculator( $atts ): string {
        $a = shortcode_atts( [ 'title' => 'Flight Time & Route Calculator' ], $atts );
        ob_start();
        include AVIAPAGES_PLUGIN_DIR . 'templates/flight-calculator.php';
        return ob_get_clean();
    }

    /* ───────────────────────────────────────────────
     *  PRICE CALCULATOR
     * ─────────────────────────────────────────────── */
    public static function render_price_calculator( $atts ): string {
        $a = shortcode_atts( [ 'title' => 'Charter Price Estimator' ], $atts );
        ob_start();
        include AVIAPAGES_PLUGIN_DIR . 'templates/price-calculator.php';
        return ob_get_clean();
    }

    /* ───────────────────────────────────────────────
     *  AIRCRAFT SEARCH
     * ─────────────────────────────────────────────── */
    public static function render_aircraft_search( $atts ): string {
        $a = shortcode_atts( [ 'title' => 'Available Jets', 'class' => '' ], $atts );
        ob_start();
        include AVIAPAGES_PLUGIN_DIR . 'templates/aircraft-search.php';
        return ob_get_clean();
    }

    /* ───────────────────────────────────────────────
     *  EMPTY LEGS
     * ─────────────────────────────────────────────── */
    public static function render_empty_legs( $atts ): string {
        $a = shortcode_atts( [ 'title' => 'Empty Leg Deals', 'limit' => 20 ], $atts );
        ob_start();
        include AVIAPAGES_PLUGIN_DIR . 'templates/empty-legs.php';
        return ob_get_clean();
    }

    /* ───────────────────────────────────────────────
     *  CHARTER REQUEST FORM
     * ─────────────────────────────────────────────── */
    public static function render_charter_request( $atts ): string {
        $a = shortcode_atts( [ 'title' => 'Request a Charter Flight' ], $atts );
        ob_start();
        include AVIAPAGES_PLUGIN_DIR . 'templates/charter-request.php';
        return ob_get_clean();
    }
}
