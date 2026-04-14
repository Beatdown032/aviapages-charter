<?php defined( 'ABSPATH' ) || exit; ?>
<div class="avia-widget avia-aircraft-search" id="avia-aircraft-search">
    <div class="avia-widget__header">
        <span class="avia-widget__icon">🛩</span>
        <h3 class="avia-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
    </div>

    <div class="avia-filters">
        <div class="avia-filter-row">
            <div class="avia-field">
                <label>Aircraft Class</label>
                <select class="avia-input" id="filter-class">
                    <option value="">All Classes</option>
                </select>
            </div>
            <div class="avia-field avia-field--sm">
                <label>Min Passengers</label>
                <input type="number" class="avia-input" id="filter-pax" placeholder="e.g. 8" min="1" max="100" />
            </div>
            <button class="avia-btn avia-btn--primary" id="avia-search-jets-btn">Search Jets</button>
        </div>
    </div>

    <div class="avia-aircraft-grid" id="avia-aircraft-grid">
        <div class="avia-loading">Loading available aircraft…</div>
    </div>
    <div class="avia-error" id="avia-aircraft-error" style="display:none"></div>
</div>
