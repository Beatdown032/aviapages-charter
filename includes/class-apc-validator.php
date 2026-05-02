<?php
/**
 * APC_Validator
 * Centralised input sanitisation and validation.
 * All endpoint handlers call this before touching the proxy.
 */
defined( 'ABSPATH' ) || exit;

class APC_Validator {

    /** @var array<string,string> Accumulated error messages */
    private array $errors = [];

    /* ── Fluent field checks ────────────────────────── */

    public function required_string( string $key, mixed $value, int $min = 1 ): self {
        $v = is_string( $value ) ? trim( sanitize_text_field( $value ) ) : '';
        if ( strlen( $v ) < $min ) {
            $this->errors[ $key ] = "'{$key}' is required.";
        }
        return $this;
    }

    public function icao( string $key, mixed $value ): self {
        $v = strtoupper( trim( sanitize_text_field( (string) $value ) ) );
        // ICAO codes are 4 chars; IATA are 3 — accept both
        if ( ! preg_match( '/^[A-Z]{2,4}[A-Z0-9]{0,2}$/', $v ) ) {
            $this->errors[ $key ] = "'{$key}' must be a valid ICAO or IATA airport code.";
        }
        return $this;
    }

    public function date( string $key, mixed $value ): self {
        $v = sanitize_text_field( (string) $value );
        $d = DateTime::createFromFormat( 'Y-m-d', $v );
        if ( ! $d || $d->format( 'Y-m-d' ) !== $v ) {
            $this->errors[ $key ] = "'{$key}' must be a valid date (YYYY-MM-DD).";
        }
        return $this;
    }

    public function positive_int( string $key, mixed $value, int $min = 1, int $max = 500 ): self {
        $v = (int) $value;
        if ( $v < $min || $v > $max ) {
            $this->errors[ $key ] = "'{$key}' must be between {$min} and {$max}.";
        }
        return $this;
    }

    public function email( string $key, mixed $value ): self {
        $v = sanitize_email( (string) $value );
        if ( ! is_email( $v ) ) {
            $this->errors[ $key ] = "'{$key}' must be a valid email address.";
        }
        return $this;
    }

    public function optional_string( string $key, mixed $value, int $max = 2000 ): self {
        $v = sanitize_textarea_field( (string) $value );
        if ( strlen( $v ) > $max ) {
            $this->errors[ $key ] = "'{$key}' exceeds maximum length of {$max} characters.";
        }
        return $this;
    }

    /* ── Result helpers ─────────────────────────────── */

    public function passes(): bool {
        return empty( $this->errors );
    }

    public function first_error(): string {
        return reset( $this->errors ) ?: 'Validation failed.';
    }

    public function all_errors(): array {
        return $this->errors;
    }

    /* ── Static quick-clean helpers ─────────────────── */

    public static function clean_icao( mixed $v ): string {
        return strtoupper( trim( sanitize_text_field( (string) $v ) ) );
    }

    public static function clean_str( mixed $v ): string {
        return trim( sanitize_text_field( (string) $v ) );
    }

    public static function clean_int( mixed $v, int $default = 0 ): int {
        return (int) $v ?: $default;
    }

    public static function clean_float( mixed $v, float $default = 0.0 ): float {
        return (float) $v ?: $default;
    }

    public static function clean_date( mixed $v ): string {
        $s = sanitize_text_field( (string) $v );
        $d = DateTime::createFromFormat( 'Y-m-d', $s );
        return ( $d && $d->format( 'Y-m-d' ) === $s ) ? $s : gmdate( 'Y-m-d' );
    }

    public static function clean_email( mixed $v ): string {
        return sanitize_email( (string) $v );
    }

    public static function clean_textarea( mixed $v, int $max = 2000 ): string {
        return mb_substr( sanitize_textarea_field( (string) $v ), 0, $max );
    }
}
