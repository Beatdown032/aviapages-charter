<?php defined( 'ABSPATH' ) || exit; ?>
<div class="avia-widget avia-price-calc" id="avia-price-calc">
    <div class="avia-widget__header">
        <span class="avia-widget__icon">💰</span>
        <h3 class="avia-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
    </div>

    <div class="avia-form">
        <div class="avia-row">
            <div class="avia-field avia-field--airport">
                <label>From</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="Departure airport…" data-target="price_from" />
                    <input type="hidden" class="avia-airport-val" name="price_from" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
            <div class="avia-swap-btn">⇄</div>
            <div class="avia-field avia-field--airport">
                <label>To</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="Destination airport…" data-target="price_to" />
                    <input type="hidden" class="avia-airport-val" name="price_to" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
        </div>

        <div class="avia-row">
            <div class="avia-field">
                <label>Aircraft Profile</label>
                <input type="text" class="avia-input" id="avia-price-profile-search" placeholder="Search jet…" />
                <select name="price_profile_id" class="avia-input" id="avia-price-profile-select">
                    <option value="">— select aircraft —</option>
                </select>
            </div>
            <div class="avia-field">
                <label>Date</label>
                <input type="date" name="price_date" class="avia-input" value="<?php echo esc_attr( date('Y-m-d') ); ?>" />
            </div>
            <div class="avia-field avia-field--sm">
                <label>Passengers</label>
                <input type="number" name="price_pax" class="avia-input" value="4" min="1" max="100" />
            </div>
        </div>

        <button class="avia-btn avia-btn--gold avia-calc-btn" data-mode="price">
            <span class="avia-btn-text">Get Price Estimate</span>
            <span class="avia-btn-loader" style="display:none">⏳ Calculating…</span>
        </button>
    </div>

    <div class="avia-result avia-result--price" id="avia-price-result" style="display:none">
        <div class="avia-price-hero">
            <div class="avia-price-label">Estimated Charter Price</div>
            <div class="avia-price-value" id="res-price-total">—</div>
            <div class="avia-price-note" id="res-price-note"></div>
        </div>
        <div class="avia-result__grid">
            <div class="avia-result__card">
                <div class="avia-result__label">Base Cost</div>
                <div class="avia-result__value" id="res-base-cost">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__label">Fees & Taxes</div>
                <div class="avia-result__value" id="res-fees">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__label">Commission</div>
                <div class="avia-result__value" id="res-commission">—</div>
            </div>
            <div class="avia-result__card">
                <div class="avia-result__label">Flight Time</div>
                <div class="avia-result__value" id="res-price-time">—</div>
            </div>
        </div>
        <div class="avia-cta-row">
            <a href="#avia-charter-form" class="avia-btn avia-btn--primary">Book This Flight →</a>
        </div>
    </div>

    <div class="avia-error" id="avia-price-error" style="display:none"></div>
</div>
