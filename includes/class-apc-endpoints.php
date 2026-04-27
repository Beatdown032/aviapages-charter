<?php
/**
 * APC_Endpoints — AJAX proxy handlers.
 *
 * CONFIRMED from debug (2026-04-17):
 * The AviaPages flight_calculator v3 API uses:
 *   departure_airport → ICAO string (e.g. "RPLL") — NOT integer ID
 *   arrival_airport   → ICAO string (e.g. "OMDB") — NOT integer ID
 *   aircraft          → aircraft_type_icao string  — NOT profile integer
 *   departure_date    → "YYYY-MM-DD"
 *   departure_time    → "HH:MM" (optional)
 *   pax               → integer (optional)
 *
 * The API returns HTTP 200 with errors[] array on failure —
 * handled in APC_API_Proxy::parse() which throws RuntimeException.
 */
defined( 'ABSPATH' ) || exit;

class APC_Endpoints {

    private static array $actions = [
        'apc_airports',
        'apc_aircraft_classes',
        'apc_aircraft_profiles',
        'apc_aircraft_search',
        'apc_flight_and_price',
        'apc_flight_only',
        'apc_empty_legs',
        'apc_charter_search',
        'apc_charter_request',
        'apc_debug_flight',
    ];

    public static function register(): void {
        foreach ( self::$actions as $action ) {
            add_action( "wp_ajax_{$action}",        [ __CLASS__, $action ] );
            add_action( "wp_ajax_nopriv_{$action}", [ __CLASS__, $action ] );
        }
    }

    /* ── Helpers ─────────────────────────────────── */

    private static function verify(): void {
        if ( ! check_ajax_referer( 'apc_nonce', 'nonce', false ) ) {
            self::fail( 'Security check failed. Please refresh the page.' );
        }
    }

    private static function ok( array $data ): never {
        wp_send_json_success( $data, 200 );
    }

    private static function fail( string $message ): never {
        wp_send_json_error( [ 'message' => $message ], 200 );
    }

    private static function try_proxy( callable $fn ): never {
        try {
            self::ok( is_array( $r = $fn() ) ? $r : [] );
        } catch ( RuntimeException $e ) {
            self::fail( $e->getMessage() );
        }
    }

    /** Read from POST first, fall back to GET */
    private static function input( string $key, string $default = '' ): string {
        return trim( (string) ( $_POST[ $key ] ?? $_GET[ $key ] ?? $default ) );
    }

    /* ══════════════════════════════════════════════
     *  1. AIRPORTS — full objects with ICAO + integer id
     * ══════════════════════════════════════════════ */

    public static function apc_airports(): void {
        self::verify();
        $q = APC_Validator::clean_str( self::input( 'q' ) );
        if ( mb_strlen( $q ) < 2 ) self::ok( [] );

        self::try_proxy( static function () use ( $q ) {
            $raw = APC_API_Proxy::airports( $q );
            return array_map( static fn( $ap ) => [
                'id'         => (int) ( $ap['id'] ?? 0 ),
                'icao'       => $ap['icao']  ?? '',
                'iata'       => $ap['iata']  ?? '',
                'name'       => $ap['name']  ?? '',
                'city'       => is_array( $ap['city'] ?? null )
                                ? ( $ap['city']['name'] ?? '' )
                                : ( $ap['city_name'] ?? '' ),
                'country'    => is_array( $ap['country'] ?? null )
                                ? ( $ap['country']['name'] ?? '' )
                                : ( $ap['country_name'] ?? '' ),
                'timezone'   => $ap['time_zone']  ?? '',
                'time_shift' => $ap['time_shift'] ?? '',
            ], $raw['results'] ?? [] );
        } );
    }

    /* ══════════════════════════════════════════════
     *  2. AIRCRAFT CLASSES
     * ══════════════════════════════════════════════ */

    public static function apc_aircraft_classes(): void {
        self::verify();
        self::try_proxy( static fn () => APC_API_Proxy::aircraft_classes() );
    }

    /* ══════════════════════════════════════════════
     *  3. AIRCRAFT PROFILES
     *  Returns aircraft_type_icao which is what
     *  flight_calculator needs as 'aircraft' field.
     * ══════════════════════════════════════════════ */

