<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap apc-admin">

    <header class="apc-admin__hero">
        <div class="apc-admin__hero-inner">
            <div class="apc-admin__brand">
                <span class="apc-admin__plane">✈</span>
                <div>
                    <h1 class="apc-admin__title">AviaPages Charter Suite</h1>
                    <p class="apc-admin__sub">Secure server-side proxy · Chained Flight &amp; Price APIs · Full booking flow</p>
                </div>
            </div>
            <div class="apc-admin__status">
                <?php if ( $connection ) : ?>
                    <?php if ( ! empty( $connection['ok'] ) ) : ?>
                        <span class="apc-badge apc-badge--ok">✓ API Connected — <?php echo (int) ( $connection['classes'] ?? 0 ); ?> aircraft classes</span>
                    <?php else : ?>
                        <span class="apc-badge apc-badge--err">✗ <?php echo esc_html( $connection['error'] ?? 'Connection failed' ); ?></span>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="apc-badge apc-badge--warn">⚠ Enter API key to connect</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><strong>✓ Settings saved.</strong> Cache cleared automatically.</p></div>
    <?php endif; ?>
    <?php if ( $flushed ) : ?>
        <div class="notice notice-success is-dismissible"><p><strong>✓ Cache flushed.</strong></p></div>
    <?php endif; ?>

    <!-- Tab navigation -->
    <nav class="apc-admin-tabs">
        <button class="apc-admin-tab apc-admin-tab--active" data-tab="settings">⚙ Settings</button>
        <button class="apc-admin-tab" data-tab="debug">🔍 API Debug</button>
        <button class="apc-admin-tab" data-tab="shortcodes">📋 Shortcodes</button>
    </nav>

    <!-- ════════════════════════════════════════════════════════════
         SETTINGS TAB
         IMPORTANT: The "Flush Cache" button lives in its OWN
         standalone <form> that is OUTSIDE this settings <form>.
         Nested <form> tags break browser form submission entirely.
    ════════════════════════════════════════════════════════════ -->
    <div class="apc-tab-pane" id="apc-tab-settings">

        <form method="post" action="options.php" id="apc-settings-form">
            <?php settings_fields( 'apc_group' ); ?>

            <div class="apc-admin__grid">

                <!-- API Connection -->
                <div class="apc-card">
                    <h2 class="apc-card__title">🔑 API Connection</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="apc_api_key">API Key</label></th>
                            <td>
                                <input
                                    type="text"
                                    name="apc_api_key"
                                    id="apc_api_key"
                                    class="regular-text apc-key-input"
                                    value="<?php echo esc_attr( get_option( 'apc_api_key', '' ) ); ?>"
                                    placeholder="Paste your AviaPages API token…"
                                    spellcheck="false"
                                    autocomplete="off"
                                />
                                <p class="description">
                                    Stored server-side only — never exposed to the browser.<br>
                                    Get yours at <a href="https://aviapages.com/aviapages_api/" target="_blank" rel="noopener">aviapages.com/aviapages_api</a>
                                </p>
                                <p style="margin-top:.4rem">
                                    <button type="button" class="button button-small" onclick="
                                        var f=document.getElementById('apc_api_key');
                                        f.type = f.type==='password' ? 'text' : 'password';
                                    ">👁 Show / Hide</button>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="apc_cache_ttl">Cache TTL (seconds)</label></th>
                            <td>
                                <input type="number" name="apc_cache_ttl" id="apc_cache_ttl"
                                       class="small-text"
                                       value="<?php echo esc_attr( get_option( 'apc_cache_ttl', 300 ) ); ?>"
                                       min="0" max="86400" step="30" /> seconds
                                <p class="description">0 = no caching. Calculator calls are never cached regardless.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Pricing -->
                <div class="apc-card">
                    <h2 class="apc-card__title">💰 Pricing</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="apc_commission">Commission %</label></th>
                            <td>
                                <input type="number" name="apc_commission" id="apc_commission"
                                       class="small-text"
                                       value="<?php echo esc_attr( get_option( 'apc_commission', 15 ) ); ?>"
                                       min="0" max="100" step="0.5" /> %
                                <p class="description">Added server-side to the price_calculator API call. Clients see the final price only.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="apc_currency">Currency</label></th>
                            <td>
                                <select name="apc_currency" id="apc_currency">
                                    <?php foreach ( ['USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — British Pound', 'AED' => 'AED — UAE Dirham', 'CHF' => 'CHF — Swiss Franc', 'SGD' => 'SGD — Singapore Dollar', 'CAD' => 'CAD — Canadian Dollar'] as $code => $label ) : ?>
                                        <option value="<?php echo esc_attr( $code ); ?>" <?php selected( get_option( 'apc_currency', 'USD' ), $code ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Lead Routing -->
                <div class="apc-card">
                    <h2 class="apc-card__title">📬 Lead Routing</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="apc_lead_email">Lead Email</label></th>
                            <td>
                                <input type="email" name="apc_lead_email" id="apc_lead_email"
                                       class="regular-text"
                                       value="<?php echo esc_attr( get_option( 'apc_lead_email', get_option( 'admin_email' ) ) ); ?>" />
                                <p class="description">Charter form submissions are forwarded here immediately.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Architecture note -->
                <div class="apc-card apc-card--info">
                    <h2 class="apc-card__title">🔒 Security Architecture</h2>
                    <p style="font-size:.85rem;color:#44403c;line-height:1.6">
                        All API requests flow through a <strong>secure server-side PHP proxy</strong>.
                        Your API key is stored in <code>wp_options</code> and added as
                        <code>Authorization: Token &lt;key&gt;</code> in PHP — the browser never sees it.
                        Every AJAX handler verifies a WordPress nonce and sanitises all inputs.
                        The Flight → Price chain runs entirely server-side.
                    </p>
                </div>

            </div><!-- /.apc-admin__grid -->

            <?php submit_button( 'Save Settings', 'primary large', 'submit', true ); ?>

        </form><!-- /#apc-settings-form  ← settings form ends here -->

        <!--
            FLUSH CACHE — Standalone form, OUTSIDE the settings form above.
            Nested <form> tags are invalid HTML and break form submission.
        -->
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #e7e5e4;display:flex;align-items:center;gap:.75rem">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'apc_flush_cache' ); ?>
                <input type="hidden" name="action" value="apc_flush_cache" />
                <button type="submit" class="button button-secondary">🗑 Flush API Cache</button>
            </form>
            <span class="description">Clears all cached API responses immediately.</span>
        </div>

    </div><!-- /#apc-tab-settings -->

    <!-- ════════════════════════════════════════════════════════════
         API DEBUG TAB
    ════════════════════════════════════════════════════════════ -->
    <div class="apc-tab-pane" id="apc-tab-debug" style="display:none">
        <div class="apc-card apc-card--full" style="margin-top:1.5rem">
            <h2 class="apc-card__title">🔍 Live API Response Inspector</h2>
            <p class="description" style="margin-bottom:1rem">
                Test the flight calculator API directly and see the <strong>exact raw JSON response</strong> — including all real field names.
                Enter ICAO codes for airports (e.g. <code>RPLL</code> for Manila, <code>OMDB</code> for Dubai) and an aircraft type ICAO
                (e.g. <code>HDJT</code> for HondaJet, <code>GL5T</code> for Gulfstream G500, <code>C56X</code> for Citation Excel).
            </p>

            <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem">
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6b7a99;margin-bottom:.35rem">
                        From Airport ICAO <span style="color:#b45309">*</span>
                    </label>
                    <input type="text" id="dbg-from" style="width:160px;text-transform:uppercase" placeholder="e.g. RPLL" maxlength="6" />
                    <p style="font-size:.7rem;color:#6b7a99;margin:.2rem 0 0">Use Lookup below ↓</p>
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6b7a99;margin-bottom:.35rem">
                        To Airport ICAO <span style="color:#b45309">*</span>
                    </label>
                    <input type="text" id="dbg-to" style="width:160px;text-transform:uppercase" placeholder="e.g. OMDB" maxlength="6" />
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6b7a99;margin-bottom:.35rem">
                        Aircraft Type ICAO <span style="color:#b45309">*</span>
                    </label>
                    <input type="text" id="dbg-profile" style="width:160px;text-transform:uppercase" placeholder="e.g. HDJT, GL5T, C56X" maxlength="8" />
                    <p style="font-size:.7rem;color:#6b7a99;margin:.2rem 0 0">ICAO aircraft type code</p>
                </div>
                <div>
                    <label style="display:block;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#6b7a99;margin-bottom:.35rem">
                        Date
                    </label>
                    <input type="date" id="dbg-date" style="width:160px" value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>" />
                </div>
            </div>

            <div style="display:flex;gap:.5rem;margin-bottom:1.5rem">
                <button id="dbg-run" class="button button-primary" type="button">▶ Run Flight Calculator Test</button>
                <button id="dbg-search-airports-btn" class="button" type="button">🔍 Lookup Airport IDs</button>
            </div>

            <!-- Airport search tool -->
            <div id="dbg-airport-search" style="display:none;background:#f5f5f4;border:1px solid #e7e5e4;border-radius:8px;padding:1rem;margin-bottom:1.5rem">
                <h3 style="margin:0 0 .75rem;font-size:.875rem">Airport ID Lookup</h3>
                <div style="display:flex;gap:.5rem;align-items:center">
                    <input type="text" id="dbg-airport-query" style="width:280px" placeholder="Airport name, city or ICAO…" />
                    <button class="button" id="dbg-do-airport-search" type="button">Search</button>
                </div>
                <div id="dbg-airport-results" style="margin-top:.875rem"></div>
            </div>

            <!-- Results -->
            <div id="dbg-error" style="display:none;padding:.875rem;background:#fef2f2;border:1px solid rgba(153,27,27,.2);border-radius:6px;color:#991b1b;font-size:.85rem;margin-bottom:1rem"></div>

            <div id="dbg-result" style="display:none">
                <div style="display:flex;gap:1rem;align-items:center;margin-bottom:.75rem">
                    <h3 style="margin:0;font-size:.875rem">Raw API Response (flight_calculator):</h3>
                    <span id="dbg-request-id" style="font-size:.8rem;color:#1a8a5a;font-weight:600"></span>
                </div>
                <div id="dbg-keys-box" style="background:#edf7f2;border:1px solid rgba(26,138,90,.2);border-radius:6px;padding:.875rem;margin-bottom:.75rem;font-size:.8rem">
                    <strong>Top-level response keys:</strong><br>
                    <code id="dbg-keylist" style="margin-top:.35rem;display:block;word-break:break-all"></code>
                </div>
                <pre id="dbg-json" style="background:#1c1917;color:#fcd34d;padding:1.25rem;border-radius:8px;overflow:auto;max-height:500px;font-size:.78rem;white-space:pre-wrap;line-height:1.6;margin:0"></pre>
            </div>

        </div>
    </div><!-- /#apc-tab-debug -->

    <!-- ════════════════════════════════════════════════════════════
         SHORTCODES TAB
    ════════════════════════════════════════════════════════════ -->
    <div class="apc-tab-pane" id="apc-tab-shortcodes" style="display:none">
        <div class="apc-card apc-card--full" style="margin-top:1.5rem">
            <h2 class="apc-card__title">📋 Shortcodes Reference</h2>
            <table class="widefat striped apc-sc-table">
                <thead>
                    <tr><th>Shortcode</th><th>Description</th><th>Optional attributes</th></tr>
                </thead>
                <tbody>
                    <?php
                    $shortcodes = [
                        '[aviapages_calculator]'   => [ 'Chained Flight + Price Calculator — shows map &amp; full results from AviaPages', 'title, show_price="yes|no"' ],
                        '[aviapages_aircraft]'     => [ 'Browse &amp; filter available charter jets', 'title, limit="12"' ],
                        '[aviapages_empty_legs]'   => [ 'Live empty leg deals board with filters', 'title, limit="20"' ],
                        '[aviapages_charter_form]' => [ 'Charter inquiry form → lead email + API submission', 'title' ],
                    ];
                    foreach ( $shortcodes as $sc => [ $desc, $attrs ] ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $sc ); ?></code></td>
                        <td><?php echo $desc; ?></td>
                        <td><code style="font-size:.8em;color:#666"><?php echo esc_html( $attrs ); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:1.5rem;padding:1rem;background:#fafaf9;border:1px solid #e7e5e4;border-radius:8px">
                <h3 style="margin:0 0 .5rem;font-size:.875rem">Example page setup</h3>
                <p style="font-size:.85rem;color:#44403c;margin:0;line-height:1.6">
                    Create a "Charter" page and paste all four shortcodes. The calculator links directly to the charter form
                    via "Request This Flight →". The empty legs "Enquire" buttons also link to the form.
                </p>
            </div>
        </div>
    </div><!-- /#apc-tab-shortcodes -->

</div><!-- /.wrap.apc-admin -->

<script>
(function($){
    'use strict';

    /* ── Tab switching ─────────────────────────── */
    $('.apc-admin-tab').on('click', function(){
        const tab = $(this).data('tab');
        $('.apc-admin-tab').removeClass('apc-admin-tab--active');
        $(this).addClass('apc-admin-tab--active');
        $('.apc-tab-pane').hide();
        $('#apc-tab-' + tab).show();
    });

    /* ── Airport search ─────────────────────────── */
    $('#dbg-search-airports-btn').on('click', function(){
        $('#dbg-airport-search').slideToggle(200);
    });

    $('#dbg-do-airport-search').on('click', function(){
        const q = $('#dbg-airport-query').val().trim();
        if ( q.length < 2 ) return;
        $('#dbg-airport-results').html('<em>Searching…</em>');

        $.get(ajaxurl, {
            action : 'apc_airports',
            nonce  : '<?php echo esc_js( wp_create_nonce( "apc_nonce" ) ); ?>',
            q      : q,
        }, function(res){
            if ( ! res.success || ! res.data.length ) {
                $('#dbg-airport-results').html('<em>No airports found.</em>');
                return;
            }
            var html = '<table class="widefat" style="margin-top:.5rem"><thead><tr><th>Integer ID</th><th>ICAO</th><th>IATA</th><th>Name</th><th>City</th></tr></thead><tbody>';
            $.each(res.data, function(i, ap){
                html += '<tr><td><strong style="color:#b45309">' + ap.id + '</strong></td><td>' + ap.icao + '</td><td>' + ap.iata + '</td><td>' + ap.name + '</td><td>' + ap.city + ', ' + ap.country + '</td></tr>';
            });
            html += '</tbody></table>';
            $('#dbg-airport-results').html(html);
        });
    });

    /* ── Enter key in airport search ──────────── */
    $('#dbg-airport-query').on('keydown', function(e){
        if ( e.key === 'Enter' ) { e.preventDefault(); $('#dbg-do-airport-search').trigger('click'); }
    });

    /* ── Debug: run flight calculator ─────────── */
    $('#dbg-run').on('click', function(){
        const $btn    = $(this);
        const fromId  = $('#dbg-from').val().trim();
        const toId    = $('#dbg-to').val().trim();
        const profile = $('#dbg-profile').val().trim();
        const date    = $('#dbg-date').val();

        if ( ! fromId || ! toId || ! profile ) {
            alert('Please enter From Airport ID, To Airport ID, and Aircraft Profile ID.');
            return;
        }

        $btn.prop('disabled', true).text('⏳ Calling API…');
        $('#dbg-result, #dbg-error').hide();

        $.post(ajaxurl, {
            action       : 'apc_debug_flight',
            nonce        : '<?php echo esc_js( wp_create_nonce( "apc_nonce" ) ); ?>',
            from_icao    : fromId.toUpperCase(),
            to_icao      : toId.toUpperCase(),
            aircraft_icao: profile.toUpperCase(),
            date         : date,
            pax          : 4,
        }, function(res){
            $btn.prop('disabled', false).text('▶ Run Flight Calculator Test');

            if ( res.success ) {
                const d = res.data;
                const reqId = d.request_id;

                $('#dbg-request-id').text( reqId ? '✅ request_id = ' + reqId : '⚠ No request_id found' );
                $('#dbg-keylist').text( d.response_keys ? d.response_keys.join(', ') : 'N/A' );
                $('#dbg-json').text( JSON.stringify(d.raw_flight, null, 2) );
                $('#dbg-result').show();
                $('#dbg-error').hide();
            } else {
                const msg = res.data && res.data.message ? res.data.message : JSON.stringify(res);
                $('#dbg-error').text('API Error: ' + msg).show();
                $('#dbg-result').hide();
            }
        }).fail(function(xhr){
            $btn.prop('disabled', false).text('▶ Run Flight Calculator Test');
            $('#dbg-error').text('Network error ' + xhr.status + ': ' + xhr.responseText.slice(0, 300)).show();
        });
    });

})(jQuery);
</script>
