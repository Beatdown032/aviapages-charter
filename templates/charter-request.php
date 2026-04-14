<?php defined( 'ABSPATH' ) || exit; ?>
<div class="avia-widget avia-charter-form" id="avia-charter-form">
    <div class="avia-widget__header">
        <span class="avia-widget__icon">📋</span>
        <h3 class="avia-widget__title"><?php echo esc_html( $a['title'] ); ?></h3>
    </div>

    <div class="avia-form" id="charter-request-form">
        <div class="avia-form-section">
            <h4 class="avia-section-label">Flight Details</h4>
            <div class="avia-row">
                <div class="avia-field avia-field--airport">
                    <label>Departure Airport <span class="req">*</span></label>
                    <div class="avia-autocomplete-wrap">
                        <input type="text" class="avia-input avia-airport-input" placeholder="City or ICAO…" data-target="req_from" />
                        <input type="hidden" class="avia-airport-val" name="req_from" id="req_from" />
                        <ul class="avia-autocomplete-list"></ul>
                    </div>
                </div>
                <div class="avia-field avia-field--airport">
                    <label>Destination <span class="req">*</span></label>
                    <div class="avia-autocomplete-wrap">
                        <input type="text" class="avia-input avia-airport-input" placeholder="City or ICAO…" data-target="req_to" />
                        <input type="hidden" class="avia-airport-val" name="req_to" id="req_to" />
                        <ul class="avia-autocomplete-list"></ul>
                    </div>
                </div>
            </div>
            <div class="avia-row">
                <div class="avia-field">
                    <label>Departure Date <span class="req">*</span></label>
                    <input type="date" class="avia-input" id="req_date" name="req_date" min="<?php echo esc_attr( date('Y-m-d') ); ?>" />
                </div>
                <div class="avia-field avia-field--sm">
                    <label>Passengers <span class="req">*</span></label>
                    <input type="number" class="avia-input" id="req_pax" name="req_pax" value="4" min="1" max="200" />
                </div>
            </div>
        </div>

        <div class="avia-form-section">
            <h4 class="avia-section-label">Your Details</h4>
            <div class="avia-row">
                <div class="avia-field">
                    <label>Full Name <span class="req">*</span></label>
                    <input type="text" class="avia-input" id="req_name" placeholder="John Smith" />
                </div>
                <div class="avia-field">
                    <label>Email Address <span class="req">*</span></label>
                    <input type="email" class="avia-input" id="req_email" placeholder="john@example.com" />
                </div>
            </div>
            <div class="avia-row">
                <div class="avia-field">
                    <label>Phone Number</label>
                    <input type="tel" class="avia-input" id="req_phone" placeholder="+1 555 000 000" />
                </div>
                <div class="avia-field">
                    <label>Special Requests / Notes</label>
                    <textarea class="avia-input avia-textarea" id="req_notes" placeholder="Catering preferences, pet-friendly aircraft, etc."></textarea>
                </div>
            </div>
        </div>

        <div class="avia-submit-row">
            <button class="avia-btn avia-btn--primary avia-btn--xl" id="avia-submit-request">
                <span class="avia-btn-text">✈ Submit Charter Request</span>
                <span class="avia-btn-loader" style="display:none">⏳ Sending…</span>
            </button>
        </div>
    </div>

    <div class="avia-success" id="avia-request-success" style="display:none">
        <div class="avia-success__icon">✅</div>
        <h3>Request Submitted!</h3>
        <p>Thank you. Our team will review your request and get back to you within the hour.</p>
    </div>

    <div class="avia-error" id="avia-request-error" style="display:none"></div>
</div>
