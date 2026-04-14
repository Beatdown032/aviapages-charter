<?php
defined( 'ABSPATH' ) || exit;

class AviaPages_Ajax {

    public static function init(): void {
        $actions = [
            'avia_search_airports',
            'avia_search_aircraft',
            'avia_flight_calc',
            'avia_price_calc',
            'avia_empty_legs',
            'avia_charter_search',
            'avia_charter_request',
            'avia_aircraft_classes',
        ];
        foreach ( $actions as $action ) {
            add_action( "wp_ajax_$action",        [ __CLASS__, $action ] );
            add_action( "wp_ajax_nopriv_$action", [ __CLASS__, $action ] );
        }
    }

    private static function verify(): void {
        check_ajax_referer( 'aviapages_nonce', 'nonce' );
    }

    private static function send( array $data ): void {
        if ( isset( $data['error'] ) ) {
            wp_send_json_error( $data['error'] );
        }
        wp_send_json_success( $data );
    }

    /* ---- Airport autocomplete ---- */
    public static function avia_search_airports(): void {
        self::verify();
        $q = sanitize_text_field( $_GET['q'] ?? '' );
        if ( strlen( $q ) < 2 ) {
            wp_send_json_success( [] );
        }
        $res = AviaPages_API::search_airports( $q );
        $out = [];
        foreach ( $res['results'] ?? [] as $ap ) {
            $out[] = [
                'icao'  => $ap['icao'] ?? '',
                'iata'  => $ap['iata'] ?? '',
                'name'  => $ap['name'] ?? '',
                'city'  => $ap['city_name'] ?? '',
                'country' => $ap['country_name'] ?? '',
                'label' => sprintf( '%s (%s) — %s', $ap['name'] ?? '', $ap['icao'] ?? $ap['iata'] ?? '', $ap['city_name'] ?? '' ),
            ];
        }
        wp_send_json_success( $out );
    }

    /* ---- Aircraft search ---- */
    public static function avia_search_aircraft(): void {
        self::verify();
        $filters = [];
        if ( ! empty( $_POST['class'] ) ) {
            $filters['aircraft_class_id'] = (int) $_POST['class'];
        }
        if ( ! empty( $_POST['pax'] ) ) {
            $filters['pax_minimum_min'] = (int) $_POST['pax'];
        }
        self::send( AviaPages_API::search_aircraft( $filters ) );
    }

    /* ---- Aircraft classes ---- */
    public static function avia_aircraft_classes(): void {
        self::verify();
        self::send( AviaPages_API::aircraft_classes() );
    }

    /* ---- Flight calculator ---- */
    public static function avia_flight_calc(): void {
        self::verify();
        $from    = sanitize_text_field( $_POST['from'] ?? '' );
        $to      = sanitize_text_field( $_POST['to'] ?? '' );
        $profile = (int) ( $_POST['profile'] ?? 0 );
        $date    = sanitize_text_field( $_POST['date'] ?? date( 'Y-m-d' ) );

        if ( ! $from || ! $to || ! $profile ) {
            wp_send_json_error( 'Please fill in all required fields.' );
        }

        $payload = [
            'departure_airport_icao'  => $from,
            'destination_airport_icao'=> $to,
            'aircraft_profile_id'     => $profile,
            'departure_date'          => $date,
        ];

        self::send( AviaPages_API::flight_calculator( $payload ) );
    }

    /* ---- Price calculator ---- */
    public static function avia_price_calc(): void {
        self::verify();
        $from    = sanitize_text_field( $_POST['from'] ?? '' );
        $to      = sanitize_text_field( $_POST['to'] ?? '' );
        $profile = (int) ( $_POST['profile'] ?? 0 );
        $date    = sanitize_text_field( $_POST['date'] ?? date( 'Y-m-d' ) );
        $pax     = max( 1, (int) ( $_POST['pax'] ?? 1 ) );

        if ( ! $from || ! $to || ! $profile ) {
            wp_send_json_error( 'Please fill in all required fields.' );
        }

        $commission = (float) get_option( 'aviapages_commission_pct', 15 );

        $payload = [
            'departure_airport_icao'   => $from,
            'destination_airport_icao' => $to,
            'aircraft_profile_id'      => $profile,
            'departure_date'           => $date,
            'pax'                      => $pax,
            'commission_percent'       => $commission,
        ];

        self::send( AviaPages_API::price_calculator( $payload ) );
    }

    /* ---- Empty legs ---- */
    public static function avia_empty_legs(): void {
        self::verify();
        $filters = [ 'availability_type' => 'empty_leg' ];
        if ( ! empty( $_POST['from_icao'] ) ) {
            $filters['departure_airport_icao'] = sanitize_text_field( $_POST['from_icao'] );
        }
        if ( ! empty( $_POST['to_icao'] ) ) {
            $filters['destination_airport_icao'] = sanitize_text_field( $_POST['to_icao'] );
        }
        if ( ! empty( $_POST['date'] ) ) {
            $filters['date_from'] = sanitize_text_field( $_POST['date'] );
        }
        self::send( AviaPages_API::empty_legs( $filters ) );
    }

    /* ---- Charter search (find operators) ---- */
    public static function avia_charter_search(): void {
        self::verify();
        $from = sanitize_text_field( $_POST['from'] ?? '' );
        $to   = sanitize_text_field( $_POST['to'] ?? '' );
        $date = sanitize_text_field( $_POST['date'] ?? '' );
        $pax  = max( 1, (int) ( $_POST['pax'] ?? 1 ) );

        if ( ! $from || ! $to || ! $date ) {
            wp_send_json_error( 'All fields are required.' );
        }

        $payload = [
            'departure_airport_icao'   => $from,
            'destination_airport_icao' => $to,
            'departure_date'           => $date,
            'pax'                      => $pax,
        ];

        self::send( AviaPages_API::charter_searches( $payload ) );
    }

    /* ---- Submit charter request ---- */
    public static function avia_charter_request(): void {
        self::verify();

        $from  = sanitize_text_field( $_POST['from'] ?? '' );
        $to    = sanitize_text_field( $_POST['to'] ?? '' );
        $date  = sanitize_text_field( $_POST['date'] ?? '' );
        $pax   = max( 1, (int) ( $_POST['pax'] ?? 1 ) );
        $name  = sanitize_text_field( $_POST['name'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );

        if ( ! $from || ! $to || ! $date || ! $name || ! $email ) {
            wp_send_json_error( 'Please complete all required fields.' );
        }

        // Forward to lead email
        $lead_email = get_option( 'aviapages_lead_email', get_option( 'admin_email' ) );
        $subject    = "New Charter Inquiry: $from → $to on $date";
        $message    = "Name: $name\nEmail: $email\nPhone: $phone\n\nRoute: $from → $to\nDate: $date\nPax: $pax\n\nNotes:\n$notes";
        wp_mail( $lead_email, $subject, $message );

        // Submit to AviaPages API
        $payload = [
            'departure_airport_icao'   => $from,
            'destination_airport_icao' => $to,
            'departure_date'           => $date,
            'pax'                      => $pax,
            'client_name'              => $name,
            'client_email'             => $email,
            'client_phone'             => $phone,
            'comment'                  => $notes,
        ];
        $res = AviaPages_API::charter_quote_request( $payload );

        // Even if API call fails, the lead email was sent
        wp_send_json_success( [
            'message' => 'Your request has been submitted. We will contact you shortly.',
            'api'     => $res,
        ] );
    }
}
