<?php defined( 'ABSPATH' ) || exit;
$show_price = isset( $a['show_price'] ) ? $a['show_price'] !== 'no' : true;
$api_key    = get_option( 'apc_api_key', '' );
?>
<div class="apc-widget apc-calc" data-widget="calculator">

    <div class="apc-widget__header">
        <div class="apc-widget__header-left">
            <svg class="apc-widget__svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 19l-7-7 1.5-1.5 5.5 2L19 5l1.5 1.5z"/></svg>
            <h3 class="apc-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem">
            <a href="https://aviapages.com/charter-flight-calculator/" target="_blank" rel="noopener"
               class="apc-btn apc-btn--secondary" style="font-size:.72rem;padding:.35rem .875rem">
                ↗ Open Full Calculator
            </a>
        </div>
    </div>

    <div class="apc-widget__body" style="padding:0">

        <!-- ═══════════════════════════════════════════
             QUICK FORM — collects inputs, then redirects
             to AviaPages calculator with pre-filled data
             via URL parameters (their calculator reads them)
        ═══════════════════════════════════════════ -->
        <div class="apc-form" style="padding:1.5rem 1.75rem" id="apc-calc-form">

            <div class="apc-form__row">
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label">Departure Airport <span class="apc-req">*</span></label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input apc-airport-input" type="text"
                               placeholder="City, airport or ICAO…" data-field="from" autocomplete="off" />
                        <input type="hidden" class="apc-airport-icao" name="from_icao" />
                        <input type="hidden" class="apc-airport-name-full" name="from_name" />
                        <input type="hidden" class="apc-airport-tz"   name="from_tz" />
                        <div class="apc-ac-tag" style="display:none"></div>
                        <ul class="apc-ac-list" role="listbox"></ul>
                    </div>
                </div>
                <button class="apc-swap" type="button" title="Swap airports">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                </button>
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label">Destination Airport <span class="apc-req">*</span></label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input apc-airport-input" type="text"
                               placeholder="City, airport or ICAO…" data-field="to" autocomplete="off" />
                        <input type="hidden" class="apc-airport-icao" name="to_icao" />
                        <input type="hidden" class="apc-airport-name-full" name="to_name" />
                        <input type="hidden" class="apc-airport-tz"   name="to_tz" />
                        <div class="apc-ac-tag" style="display:none"></div>
                        <ul class="apc-ac-list" role="listbox"></ul>
                    </div>
                </div>
            </div>

            <div class="apc-form__row">
                <div class="apc-form__field">
                    <label class="apc-label" for="apc-date">Date <span class="apc-req">*</span></label>
                    <input class="apc-input" id="apc-date" name="date" type="date"
                           value="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
                           min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
                </div>
                <div class="apc-form__field apc-form__field--sm">
                    <label class="apc-label" for="apc-time">
                        Local Time
                        <span class="apc-tz-label" id="apc-tz-label"></span>
                    </label>
                    <input class="apc-input" id="apc-time" name="time" type="time" value="09:00" />
                </div>
                <div class="apc-form__field apc-form__field--sm">
                    <label class="apc-label" for="apc-pax">Passengers <span class="apc-req">*</span></label>
                    <input class="apc-input" id="apc-pax" name="pax" type="number" value="4" min="1" max="500" />
                </div>
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label" for="apc-profile-search">Aircraft Type</label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input" id="apc-profile-search" type="text"
                               placeholder="Search jet type…" autocomplete="off" />
                        <ul class="apc-ac-list apc-ac-list--profiles" role="listbox"></ul>
                    </div>
                    <input type="hidden" id="apc-aircraft-icao" name="aircraft_icao" />
                    <input type="hidden" id="apc-aircraft-name" name="aircraft_name" />
                </div>
            </div>

            <div class="apc-form__row apc-form__row--submit">
                <button class="apc-btn apc-btn--primary apc-calc-submit" type="button">
                    <span class="apc-btn__text">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:.3rem"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Calculate Flight &amp; Price
                    </span>
                    <span class="apc-btn__loading" style="display:none">
                        <span class="apc-spinner"></span>&ensp;Loading…
                    </span>
                </button>
                <button class="apc-btn apc-btn--secondary apc-advanced-toggle" type="button">⚙ Advanced</button>
            </div>

            <!-- Advanced panel -->
            <div class="apc-advanced-panel" style="display:none">
                <div class="apc-advanced-grid">
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-etops">ETOPS</label>
                        <select class="apc-input" id="apc-etops" name="etops">
                            <option value="">Disabled</option>
                            <option value="1">Enabled</option>
                        </select>
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-payload">Custom Payload (kg)</label>
                        <input class="apc-input" id="apc-payload" name="payload_kg" type="number" placeholder="e.g. 500" min="0" />
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-extrafuel">Extra Fuel (kg)</label>
                        <input class="apc-input" id="apc-extrafuel" name="extra_fuel_kg" type="number" placeholder="e.g. 200" min="0" />
                    </div>
                </div>
            </div>

        </div><!-- /.apc-form -->

        <!-- Error alert -->
        <div class="apc-alert apc-alert--error" style="display:none;margin:0 1.75rem 1rem"></div>

        <!-- ═══════════════════════════════════════════
             RESULTS: Embedded AviaPages calculator
             The iframe loads the full calculator at
             aviapages.com with our pre-filled values.
             This gives users the complete experience:
             map, route, fuel table, price breakdown.
        ═══════════════════════════════════════════ -->
        <div id="apc-iframe-section" style="display:none">

            <!-- Thin info bar above iframe -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;padding:.875rem 1.75rem;background:#292524;border-top:2px solid #b45309">
                <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap">
                    <span style="font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.45)">Route</span>
                    <span id="apc-route-label" style="font-family:'DM Serif Display',serif;font-size:1.1rem;color:#fcd34d">—</span>
                    <span id="apc-date-label" style="font-size:.8rem;color:rgba(255,255,255,.55)"></span>
                    <span id="apc-pax-label" style="font-size:.8rem;color:rgba(255,255,255,.55)"></span>
                </div>
                <div style="display:flex;align-items:center;gap:.75rem">
                    <a id="apc-open-tab" href="#" target="_blank" rel="noopener"
                       class="apc-btn apc-btn--gold" style="font-size:.75rem;padding:.4rem 1rem">
                        ↗ Open in New Tab
                    </a>
                    <button id="apc-back-btn" class="apc-btn apc-btn--secondary" type="button"
                            style="font-size:.75rem;padding:.4rem 1rem">
                        ← Edit Form
                    </button>
                </div>
            </div>

            <!-- The iframe — loads AviaPages calculator with inputs pre-filled -->
            <div style="position:relative;min-height:200px">
                <div id="apc-iframe-loading"
                     style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#fff;z-index:2;padding:2rem;font-family:'DM Sans',sans-serif;color:#78716c;font-size:.9rem">
                    <span class="apc-spinner" style="margin-right:.6rem"></span>
                    Loading AviaPages calculator…
                </div>
                <iframe id="apc-results-iframe"
                        src=""
                        style="width:100%;height:900px;border:none;display:block;background:#fff"
                        allowfullscreen
                        title="AviaPages Flight Calculator">
                </iframe>
            </div>

            <?php if ( $show_price ) : ?>
            <div style="text-align:center;padding:1.25rem;border-top:1px solid #e7e5e4;background:#fafaf9">
                <a href="#apc-charter-form" class="apc-btn apc-btn--primary">Request This Charter Flight →</a>
            </div>
            <?php endif; ?>

        </div><!-- /#apc-iframe-section -->

    </div><!-- /.apc-widget__body -->
</div>
