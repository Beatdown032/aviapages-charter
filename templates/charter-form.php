<?php defined( 'ABSPATH' ) || exit; ?>
<div class="apc-widget apc-charter-form" id="apc-charter-form" data-widget="charter-form">

    <div class="apc-widget__header">
        <div class="apc-widget__header-left">
            <svg class="apc-widget__svg-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="apc-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
        </div>
    </div>

    <div class="apc-widget__body">

        <div id="apc-charter-body">

            <fieldset class="apc-fieldset">
                <legend class="apc-fieldset__legend">Flight Details</legend>

                <div class="apc-form__row">
                    <div class="apc-form__field apc-form__field--grow">
                        <label class="apc-label">Departure Airport <span class="apc-req">*</span></label>
                        <div class="apc-ac-wrap">
                            <input class="apc-input apc-airport-input" type="text"
                                   placeholder="City or ICAO code…" data-field="req_from" autocomplete="off" />
                            <input type="hidden" class="apc-airport-code" name="req_from" />
                            <div class="apc-ac-tag" style="display:none"></div>
                            <ul class="apc-ac-list" role="listbox"></ul>
                        </div>
                    </div>

                    <div class="apc-form__field apc-form__field--grow">
                        <label class="apc-label">Destination <span class="apc-req">*</span></label>
                        <div class="apc-ac-wrap">
                            <input class="apc-input apc-airport-input" type="text"
                                   placeholder="City or ICAO code…" data-field="req_to" autocomplete="off" />
                            <input type="hidden" class="apc-airport-code" name="req_to" />
                            <div class="apc-ac-tag" style="display:none"></div>
                            <ul class="apc-ac-list" role="listbox"></ul>
                        </div>
                    </div>
                </div>

                <div class="apc-form__row">
                    <div class="apc-form__field">
                        <label class="apc-label" for="req-date">Date <span class="apc-req">*</span></label>
                        <input class="apc-input" id="req-date" type="date"
                               min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
                    </div>
                    <div class="apc-form__field apc-form__field--sm">
                        <label class="apc-label" for="req-pax">Passengers <span class="apc-req">*</span></label>
                        <input class="apc-input" id="req-pax" type="number" value="4" min="1" max="500" />
                    </div>
                    <div class="apc-form__field apc-form__field--grow">
                        <label class="apc-label" for="req-notes">Notes / Preferences</label>
                        <textarea class="apc-input apc-textarea" id="req-notes"
                                  placeholder="Aircraft preference, catering, return trip details…" rows="3"></textarea>
                    </div>
                </div>
            </fieldset>

            <fieldset class="apc-fieldset">
                <legend class="apc-fieldset__legend">Your Details</legend>
                <div class="apc-form__row">
                    <div class="apc-form__field">
                        <label class="apc-label" for="req-name">Full Name <span class="apc-req">*</span></label>
                        <input class="apc-input" id="req-name" type="text"
                               placeholder="John Smith" autocomplete="name" />
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="req-email">Email <span class="apc-req">*</span></label>
                        <input class="apc-input" id="req-email" type="email"
                               placeholder="john@example.com" autocomplete="email" />
                    </div>
                    <div class="apc-form__field">
                        <label class="apc-label" for="req-phone">Phone</label>
                        <input class="apc-input" id="req-phone" type="tel"
                               placeholder="+1 555 000 000" autocomplete="tel" />
                    </div>
                </div>
            </fieldset>

            <div class="apc-alert apc-alert--error" role="alert" hidden></div>

            <div class="apc-form__row apc-form__row--submit">
                <button class="apc-btn apc-btn--primary apc-btn--xl" id="apc-charter-submit" type="button">
                    <span class="apc-btn__text">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:.4rem"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                        Submit Charter Request
                    </span>
                    <span class="apc-btn__loading" style="display:none">
                        <span class="apc-spinner"></span>&ensp;Submitting…
                    </span>
                </button>
            </div>

        </div><!-- /#apc-charter-body -->

        <!-- Success state -->
        <div id="apc-charter-success" hidden style="text-align:center;padding:3rem 2rem">
            <div class="apc-success__icon">✈</div>
            <h3 class="apc-success__title">Request Submitted</h3>
            <p class="apc-success__text">Thank you — your charter inquiry has been received. Our team will respond within the hour with operator offers.</p>
        </div>

    </div><!-- /.apc-widget__body -->
</div>
