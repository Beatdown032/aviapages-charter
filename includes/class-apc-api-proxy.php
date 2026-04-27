<?php
/**
 * APC_API_Proxy — Server-side proxy to AviaPages API.
 *
 * CONFIRMED FROM DEBUG RESPONSE (2026-04-17):
 * The flight_calculator v3 API:
 *   - Returns HTTP 200 even on errors, with an 'errors' array
 *   - Field names: 'departure_airport', 'arrival_airport', 'aircraft'
 *   - Integer airport IDs do NOT work → must use ICAO codes (strings)
 *   - Aircraft field needs aircraft_type ICAO (e.g. "HDJT") not profile_id
 *
 * Correct payload:
 *   departure_airport → ICAO string  e.g. "RPLL"
 *   arrival_airport   → ICAO string  e.g. "OMDB"
 *   aircraft          → aircraft type ICAO string e.g. "HDJT"  (optional)
 *   departure_date    → "YYYY-MM-DD"
 *   departure_time    → "HH:MM"      (optional)
 *   pax               → integer      (optional)
 */
defined( 'ABSPATH' ) || exit;

class APC_API_Proxy {

    private static function key(): string {
        $key = trim( (string) get_option( 'apc_api_key', '' ) );
        if ( $key === '' ) {
            throw new RuntimeException( 'AviaPages API key is not configured. Go to Charter Suite → Settings.' );
        }
        return $key;
    }

