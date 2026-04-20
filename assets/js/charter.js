/**
 * AviaPages Charter Suite v2 — Frontend JS
 *
 * Calculator approach (confirmed from API debug 2026-04-18):
 * The free API tier validates inputs but doesn't return full flight data.
 * Full results (map, route, fuel, price) require the AviaPages web calculator.
 *
 * Strategy:
 *  1. User fills our styled form (airport autocomplete, aircraft, date, time, pax)
 *  2. We build the AviaPages calculator URL with inputs as query parameters
 *  3. Load their full calculator in an iframe — user gets the complete experience
 *     including map, route waypoints, fuel table, and price breakdown
 *  4. "Open in New Tab" button lets them see it full-screen on AviaPages.com
 */
(function ($) {
    'use strict';

    const CFG = {
        ajaxUrl:     (typeof APC !== 'undefined') ? APC.ajaxUrl    : '',
        nonce:       (typeof APC !== 'undefined') ? APC.nonce      : '',
        currency:    (typeof APC !== 'undefined') ? APC.currency   : 'USD',
        calcBase:    'https://aviapages.com/charter-flight-calculator/',
        legacyBase:  'https://aviapages.com/flight_route_calculator/',
    };

    /* ─── Utilities ──────────────────────────────── */

    function esc(s) { return $('<div>').text(String(s ?? '')).html(); }

    function showAlert($w, msg) {
        const $el = $w.find('.apc-alert--error').first();
        $el.text(msg).show();
        clearTimeout($el.data('_t'));
        $el.data('_t', setTimeout(() => $el.fadeOut(400), 12000));
    }

    function hideAlert($w) { $w.find('.apc-alert--error').hide(); }

    function setBusy($btn, on) {
        $btn.prop('disabled', on);
        $btn.find('.apc-btn__text').toggle(!on);
        $btn.find('.apc-btn__loading').toggle(on);
    }

    /* ─── AJAX ───────────────────────────────────── */

    function ajaxCall(method, action, data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url:    CFG.ajaxUrl,
                method: method,
                data:   Object.assign({ action, nonce: CFG.nonce }, data || {}),
            })
            .done(res => {
                if (res && res.success) resolve(res.data);
                else reject((res && res.data && res.data.message) ? res.data.message : 'An error occurred.');
            })
            .fail((xhr, status) => {
                console.error('[APC]', action, status, xhr.status);
                reject('Network error — please check your connection.');
            });
        });
    }

    const apiGet  = (action, data) => ajaxCall('GET',  action, data);
    const apiPost = (action, data) => ajaxCall('POST', action, data);

    /* ─── Airport autocomplete ───────────────────── */

    const acTimers = {};

    $(document).on('input', '.apc-airport-input', function () {
        const $inp  = $(this);
        const key   = $inp.data('field') || 'ac_' + Math.random();
        const q     = $inp.val().trim();
        const $wrap = $inp.closest('.apc-ac-wrap');
        const $list = $wrap.find('.apc-ac-list').not('.apc-ac-list--profiles');

        $wrap.find('.apc-airport-icao').val('');
        $wrap.find('.apc-airport-name-full').val('');
        $wrap.find('.apc-airport-tz').val('');
        $wrap.find('.apc-ac-tag').text('').hide();

        if (q.length < 2) { $list.empty().hide(); return; }

        clearTimeout(acTimers[key]);
        acTimers[key] = setTimeout(async () => {
            try {
                const list = await apiGet('apc_airports', { q });
                $list.empty();
                if (!Array.isArray(list) || !list.length) { $list.hide(); return; }
                list.forEach(ap => {
                    const code = ap.icao || ap.iata || '';
                    $('<li>').attr({ role:'option', 'data-type':'airport' })
                        .html('<strong>' + esc(code) + '</strong> ' + esc(ap.name) + ' <small>— ' + esc(ap.city) + ', ' + esc(ap.country) + '</small>')
                        .data('ap', ap)
                        .appendTo($list);
                });
                $list.show();
            } catch(_) { $list.hide(); }
        }, 260);
    });

    $(document).on('click', '.apc-ac-list:not(.apc-ac-list--profiles) li[data-type="airport"]', function (e) {
        e.stopPropagation();
        const ap    = $(this).data('ap');
        const $wrap = $(this).closest('.apc-ac-wrap');
        const code  = ap.icao || ap.iata || '';

        $wrap.find('.apc-airport-input').val(ap.name + ' (' + code + ')');
        $wrap.find('.apc-airport-icao').val(code);
        $wrap.find('.apc-airport-name-full').val(ap.name);
        $wrap.find('.apc-airport-tz').val(ap.time_shift || '');
        $wrap.find('.apc-ac-tag').text(code).show();
        $(this).closest('.apc-ac-list').empty().hide();

        // Show timezone on local time label if departure
        if ($wrap.find('.apc-airport-input').data('field') === 'from') {
            $('#apc-tz-label').text(ap.time_shift ? '(' + ap.time_shift + ')' : '');
        }
    });

    /* ─── Aircraft profile autocomplete ─────────── */

    let _profiles = null;

    async function getProfiles() {
        if (_profiles) return _profiles;
        try {
            const res = await apiGet('apc_aircraft_profiles', {});
            _profiles = (res.results || []).filter(p => p.aircraft_type_icao);
        } catch(e) { _profiles = []; }
        return _profiles;
    }

    $(document).on('focus input', '#apc-profile-search', async function () {
        const $inp  = $(this);
        const q     = $inp.val().trim().toLowerCase();
        const $list = $inp.closest('.apc-ac-wrap').find('.apc-ac-list--profiles');
        const all   = await getProfiles();

        const show = q
            ? all.filter(p =>
                (p.aircraft_type_name || '').toLowerCase().includes(q) ||
                (p.name || '').toLowerCase().includes(q) ||
                (p.aircraft_class_name || '').toLowerCase().includes(q) ||
                (p.aircraft_type_icao || '').toLowerCase().includes(q))
            : all.slice(0, 30);

        $list.empty();
        if (!show.length) { $list.hide(); return; }
        show.slice(0, 30).forEach(p => {
            $('<li>').attr({ role:'option', 'data-type':'profile' })
                .html('<strong>' + esc(p.aircraft_type_name || p.name) + '</strong>&ensp;<small>' + esc(p.aircraft_class_name || '') + '</small>&ensp;<code style="font-size:.7rem;color:#b45309">' + esc(p.aircraft_type_icao || '') + '</code>')
                .data('prof', p)
                .appendTo($list);
        });
        $list.show();
    });

    $(document).on('click', '.apc-ac-list--profiles li[data-type="profile"]', function (e) {
        e.stopPropagation();
        const p = $(this).data('prof');
        $(this).closest('.apc-ac-wrap').find('#apc-profile-search').val(p.aircraft_type_name || p.name);
        $(this).closest('.apc-widget').find('#apc-aircraft-icao').val(p.aircraft_type_icao || '');
        $(this).closest('.apc-widget').find('#apc-aircraft-name').val(p.aircraft_type_name || p.name || '');
        $(this).closest('.apc-ac-list--profiles').empty().hide();
    });

    $(document).on('input', '#apc-profile-search', function () {
        if (!$(this).val().trim()) {
            $(this).closest('.apc-widget').find('#apc-aircraft-icao, #apc-aircraft-name').val('');
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.apc-ac-wrap').length) $('.apc-ac-list').empty().hide();
    });

    /* ─── Swap airports ──────────────────────────── */

    $(document).on('click', '.apc-swap', function () {
        const $row = $(this).closest('.apc-form__row');

        const swap = (sel) => {
            const $a = $row.find(sel).eq(0), $b = $row.find(sel).eq(1);
            if (!$a.length || !$b.length) return;
            const v = $a.val(); $a.val($b.val()); $b.val(v);
        };

        swap('.apc-airport-input');
        swap('.apc-airport-icao');
        swap('.apc-airport-name-full');
        swap('.apc-airport-tz');

        const $tags = $row.find('.apc-ac-tag');
        const t0 = $tags.eq(0).text(), t1 = $tags.eq(1).text();
        const s0 = $tags.eq(0).is(':visible'), s1 = $tags.eq(1).is(':visible');
        $tags.eq(0).text(t1); s1 ? $tags.eq(0).show() : $tags.eq(0).hide();
        $tags.eq(1).text(t0); s0 ? $tags.eq(1).show() : $tags.eq(1).hide();

        const newTz = $row.find('.apc-airport-tz').eq(0).val();
        $('#apc-tz-label').text(newTz ? '(' + newTz + ')' : '');
    });

    /* ─── Advanced toggle ────────────────────────── */

    $(document).on('click', '.apc-advanced-toggle', function () {
        const $p = $(this).closest('.apc-form').find('.apc-advanced-panel');
        $p.slideToggle(200);
        $(this).text($p.is(':visible') ? '✕ Close Advanced' : '⚙ Advanced');
    });

    /* ═══════════════════════════════════════════════
     *  CALCULATOR SUBMIT
     *
     *  Builds AviaPages calculator URL with all inputs
     *  as query parameters, then loads it in an iframe.
     *  This gives the FULL calculation experience:
     *  map, route, fuel data, price breakdown.
     * ═══════════════════════════════════════════════ */

    $(document).on('click', '.apc-calc-submit', function () {
        const $btn    = $(this);
        const $widget = $btn.closest('.apc-widget');

        hideAlert($widget);

        const fromIcao  = $widget.find('[name="from_icao"]').val()  || '';
        const toIcao    = $widget.find('[name="to_icao"]').val()    || '';
        const fromName  = $widget.find('[name="from_name"]').val()  || fromIcao;
        const toName    = $widget.find('[name="to_name"]').val()    || toIcao;
        const date      = $widget.find('[name="date"]').val()       || '';
        const time      = $widget.find('[name="time"]').val()       || '09:00';
        const pax       = $widget.find('[name="pax"]').val()        || '4';
        const acIcao    = $widget.find('#apc-aircraft-icao').val()  || '';
        const acName    = $widget.find('#apc-aircraft-name').val()  || '';

        // Validation
        if (!fromIcao) { showAlert($widget, 'Please select a departure airport from the suggestions.'); return; }
        if (!toIcao)   { showAlert($widget, 'Please select a destination airport from the suggestions.'); return; }
        if (fromIcao === toIcao) { showAlert($widget, 'Departure and destination airports cannot be the same.'); return; }
        if (!date)     { showAlert($widget, 'Please select a departure date.'); return; }

        setBusy($btn, true);

        // Build AviaPages calculator URL
        // Their calculator reads pre-fill params from the URL
        const params = new URLSearchParams({
            departure: fromIcao,
            arrival:   toIcao,
            date:      date,
            time:      time,
            pax:       pax,
        });
        if (acIcao) params.set('aircraft', acIcao);

        const calcUrl  = CFG.calcBase + '?' + params.toString();
        const legacyUrl = CFG.legacyBase + '?dep=' + fromIcao + '&arr=' + toIcao + '&pax=' + pax;

        // Update info bar
        $('#apc-route-label').text(fromIcao + ' → ' + toIcao);
        $('#apc-date-label').text(date + ' ' + time);
        $('#apc-pax-label').text(pax + ' pax' + (acName ? ' · ' + acName : ''));
        $('#apc-open-tab').attr('href', calcUrl);

        // Show iframe section, hide form
        const $iframeSection = $('#apc-iframe-section');
        const $iframe        = $('#apc-results-iframe');
        const $loading       = $('#apc-iframe-loading');

        $iframeSection.show();
        $loading.show();
        $iframe.hide();

        // Load calculator in iframe
        $iframe.off('load').on('load', function () {
            $loading.hide();
            $iframe.show();
            setBusy($btn, false);
        });

        // If iframe fails to load within 15s, hide spinner anyway
        const loadTimeout = setTimeout(() => {
            $loading.hide();
            $iframe.show();
            setBusy($btn, false);
        }, 15000);

        $iframe.on('load', () => clearTimeout(loadTimeout));
        $iframe.attr('src', calcUrl);

        // Smooth scroll to iframe
        $('html,body').animate({
            scrollTop: $iframeSection.offset().top - 80
        }, 400);
    });

    /* ─── Back button ────────────────────────────── */

    $(document).on('click', '#apc-back-btn', function () {
        $('#apc-iframe-section').hide();
        $('#apc-results-iframe').attr('src', '');
        $('html,body').animate({ scrollTop: $('.apc-calc').offset().top - 80 }, 300);
    });

    /* ─── Aircraft search widget ─────────────────── */

    async function initAircraftClasses() {
        const $sel = $('#jet-class');
        if (!$sel.length || $sel.find('option').length > 1) return;
        try {
            const res = await apiPost('apc_aircraft_classes', {});
            (res.results || []).forEach(c => $('<option>').val(c.aircraft_class_id).text(c.name).appendTo($sel));
        } catch(_) {}
    }

    async function searchJets() {
        const $grid = $('#apc-jet-grid');
        if (!$grid.length) return;
        $grid.html('<div class="apc-loading"><span class="apc-spinner"></span>&ensp;Searching jets…</div>');
        try {
            const res  = await apiPost('apc_aircraft_search', {
                class_id: $('#jet-class').val() || '',
                pax_min:  $('#jet-pax').val()   || '',
                search:   $('#jet-name').val()  || '',
            });
            const jets = res.results || [];
            if (!jets.length) { $grid.html('<div class="apc-loading">No aircraft match your filters.</div>'); return; }
            $grid.empty();
            jets.forEach(j => {
                const name  = j.aircraft_type_name || j.name || 'Aircraft';
                const cls   = j.aircraft_class_name || '';
                const pax   = j.pax_maximum || '—';
                const range = j.range_maximum ? Math.round(j.range_maximum).toLocaleString() + ' nm' : '—';
                const speed = j.cruise_speed_max ? Math.round(j.cruise_speed_max) + ' kts' : '—';
                $grid.append(
                    '<div class="apc-jet-card">' +
                    '<div class="apc-jet-card__head"><div class="apc-jet-card__name">' + esc(name) + '</div>' +
                    (cls ? '<span class="apc-jet-card__class">' + esc(cls) + '</span>' : '') +
                    '</div><div class="apc-jet-card__body"><div class="apc-jet-specs">' +
                    '<div class="apc-jet-spec"><span class="apc-jet-spec__label">Pax</span><span class="apc-jet-spec__value">' + esc(String(pax)) + '</span></div>' +
                    '<div class="apc-jet-spec"><span class="apc-jet-spec__label">Range</span><span class="apc-jet-spec__value">' + esc(range) + '</span></div>' +
                    '<div class="apc-jet-spec"><span class="apc-jet-spec__label">Speed</span><span class="apc-jet-spec__value">' + esc(speed) + '</span></div>' +
                    '</div></div><div class="apc-jet-card__foot">' +
                    '<a href="#apc-charter-form" class="apc-btn apc-btn--primary" style="font-size:.72rem;padding:.45rem 1rem">Request →</a>' +
                    '</div></div>'
                );
            });
        } catch(msg) {
            $grid.html('<div class="apc-loading" style="color:#991b1b">' + esc(String(msg)) + '</div>');
        }
    }

    /* ─── Empty legs ─────────────────────────────── */

    async function searchEmptyLegs() {
        const $list = $('#apc-el-list');
        if (!$list.length) return;
        $list.html('<div class="apc-loading"><span class="apc-spinner"></span>&ensp;Loading live empty leg deals…</div>');
        try {
            const res  = await apiPost('apc_empty_legs', {
                from_icao: $('[name="el_from"]').val() || '',
                to_icao:   $('[name="el_to"]').val()   || '',
                date_from: $('#el-date').val()         || '',
            });
            const legs = res.results || [];
            if (!legs.length) { $list.html('<div class="apc-loading">No empty legs found. New deals posted daily.</div>'); return; }
            $list.empty();
            legs.forEach(el => {
                const dep  = (el.departure_airport && el.departure_airport.icao)   || el.departure_airport_icao   || '—';
                const arr  = (el.destination_airport && el.destination_airport.icao) || el.destination_airport_icao || '—';
                const dCty = (el.departure_airport && el.departure_airport.city_name)    || '';
                const aCty = (el.destination_airport && el.destination_airport.city_name) || '';
                const date = el.date || el.departure_date || '—';
                const ac   = el.aircraft_type_name || (el.aircraft && el.aircraft.aircraft_type_name) || 'Charter Jet';
                $list.append(
                    '<div class="apc-el-item">' +
                    '<div><div class="apc-el-airport">' + esc(dep) + '</div><div class="apc-el-city">' + esc(dCty) + '</div></div>' +
                    '<div class="apc-el-arrow">→</div>' +
                    '<div><div class="apc-el-airport">' + esc(arr) + '</div><div class="apc-el-city">' + esc(aCty) + '</div></div>' +
                    '<div class="apc-el-meta"><strong>' + esc(ac) + '</strong></div>' +
                    '<div class="apc-el-date">' + esc(date) + '</div>' +
                    '<div><a href="#apc-charter-form" class="apc-btn apc-btn--gold" style="font-size:.7rem;padding:.4rem .875rem">Enquire</a></div>' +
                    '</div>'
                );
            });
        } catch(msg) {
            $list.html('<div class="apc-loading" style="color:#991b1b">Could not load: ' + esc(String(msg)) + '</div>');
        }
    }

    /* ─── Charter request form ───────────────────── */

    $(document).on('click', '#apc-charter-submit', async function () {
        const $btn    = $(this);
        const $widget = $btn.closest('.apc-widget');
        hideAlert($widget);

        const from  = $widget.find('[name="req_from"]').val() || '';
        const to    = $widget.find('[name="req_to"]').val()   || '';
        const date  = $widget.find('#req-date').val()         || '';
        const pax   = parseInt($widget.find('#req-pax').val() || 4, 10);
        const name  = $widget.find('#req-name').val().trim();
        const email = $widget.find('#req-email').val().trim();
        const phone = $widget.find('#req-phone').val().trim();
        const notes = $widget.find('#req-notes').val().trim();

        if (!from)  { showAlert($widget, 'Please select a departure airport.'); return; }
        if (!to)    { showAlert($widget, 'Please select a destination airport.'); return; }
        if (!date)  { showAlert($widget, 'Please select a departure date.'); return; }
        if (!name)  { showAlert($widget, 'Please enter your full name.'); return; }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showAlert($widget, 'Please enter a valid email address.'); return;
        }

        setBusy($btn, true);
        try {
            await apiPost('apc_charter_request', { from, to, date, pax, name, email, phone, notes });
            $widget.find('#apc-charter-body').fadeOut(300, function() {
                $widget.find('#apc-charter-success').removeAttr('hidden').fadeIn(350);
            });
        } catch(msg) {
            showAlert($widget, typeof msg === 'string' ? msg : 'Submission failed. Please try again.');
        } finally {
            setBusy($btn, false);
        }
    });

    /* ─── Init ───────────────────────────────────── */

    $(async function () {
        if ($('#apc-jet-grid').length) { await initAircraftClasses(); await searchJets(); }
        if ($('#apc-el-list').length)  { await searchEmptyLegs(); }
        $(document).on('click', '#jet-search-btn', () => searchJets());
        $(document).on('click', '#el-search-btn',  () => searchEmptyLegs());
        $(document).on('keydown', '#jet-class,#jet-pax,#jet-name', e => {
            if (e.key === 'Enter') { e.preventDefault(); searchJets(); }
        });
    });

})(jQuery);