    public static function apc_aircraft_profiles(): void {
        self::verify();
        $s = APC_Validator::clean_str( self::input( 'search' ) );
        self::try_proxy( static fn () => APC_API_Proxy::aircraft_profiles(
            $s ? [ 'search_aircraft_type_name' => $s ] : []
        ) );
    }

    /* ══════════════════════════════════════════════
     *  4. AIRCRAFT SEARCH (browse jets widget)
     * ══════════════════════════════════════════════ */

    public static function apc_aircraft_search(): void {
        self::verify();
        $filters = [];
        $class = APC_Validator::clean_int( self::input( 'class_id' ) );
        $pax   = APC_Validator::clean_int( self::input( 'pax_min' ) );
        $name  = APC_Validator::clean_str( self::input( 'search' ) );
        if ( $class > 0 )   $filters['aircraft_class_id'] = $class;
        if ( $pax   > 0 )   $filters['pax_minimum_min']   = $pax;
        if ( $name  !== '' ) $filters['search_name']       = $name;
        self::try_proxy( static fn () => APC_API_Proxy::aircraft( $filters ) );
    }

    /* ══════════════════════════════════════════════
     *  5. CHAINED: FLIGHT → PRICE
     *
     *  Uses ICAO codes (confirmed from debug output):
     *    departure_airport → from_icao (string)
     *    arrival_airport   → to_icao   (string)
     *    aircraft          → aircraft_type_icao (string, e.g. "HDJT")
     * ══════════════════════════════════════════════ */

    public static function apc_flight_and_price(): void {
        self::verify();

        $from_icao    = APC_Validator::clean_icao( self::input( 'from_icao' ) );
        $to_icao      = APC_Validator::clean_icao( self::input( 'to_icao' ) );
        $date         = self::input( 'date' );
        $time         = self::input( 'time' );
        $pax          = max( 1, min( 500, APC_Validator::clean_int( self::input( 'pax' ), 4 ) ) );
        $ac_icao      = APC_Validator::clean_str( self::input( 'aircraft_icao' ) );  // e.g. "HDJT"
        $etops        = self::input( 'etops' ) === '1';
        $payload_kg   = APC_Validator::clean_int( self::input( 'payload_kg' ) );
        $xfuel        = APC_Validator::clean_int( self::input( 'extra_fuel_kg' ) );

        if ( ! $from_icao ) self::fail( 'Please select a departure airport from the dropdown.' );
        if ( ! $to_icao )   self::fail( 'Please select a destination airport from the dropdown.' );
        if ( $from_icao === $to_icao ) self::fail( 'Departure and destination airports cannot be the same.' );
        if ( ! $ac_icao )   self::fail( 'Please select an aircraft type from the suggestions.' );

        $dt = DateTime::createFromFormat( 'Y-m-d', $date );
        if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date ) {
            self::fail( 'Please select a valid departure date.' );
        }

        $commission = max( 0.0, (float) get_option( 'apc_commission', 15 ) );

        // Build payload with CONFIRMED field names
        $body = [
            'departure_airport'                  => $from_icao,   // ICAO string
            'arrival_airport'                    => $to_icao,     // ICAO string
            'aircraft'                           => $ac_icao,     // aircraft_type_icao string
            'departure_date'                     => $date,
            'pax'                                => $pax,
            'airway_time_weather_impacted'        => true,
            'airway_time'                        => true,
            'great_circle_time'                  => true,
            'airway_fuel_weather_impacted'        => true,
            'airway_fuel_weather_impacted_detailed' => true,
        ];

        if ( $time && preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            $body['departure_time'] = $time;
        }
        if ( $etops )         $body['etops']       = true;
        if ( $payload_kg > 0 ) $body['payload']    = $payload_kg;
        if ( $xfuel      > 0 ) $body['extra_fuel'] = $xfuel;

