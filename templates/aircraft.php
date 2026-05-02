<?php defined( 'ABSPATH' ) || exit; ?>
<div class="apc-widget apc-aircraft" data-widget="aircraft">

    <div class="apc-widget__header">
        <div class="apc-widget__header-left">
            <svg class="apc-widget__svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 00-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5L21 16z"/></svg>
            <h3 class="apc-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        </div>
    </div>

    <div class="apc-widget__body">
        <!-- Filters -->
        <div class="apc-filters">
            <div class="apc-form__row">
                <div class="apc-form__field">
                    <label class="apc-label" for="jet-class">Aircraft Class</label>
                    <select class="apc-input" id="jet-class">
                        <option value="">All Classes</option>
                    </select>
                </div>
                <div class="apc-form__field apc-form__field--sm">
                    <label class="apc-label" for="jet-pax">Min Passengers</label>
                    <input class="apc-input" id="jet-pax" type="number" placeholder="e.g. 8" min="1" max="200" />
                </div>
                <div class="apc-form__field apc-form__field--grow">
                    <label class="apc-label" for="jet-name">Search Name</label>
                    <input class="apc-input" id="jet-name" type="text" placeholder="e.g. Gulfstream, Citation…" />
                </div>
                <div class="apc-form__field apc-form__field--btn">
                    <label class="apc-label">&nbsp;</label>
                    <button class="apc-btn apc-btn--primary" id="jet-search-btn" type="button">Search Jets</button>
                </div>
            </div>
        </div>

        <div class="apc-alert apc-alert--error" role="alert" hidden></div>

        <div class="apc-jet-grid" id="apc-jet-grid">
            <div class="apc-loading">
                <span class="apc-spinner"></span> Loading available jets…
            </div>
        </div>
    </div>

</div>
