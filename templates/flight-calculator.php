<?php defined( 'ABSPATH' ) || exit; ?>
<div class="avia-widget avia-flight-calc" id="avia-flight-calc">
    <div class="avia-widget__header">
        <span class="avia-widget__icon">✈</span>
        <h3 class="avia-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
    </div>

    <div class="avia-form">
        <div class="avia-row">
            <div class="avia-field avia-field--airport">
                <label>From (Airport / City)</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="e.g. London Luton, EGGW…" data-target="from_icao" />
                    <input type="hidden" class="avia-airport-val" name="from_icao" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
            <div class="avia-swap-btn" title="Swap airports">⇄</div>
            <div class="avia-field avia-field--airport">
                <label>To (Airport / City)</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="e.g. Dubai, OMDB…" data-target="to_icao" />
                    <input type="hidden" class="avia-airport-val" name="to_icao" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
        </div>

        <div class="avia-row">
            <div class="avia-field">
                <label>Aircraft Type / Profile</label>
                <input type="text" class="avia-input" id="avia-profile-search" placeholder="Search jet type…" />
                <select name="profile_id" class="avia-input" id="avia-profile-select">
                    <option value="">— select aircraft —</option>
                </select>
            </div>
            <div class="avia-field">
                <label>Departure Date</label>
                <input type="date" name="dep_date" class="avia-input" value="<?php echo esc_attr( date('Y-m-d') ); ?>" />
            </div>
        </div>

        <button class="avia-btn avia-btn--primary avia-calc-btn" data-mode="flight">
            <span class="avia-btn-text">Calculate Route</span>
            <span class="avia-btn-loader" style="display:none">⏳ Calculating…</span>
        </button>
    </div>

    <div class="avia-result" id="avia-flight-result" style="display:none">
        <div class="avia-result__grid">
            <div class="avia-result__card">
                <div class="avia-result__icon">⏱</div>
                <div class="avia-result__label">Flight Time</div>
                <div class="avia-result__value" id="res-flight-time">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__icon">📍</div>
                <div class="avia-result__label">Distance</div>
                <div class="avia-result__value" id="res-distance">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__icon">⛽</div>
                <div class="avia-result__label">Fuel (est.)</div>
                <div class="avia-result__value" id="res-fuel">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__icon">🌬</div>
                <div class="avia-result__label">Wind Impact</div>
                <div class="avia-result__value" id="res-wind">—</div>
            </div>
        </div>
        <div class="avia-result__route" id="res-route"></div>
    </div>

    <div class="avia-error" id="avia-flight-error" style="display:none"></div>
</div>
