<?php
/**
 * Uninstall script — runs when plugin is deleted (not deactivated).
 * Removes all plugin options and clears transient cache.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove all plugin options
$options = [
    'apc_api_key',
    'apc_commission',
    'apc_currency',
    'apc_lead_email',
    'apc_cache_ttl',
];

foreach ( $options as $opt ) {
    delete_option( $opt );
}

// Flush all plugin transients
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_apc_%'
        OR option_name LIKE '_transient_timeout_apc_%'"
);
