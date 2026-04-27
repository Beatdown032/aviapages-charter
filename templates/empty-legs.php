<?php defined( 'ABSPATH' ) || exit; ?>
<div class="apc-widget apc-el" data-widget="empty-legs">

    <div class="apc-widget__header">
        <div class="apc-widget__header-left">
            <svg class="apc-widget__svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 7h10M7 12h10M7 17h6"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            <h3 class="apc-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        </div>
        <span class="apc-live-badge">
            <span class="apc-live-dot"></span> LIVE
        </span>
    </div>

    <div class="apc-widget__body">

        <!-- Filters -->
        <div class="apc-filters">
            <div class="apc-form__row">
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label">From (optional)</label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input apc-airport-input" type="text"
                               placeholder="Any departure…" data-field="el_from" autocomplete="off" />
                        <input type="hidden" class="apc-airport-code" name="el_from" />
                        <ul class="apc-ac-list" role="listbox"></ul>
                    </div>
                </div>
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label">To (optional)</label>
                    <div class="apc-ac-wrap">
                        <input class="apc-input apc-airport-input" type="text"
                               placeholder="Any destination…" data-field="el_to" autocomplete="off" />
                        <input type="hidden" class="apc-airport-code" name="el_to" />
                        <ul class="apc-ac-list" role="listbox"></ul>
                    </div>
                </div>
                <div class="apc-form__field">
                    <label class="apc-label" for="el-date">From Date</label>
                    <input class="apc-input" id="el-date" type="date"
                           value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
                </div>
                <div class="apc-form__field apc-form__field--btn">
                    <label class="apc-label">&nbsp;</label>
                    <button class="apc-btn apc-btn--primary" id="el-search-btn" type="button">Search Deals</button>
                </div>
            </div>
        </div>

        <div class="apc-alert apc-alert--error" role="alert" hidden></div>

        <div class="apc-el-list" id="apc-el-list">
            <div class="apc-loading">
                <span class="apc-spinner"></span> Loading live empty legs…
            </div>
        </div>

    </div>
</div>
