<?php defined( 'ABSPATH' ) || exit; ?>
<div class="avia-widget avia-empty-legs" id="avia-empty-legs">
    <div class="avia-widget__header">
        <span class="avia-widget__icon">🏷</span>
        <h3 class="avia-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        <span class="avia-badge avia-badge--live">LIVE</span>
    </div>

    <div class="avia-filters">
        <div class="avia-filter-row">
            <div class="avia-field avia-field--airport">
                <label>From (optional)</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="Any departure…" data-target="el_from" />
                    <input type="hidden" class="avia-airport-val" name="el_from" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
            <div class="avia-field avia-field--airport">
                <label>To (optional)</label>
                <div class="avia-autocomplete-wrap">
                    <input type="text" class="avia-input avia-airport-input" placeholder="Any destination…" data-target="el_to" />
                    <input type="hidden" class="avia-airport-val" name="el_to" />
                    <ul class="avia-autocomplete-list"></ul>
                </div>
            </div>
            <div class="avia-field">
                <label>From Date</label>
                <input type="date" class="avia-input" id="el-date" value="<?php echo esc_attr( date('Y-m-d') ); ?>" />
            </div>
            <button class="avia-btn avia-btn--primary" id="avia-el-search-btn">Search Deals</button>
        </div>
    </div>

    <div class="avia-el-grid" id="avia-el-grid">
        <div class="avia-loading">Loading empty legs…</div>
    </div>

    <div class="avia-error" id="avia-el-error" style="display:none"></div>
</div>
