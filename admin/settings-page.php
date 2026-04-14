<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap avia-admin-wrap">
    <div class="avia-admin-header">
        <div class="avia-admin-logo">✈ AviaPages Charter Suite</div>
        <p class="avia-admin-sub">Configure your API connection, commission structure, and lead routing.</p>
    </div>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'aviapages_settings' ); ?>

        <div class="avia-admin-grid">

            <!-- API Connection -->
            <div class="avia-admin-card">
                <h2 class="avia-card-title">🔑 API Connection</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="aviapages_api_key">API Key</label></th>
                        <td>
                            <input type="text" name="aviapages_api_key" id="aviapages_api_key"
                                   value="<?php echo esc_attr( get_option( 'aviapages_api_key' ) ); ?>"
                                   class="regular-text" placeholder="Paste your AviaPages API key…" />
                            <p class="description">Get your key at <a href="https://aviapages.com/aviapages_api/" target="_blank">aviapages.com/aviapages_api</a></p>
                        </td>
                    </tr>
                </table>
                <?php
                // Quick connection test
                $key = get_option('aviapages_api_key','');
                if ($key): ?>
                <div class="avia-test-row">
                    <?php
                    $test = AviaPages_API::get('/aircraft_classes/', [], 0);
                    if (isset($test['error'])): ?>
                        <span class="avia-badge avia-badge--error">❌ Connection failed: <?php echo esc_html($test['error']); ?></span>
                    <?php else: ?>
                        <span class="avia-badge avia-badge--ok">✅ API connected — <?php echo (int)($test['count'] ?? 0); ?> aircraft classes loaded</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Commission -->
            <div class="avia-admin-card">
                <h2 class="avia-card-title">💰 Pricing & Commission</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="aviapages_commission_pct">Commission %</label></th>
                        <td>
                            <input type="number" name="aviapages_commission_pct" id="aviapages_commission_pct"
                                   value="<?php echo esc_attr( get_option( 'aviapages_commission_pct', 15 ) ); ?>"
                                   class="small-text" min="0" max="100" step="0.5" />
                            <p class="description">Added on top of base price when displaying estimates to clients.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="aviapages_currency">Display Currency</label></th>
                        <td>
                            <select name="aviapages_currency" id="aviapages_currency">
                                <?php foreach ( ['USD','EUR','GBP','AED','CHF'] as $c ) : ?>
                                    <option value="<?php echo $c; ?>" <?php selected( get_option('aviapages_currency','USD'), $c ); ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Lead Routing -->
            <div class="avia-admin-card">
                <h2 class="avia-card-title">📬 Lead Routing</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="aviapages_lead_email">Lead Email</label></th>
                        <td>
                            <input type="email" name="aviapages_lead_email" id="aviapages_lead_email"
                                   value="<?php echo esc_attr( get_option( 'aviapages_lead_email', get_option('admin_email') ) ); ?>"
                                   class="regular-text" />
                            <p class="description">Charter request submissions are forwarded to this address.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Shortcodes Reference -->
            <div class="avia-admin-card avia-admin-card--wide">
                <h2 class="avia-card-title">📋 Shortcode Reference</h2>
                <table class="widefat striped">
                    <thead><tr><th>Shortcode</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php
                        $codes = [
                            '[aviapages_flight_calculator]' => 'Interactive flight time & route calculator',
                            '[aviapages_price_calculator]'  => 'Charter price estimator with your commission built in',
                            '[aviapages_aircraft_search]'   => 'Browse & filter available charter jets',
                            '[aviapages_empty_legs]'        => 'Live empty leg deals board',
                            '[aviapages_charter_request]'   => 'Full charter inquiry form (sends lead + API request)',
                        ];
                        foreach ($codes as $sc => $desc): ?>
                        <tr>
                            <td><code><?php echo esc_html($sc); ?></code></td>
                            <td><?php echo esc_html($desc); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <?php submit_button( 'Save Settings', 'primary large' ); ?>
    </form>
</div>