        self::try_proxy( static function () use ( $body, $commission ) {
            return APC_API_Proxy::flight_and_price( $body, $commission );
        } );
    }

    /* ══════════════════════════════════════════════
     *  6. FLIGHT ONLY
     * ══════════════════════════════════════════════ */

    public static function apc_flight_only(): void {
        self::verify();

        $from_icao  = APC_Validator::clean_icao( self::input( 'from_icao' ) );
        $to_icao    = APC_Validator::clean_icao( self::input( 'to_icao' ) );
        $date       = self::input( 'date' );
        $time       = self::input( 'time' );
        $pax        = max( 1, min( 500, APC_Validator::clean_int( self::input( 'pax' ), 4 ) ) );
        $ac_icao    = APC_Validator::clean_str( self::input( 'aircraft_icao' ) );
        $etops      = self::input( 'etops' ) === '1';
        $payload_kg = APC_Validator::clean_int( self::input( 'payload_kg' ) );
        $xfuel      = APC_Validator::clean_int( self::input( 'extra_fuel_kg' ) );

        if ( ! $from_icao ) self::fail( 'Please select a departure airport.' );
        if ( ! $to_icao )   self::fail( 'Please select a destination airport.' );
        if ( $from_icao === $to_icao ) self::fail( 'Departure and destination airports cannot be the same.' );
        if ( ! $ac_icao )   self::fail( 'Please select an aircraft type.' );

        $dt = DateTime::createFromFormat( 'Y-m-d', $date );
        if ( ! $dt || $dt->format( 'Y-m-d' ) !== $date ) self::fail( 'Please select a valid departure date.' );

        $body = [
            'departure_airport'                    => $from_icao,
            'arrival_airport'                      => $to_icao,
            'aircraft'                             => $ac_icao,
            'departure_date'                       => $date,
            'pax'                                  => $pax,
            'airway_time_weather_impacted'          => true,
            'airway_time'                          => true,
            'great_circle_time'                    => true,
            'airway_fuel_weather_impacted'          => true,
            'airway_fuel_weather_impacted_detailed' => true,
        ];

        if ( $time && preg_match( '/^\d{2}:\d{2}$/', $time ) ) $body['departure_time'] = $time;
        if ( $etops )          $body['etops']       = true;
        if ( $payload_kg > 0 ) $body['payload']     = $payload_kg;
        if ( $xfuel      > 0 ) $body['extra_fuel']  = $xfuel;

        self::try_proxy( static function () use ( $body ) {
            $flight = APC_API_Proxy::flight_calculator( $body );
            return [ 'flight' => $flight, 'price' => [] ];
        } );
    }

    /* ══════════════════════════════════════════════
     *  7. EMPTY LEGS
     * ══════════════════════════════════════════════ */

    public static function apc_empty_legs(): void {
        self::verify();
        $filters = [];
        $from  = APC_Validator::clean_icao( self::input( 'from_icao' ) );
        $to    = APC_Validator::clean_icao( self::input( 'to_icao' ) );
        $date  = self::input( 'date_from' );
        $class = APC_Validator::clean_int( self::input( 'aircraft_class_id' ) );
        if ( $from  ) $filters['departure_airport_icao']   = $from;
        if ( $to    ) $filters['destination_airport_icao'] = $to;
        if ( $class ) $filters['aircraft_class_id']        = $class;
        if ( $date ) {
            $dt = DateTime::createFromFormat( 'Y-m-d', $date );
            if ( $dt && $dt->format( 'Y-m-d' ) === $date ) $filters['date_from'] = $date;
        }
        $limit = min( 100, max( 5, APC_Validator::clean_int( self::input( 'limit' ), 30 ) ) );
        self::try_proxy( static fn () => APC_API_Proxy::empty_legs( $filters, $limit ) );
    }

    /* ══════════════════════════════════════════════
     *  8. CHARTER SEARCH
     * ══════════════════════════════════════════════ */

    public static function apc_charter_search(): void {
        self::verify();
        $from = APC_Validator::clean_icao( self::input( 'from' ) );
        $to   = APC_Validator::clean_icao( self::input( 'to' ) );
        $date = self::input( 'date' );
        $pax  = max( 1, min( 500, APC_Validator::clean_int( self::input( 'pax' ), 1 ) ) );
        if ( ! $from || ! $to ) self::fail( 'Please select both airports.' );
        $body = [
            'departure_airport_icao'   => $from,
            'destination_airport_icao' => $to,
            'departure_date'           => $date,
            'pax'                      => $pax,
        ];
        $cls = APC_Validator::clean_int( self::input( 'class_id' ) );
        if ( $cls ) $body['aircraft_class_id'] = $cls;
        self::try_proxy( static fn () => APC_API_Proxy::charter_search( $body ) );
    }

    /* ══════════════════════════════════════════════
     *  9. CHARTER REQUEST
     * ══════════════════════════════════════════════ */

    public static function apc_charter_request(): void {
        self::verify();
        $from  = APC_Validator::clean_icao( self::input( 'from' ) );
        $to    = APC_Validator::clean_icao( self::input( 'to' ) );
        $date  = self::input( 'date' );
        $pax   = max( 1, min( 500, APC_Validator::clean_int( self::input( 'pax' ), 1 ) ) );
        $name  = APC_Validator::clean_str( self::input( 'name' ) );
        $email = APC_Validator::clean_email( self::input( 'email' ) );
        $phone = APC_Validator::clean_str( self::input( 'phone' ) );
        $notes = APC_Validator::clean_textarea( self::input( 'notes' ) );
        if ( ! $from || ! $to )      self::fail( 'Please select both airports.' );
        if ( ! $name )               self::fail( 'Please enter your full name.' );
        if ( ! is_email( $email ) )  self::fail( 'Please enter a valid email address.' );

        wp_mail(
            (string) get_option( 'apc_lead_email', get_option( 'admin_email' ) ),
            "[Charter Lead] {$from} → {$to} on {$date}",
            "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nRoute: {$from} → {$to}\nDate: {$date}\nPax: {$pax}\n\nNotes:\n{$notes}\n\n-- AviaPages Charter Suite --",
            [ 'Content-Type: text/plain; charset=UTF-8' ]
        );

        $api_ref = null;
        try {
            $r = APC_API_Proxy::charter_quote_request( [
                'departure_airport_icao'   => $from,
                'destination_airport_icao' => $to,
                'departure_date'           => $date,
                'pax'                      => $pax,
                'client_name'              => $name,
                'client_email'             => $email,
                'client_phone'             => $phone,
                'comment'                  => $notes,
            ] );
            $api_ref = $r['id'] ?? null;
        } catch ( RuntimeException $e ) {
            error_log( '[APC] charter_request API: ' . $e->getMessage() );
        }

        self::ok( [
            'message' => 'Your request has been submitted. Our team will be in touch shortly.',
            'api_ref' => $api_ref,
        ] );
    }

    /* ══════════════════════════════════════════════
     *  10. DEBUG ENDPOINT (admin only)
     *  Tests flight_calculator with ICAO codes
     *  so you can see the exact raw response.
     * ══════════════════════════════════════════════ */

    public static function apc_debug_flight(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            self::fail( 'Admin access required.' );
        }
        self::verify();

        // Accept EITHER icao strings OR integer IDs (try to be helpful)
        $from_raw = self::input( 'from_icao' ) ?: self::input( 'from_id' );
        $to_raw   = self::input( 'to_icao' )   ?: self::input( 'to_id' );
        $ac_raw   = self::input( 'aircraft_icao' ) ?: self::input( 'profile_id' );
        $date     = self::input( 'date' ) ?: gmdate( 'Y-m-d', strtotime( '+1 day' ) );
        $pax      = max( 1, APC_Validator::clean_int( self::input( 'pax' ), 4 ) );

        if ( ! $from_raw ) self::fail( 'from_icao (or from_id) is required.' );
        if ( ! $to_raw )   self::fail( 'to_icao (or to_id) is required.' );
        if ( ! $ac_raw )   self::fail( 'aircraft_icao (or profile_id) is required.' );

        // Normalise: uppercase ICAO codes
        $from_icao = strtoupper( trim( $from_raw ) );
        $to_icao   = strtoupper( trim( $to_raw ) );
        $ac        = strtoupper( trim( $ac_raw ) );

        $payload = [
            'departure_airport' => $from_icao,
            'arrival_airport'   => $to_icao,
            'aircraft'          => $ac,
            'departure_date'    => $date,
            'pax'               => $pax,
        ];

        try {
            $raw = APC_API_Proxy::flight_calculator( $payload );

            self::ok( [
                'sent_payload'  => $payload,
                'raw_flight'    => $raw,
                'response_keys' => array_keys( $raw ),
                'request_id'    => $raw['request_id'] ?? $raw['id'] ?? null,
                'airway_time'   => $raw['airway_time'] ?? null,
                'distance'      => $raw['distance'] ?? $raw['distance_km'] ?? null,
                'wind'          => $raw['wind'] ?? $raw['wind_kts'] ?? null,
            ] );
        } catch ( RuntimeException $e ) {
            self::fail( 'API error: ' . $e->getMessage() );
        }
    }
}
