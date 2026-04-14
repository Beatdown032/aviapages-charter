<?php
defined( 'ABSPATH' ) || exit;

class AviaPages_API {

    private static function key(): string {
        return (string) get_option( 'aviapages_api_key', '' );
    }

    /**
     * Generic GET request with caching.
     */
    public static function get( string $endpoint, array $params = [], int $cache_seconds = 300 ): array {
        $cache_key = 'avia_' . md5( $endpoint . serialize( $params ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url      = AVIAPAGES_API_BASE . $endpoint;
        if ( $params ) {
            $url .= '?' . http_build_query( $params );
        }

        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Token ' . self::key(),
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            return [ 'error' => $body['detail'] ?? "HTTP $code" ];
        }

        set_transient( $cache_key, $body, $cache_seconds );
        return $body;
    }

    /**
     * Generic POST request (never cached).
     */
    public static function post( string $endpoint, array $payload ): array {
        $response = wp_remote_post( AVIAPAGES_API_BASE . $endpoint, [
            'headers' => [
                'Authorization' => 'Token ' . self::key(),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            $msg = $body['detail'] ?? $body['non_field_errors'][0] ?? "HTTP $code";
            return [ 'error' => $msg ];
        }

        return $body ?? [];
    }

    /* ---- Convenience wrappers ---- */

    public static function search_airports( string $query ): array {
        return self::get( '/airports/', [ 'search_name' => $query, 'page_size' => 20 ], 600 );
    }

    public static function search_aircraft( array $filters = [] ): array {
        return self::get( '/aircraft/', array_merge( [ 'page_size' => 30 ], $filters ), 300 );
    }

    public static function aircraft_classes(): array {
        return self::get( '/aircraft_classes/', [], 3600 );
    }

    public static function flight_calculator( array $data ): array {
        return self::post( '/flight_calculator/', $data );
    }

    public static function price_calculator( array $data ): array {
        return self::post( '/price_calculator/', $data );
    }

    public static function empty_legs( array $filters = [] ): array {
        return self::get( '/availabilities/', array_merge( [ 'page_size' => 50 ], $filters ), 180 );
    }

    public static function charter_searches( array $data ): array {
        return self::post( '/charter_searches/', $data );
    }

    public static function charter_quote_request( array $data ): array {
        return self::post( '/charter_quote_requests/', $data );
    }

    public static function aircraft_profiles( string $search = '' ): array {
        $params = [ 'page_size' => 100, 'performance' => 'true' ];
        if ( $search ) {
            $params['search_aircraft_type_name'] = $search;
        }
        return self::get( '/aircraft_profiles/', $params, 3600 );
    }
}