    private static function headers(): array {
        return [
            'Authorization' => 'Token ' . self::key(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'AviaPages-Charter-Suite/' . APC_VERSION,
        ];
    }

    private static function cache_ttl(): int {
        return max( 0, (int) get_option( 'apc_cache_ttl', 300 ) );
    }

    /* ── HTTP ────────────────────────────────────── */

    public static function get( string $endpoint, array $params = [], ?int $ttl = null ): array {
        $ttl = $ttl ?? self::cache_ttl();

        if ( $ttl > 0 ) {
            $ck = 'apc_' . md5( $endpoint . serialize( $params ) );
            $c  = get_transient( $ck );
            if ( $c !== false ) return $c;
        }

        $url  = add_query_arg( array_map( 'strval', $params ), APC_API_BASE . $endpoint );
        $resp = wp_remote_get( $url, [
            'headers'   => self::headers(),
            'timeout'   => 20,
            'sslverify' => true,
        ] );

        $data = self::parse( $resp, 'GET', $endpoint );
        if ( $ttl > 0 ) set_transient( $ck, $data, $ttl );
        return $data;
    }

    public static function post( string $endpoint, array $payload ): array {
        $body = wp_json_encode( $payload );
        if ( $body === false ) throw new RuntimeException( 'Failed to encode request payload.' );

        error_log( "[APC] POST {$endpoint} → " . substr( $body, 0, 500 ) );

        $resp = wp_remote_post( APC_API_BASE . $endpoint, [
            'headers'   => self::headers(),
            'body'      => $body,
            'timeout'   => 30,
            'sslverify' => true,
        ] );

        return self::parse( $resp, 'POST', $endpoint );
    }

    /**
     * Parse response.
     *
     * IMPORTANT: The AviaPages flight_calculator returns HTTP 200 even
     * on errors, with an 'errors' array in the body. We must check for
     * this AFTER successful HTTP parsing.
     */
    private static function parse( $resp, string $method, string $ep ): array {
        if ( is_wp_error( $resp ) ) {
            $err = $resp->get_error_message();
            error_log( "[APC] WP_Error {$method} {$ep}: {$err}" );
            throw new RuntimeException( 'Could not reach the AviaPages API. Check server connectivity.' );
        }

        $code = (int) wp_remote_retrieve_response_code( $resp );
        $raw  = wp_remote_retrieve_body( $resp );
        $data = json_decode( $raw, true );

        error_log( "[APC] {$method} {$ep} HTTP {$code}: " . substr( $raw, 0, 800 ) );

        if ( $code === 429 ) throw new RuntimeException( 'API rate limit reached. Please try again shortly.' );
        if ( $code === 401 || $code === 403 ) throw new RuntimeException( 'API authentication failed. Check your API key in Charter Suite → Settings.' );
        if ( $code === 404 ) throw new RuntimeException( "API endpoint not found: {$ep}" );
        if ( $code >= 500 ) throw new RuntimeException( 'AviaPages API is temporarily unavailable. Please try again.' );

        // Handle 400 with field errors
        if ( $code === 400 ) {
            throw new RuntimeException( self::extract_error( $data ) ?? 'Invalid request parameters.' );
        }

        // Accept 200 and 201
        if ( $code !== 200 && $code !== 201 ) {
            throw new RuntimeException( "Unexpected API response (HTTP {$code})." );
        }

        if ( ! is_array( $data ) ) {
            throw new RuntimeException( 'API returned malformed JSON.' );
        }

        // CRITICAL: flight_calculator returns HTTP 200 with 'errors' array on failure
        // Check for this BEFORE returning data
        if ( ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
            $messages = array_map(
                static fn( $e ) => $e['message'] ?? 'Unknown error',
                $data['errors']
            );
            throw new RuntimeException( implode( ' | ', $messages ) );
        }

        return $data;
    }

    private static function extract_error( ?array $d ): ?string {
        if ( ! $d ) return null;
        if ( isset( $d['detail'] ) )           return (string) $d['detail'];
        if ( isset( $d['message'] ) )          return (string) $d['message'];
        if ( isset( $d['non_field_errors'] ) ) return implode( ' ', (array) $d['non_field_errors'] );
        if ( ! empty( $d['errors'] ) )         return implode( ' | ', array_column( $d['errors'], 'message' ) );
        $msgs = [];
        foreach ( $d as $field => $errs ) {
            if ( is_array( $errs ) ) {
                $msgs[] = ucfirst( str_replace( '_', ' ', $field ) ) . ': ' . implode( ', ', $errs );
            }
        }
        return $msgs ? implode( ' | ', $msgs ) : null;
    }

    private static function log( string $msg ): void {
        error_log( '[APC] ' . $msg );
    }

    /* ══════════════════════════════════════════════
     *  PUBLIC API WRAPPERS
     * ══════════════════════════════════════════════ */

    /**
     * Airport search — returns full objects.
     * Each result has: id (int), icao (string), iata (string), name, city, country
     * The ICAO string is what flight_calculator needs.
     */
    public static function airports( string $query, int $limit = 15 ): array {
        return self::get( '/airports/', [ 'search' => $query, 'page_size' => $limit ], 600 );
    }

    public static function aircraft_classes(): array {
        return self::get( '/aircraft_classes/', [], 7200 );
    }

    public static function aircraft( array $filters = [], int $limit = 40 ): array {
        return self::get( '/aircraft/', array_merge( [ 'page_size' => $limit ], $filters ), 300 );
    }

    /**
     * Aircraft profiles — each has:
     *   aircraft_profile_id  (int)   — for profile-based calc (if supported)
     *   aircraft_type_icao   (string) — ICAO type code e.g. "HDJT"
     *   aircraft_type_name   (string) — e.g. "HondaJet"
     */
    public static function aircraft_profiles( array $params = [] ): array {
        return self::get( '/aircraft_profiles/', array_merge(
            [ 'page_size' => 100, 'performance' => 'true' ],
            $params
        ), 7200 );
    }

    /**
     * Flight Calculator — POST /v3/flight_calculator/
     *
     * CONFIRMED correct payload format (from debug 2026-04-17):
     *   departure_airport → ICAO string  (e.g. "RPLL")
     *   arrival_airport   → ICAO string  (e.g. "OMDB")
     *   aircraft          → aircraft_type_icao OR aircraft_profile_id (try both)
     *   departure_date    → "YYYY-MM-DD"
     *   departure_time    → "HH:MM"
     *   pax               → integer
     *
     * Returns HTTP 200 with either:
     *   Success: { request_id: int, ... flight data ... }
     *   Error:   { errors: [{code, scope, message}], airport: {...}, aircraft: null }
     *   (errors array is checked in parse() and thrown as RuntimeException)
     */
    public static function flight_calculator( array $payload ): array {
        return self::post( '/flight_calculator/', $payload );
    }

    public static function price_calculator( array $payload ): array {
        return self::post( '/price_calculator/', $payload );
    }

    /**
     * Chained: Flight → Price (best-effort price).
     * Returns { flight: {...}, price: {...} }
     */
    public static function flight_and_price( array $payload, float $commission ): array {
        $flight = self::flight_calculator( $payload );

        self::log( 'flight_calculator keys: ' . implode( ', ', array_keys( $flight ) ) );

        $price = [];
        try {
            $price_payload                       = $payload;
            $price_payload['commission_percent'] = $commission;
            if ( isset( $flight['airway_time'] ) ) {
                $price_payload['flight_time'] = $flight['airway_time'];
            }
            $price = self::price_calculator( $price_payload );
            self::log( 'price_calculator keys: ' . implode( ', ', array_keys( $price ) ) );
        } catch ( RuntimeException $e ) {
            self::log( 'price_calculator failed (non-fatal): ' . $e->getMessage() );
        }

        return [ 'flight' => $flight, 'price' => $price ];
    }

    public static function empty_legs( array $filters = [], int $limit = 50 ): array {
        return self::get( '/availabilities/', array_merge(
            [ 'availability_type' => 'empty_leg', 'page_size' => $limit ],
            $filters
        ), 180 );
    }

    public static function charter_search( array $payload ): array {
        return self::post( '/charter_searches/', $payload );
    }

    public static function charter_quote_request( array $payload ): array {
        return self::post( '/charter_quote_requests/', $payload );
    }

    public static function test_connection(): array {
        $data = self::get( '/aircraft_classes/', [], 0 );
        return [ 'ok' => true, 'classes' => (int) ( $data['count'] ?? count( $data['results'] ?? [] ) ) ];
    }

    public static function flush_cache(): void {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_apc_%'
                OR option_name LIKE '_transient_timeout_apc_%'"
        );
    }
}
