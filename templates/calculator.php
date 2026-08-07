<?php defined( 'ABSPATH' ) || exit;
$show_price = isset( $a['show_price'] ) ? $a['show_price'] !== 'no' : true;
?>
<div class="apc-widget apc-calc" data-widget="calculator">

    <div class="apc-widget__header">
        <div class="apc-widget__header-left">
            <svg class="apc-widget__svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 19l-7-7 1.5-1.5 5.5 2L19 5l1.5 1.5z"/></svg>
            <h3 class="apc-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        </div>
        <?php /* Price & Route / Route Only tabs hidden by design */ ?>
    </div>

    <div class="apc-widget__body">

        <!-- FORM -->
        <div class="apc-form">

            <div class="apc-form__row">
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label">Departure Airport <span class="apc-req">*</span></label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input apc-airport-input" type="text"
                               placeholder="City, airport or ICAO…" data-field="from" autocomplete="off" />
                        <input type="hidden" class="apc-airport-icao" name="from_icao" />
                        <input type="hidden" class="apc-airport-tz"   name="from_tz" />
                        <input type="hidden" class="apc-airport-lat"  name="from_lat" />
                        <input type="hidden" class="apc-airport-lng"  name="from_lng" />
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
                        <input type="hidden" class="apc-airport-tz"   name="to_tz" />
                        <input type="hidden" class="apc-airport-lat"  name="to_lat" />
                        <input type="hidden" class="apc-airport-lng"  name="to_lng" />
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
                        Local Time <span class="apc-tz-label" id="apc-tz-label"></span>
                    </label>
                    <input class="apc-input" id="apc-time" name="time" type="time" value="09:00" />
                </div>
                <div class="apc-form__field apc-form__field--sm">
                    <label class="apc-label" for="apc-pax">Passengers <span class="apc-req">*</span></label>
                    <input class="apc-input" id="apc-pax" name="pax" type="number" value="4" min="1" max="500" />
                </div>
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label" for="apc-profile-search">Aircraft Type <span class="apc-req">*</span></label>
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
                <button class="apc-btn apc-btn--primary apc-calc-submit" type="button" data-mode="price">
                    <span class="apc-btn__text">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:.3rem"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Calculate Flight
                    </span>
                    <span class="apc-btn__loading" style="display:none">
                        <span class="apc-spinner"></span>&ensp;Calculating…
                    </span>
                </button>
                <?php /* Advanced Settings button hidden by design */ ?>
            </div>

            <div class="apc-advanced-panel" style="display:none">
                <div class="apc-advanced-grid">
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-etops">ETOPS</label>
                        <select class="apc-input" id="apc-etops" name="etops">
                            <option value="">Disabled</option>
                            <option value="1">Enabled</option>
                        </select>
                        <p class="apc-field-hint">Extended-range twin-engine ops</p>
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-payload">Custom Payload (kg)</label>
                        <input class="apc-input" id="apc-payload" name="payload_kg" type="number" placeholder="e.g. 500" min="0" />
                        <p class="apc-field-hint">Additional cargo weight</p>
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="apc-extrafuel">Extra Fuel (kg)</label>
                        <input class="apc-input" id="apc-extrafuel" name="extra_fuel_kg" type="number" placeholder="e.g. 200" min="0" />
                        <p class="apc-field-hint">Reserve or ferry fuel</p>
                    </div>
                </div>
            </div>

        </div><!-- /.apc-form -->

        <div class="apc-alert apc-alert--error" style="display:none"></div>

        <!-- ═══════════════════════════════════════════
             RESULTS PANEL
             All IDs must match what renderResults() in
             charter.js writes to. Nested API structure:
               f.time  → flight times & distances
               f.fuel  → fuel totals & detail segments
               f.airport → airport codes
               f.aircraft → aircraft name
        ═══════════════════════════════════════════ -->
        <div class="apc-results-panel" id="apc-results-panel" style="display:none">

            <!-- Route header bar -->
            <div class="apc-results-header">
                <div class="apc-results-header__route">
                    <span class="apc-results-header__icao" id="res-from">—</span>
                    <span class="apc-results-header__arrow">→</span>
                    <span class="apc-results-header__icao" id="res-to">—</span>
                </div>
                <div class="apc-results-header__meta">
                    <span id="res-aircraft-label" style="font-weight:500"></span>
                    <span id="res-date-label" style="color:rgba(255,255,255,.5);font-size:.8rem"></span>
                </div>
                <a id="res-aviapages-link" href="#" target="_blank" rel="noopener"
                   class="apc-btn apc-btn--gold" style="font-size:.72rem;padding:.4rem .875rem;display:none">
                    Full Results on AviaPages ↗
                </a>
            </div>

            <!-- Map — lazy-loads Leaflet via CDN, draws great-circle route arc -->
            <div id="res-map-wrap" style="display:none;border-bottom:1px solid var(--apc-rule)">
                <div id="res-map" style="width:100%;height:340px;min-height:340px;background:#dce9f5;position:relative;z-index:0"></div>
            </div>

            <!-- 4 stat cards -->
            <div class="apc-stats-grid" style="padding:1.25rem 1.75rem">

                <div class="apc-stat">
                    <div class="apc-stat__icon">⏱</div>
                    <div class="apc-stat__label">Flight Time <small style="font-weight:400">(wind-adjusted)</small></div>
                    <div class="apc-stat__value" id="res-flight-time">—</div>
                    <div class="apc-stat__sub" id="res-airway-time"></div>
                </div>

                <div class="apc-stat">
                    <div class="apc-stat__icon">📍</div>
                    <div class="apc-stat__label">Airway Distance</div>
                    <div class="apc-stat__value" id="res-distance">—</div>
                    <div class="apc-stat__sub" id="res-gc-distance"></div>
                </div>

                <div class="apc-stat">
                    <div class="apc-stat__icon">⛽</div>
                    <div class="apc-stat__label">Est. Fuel <small style="font-weight:400">(wind-adjusted)</small></div>
                    <div class="apc-stat__value" id="res-fuel">—</div>
                    <div class="apc-stat__sub" id="res-fuel-sub"></div>
                </div>

                <div class="apc-stat">
                    <div class="apc-stat__icon">🌬</div>
                    <div class="apc-stat__label">Wind Impact</div>
                    <div class="apc-stat__value" id="res-wind">—</div>
                    <div class="apc-stat__sub" id="res-wind-sub"></div>
                </div>

            </div>

            <!-- Fuel detail breakdown toggle -->
            <div style="padding:0 1.75rem .875rem;display:flex;align-items:center;gap:.75rem">
                <button id="res-fuel-toggle" class="apc-btn apc-btn--secondary"
                        type="button" style="font-size:.75rem;padding:.4rem .875rem;display:none">
                    ▼ Show Fuel Breakdown by Phase
                </button>
            </div>

            <div id="res-fuel-detail" style="display:none;padding:0 1.75rem 1.25rem">
                <div class="apc-results__section-title" style="margin-bottom:.75rem">⛽ Fuel by Flight Phase</div>
                <div class="apc-fuel-table" id="res-fuel-table"></div>
            </div>

            <!-- Tech stops -->
            <div id="res-stops" style="display:none;padding:.875rem 1.75rem;border-top:1px solid var(--apc-rule)">
                <span class="apc-label-small">Technical Stops Required:</span>
                <span id="res-stops-list" style="font-size:.85rem;margin-left:.5rem;color:var(--apc-error)"></span>
            </div>

            <!-- Price section -->
            <?php if ( $show_price ) : ?>
            <div class="apc-results__section apc-results__section--price" id="res-price-section" style="display:none">
                <div class="apc-price-hero">
                    <div class="apc-price-hero__label">Estimated Charter Price</div>
                    <div class="apc-price-hero__value" id="res-price-total">—</div>
                    <div class="apc-price-hero__note">Includes operator fees &amp; your commission</div>
                </div>
                <div class="apc-price-breakdown">
                    <div class="apc-breakdown-row"><span>Base aircraft cost</span><span id="res-base-price">—</span></div>
                    <div class="apc-breakdown-row"><span>Airport &amp; handling fees</span><span id="res-fees">—</span></div>
                    <div class="apc-breakdown-row"><span>Overflight &amp; taxes</span><span id="res-taxes">—</span></div>
                    <div class="apc-breakdown-row apc-breakdown-row--total"><span>Total (incl. commission)</span><span id="res-total-final">—</span></div>
                </div>
                <div class="apc-price-cta">
                    <a href="#apc-charter-form" class="apc-btn apc-btn--primary">Request This Charter Flight →</a>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /#apc-results-panel -->

    </div><!-- /.apc-widget__body -->
</div>
