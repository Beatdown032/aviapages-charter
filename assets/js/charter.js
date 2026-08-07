/**
 * AviaPages Charter Suite v2 — Frontend JS
 *
 * Flight calculator API fields (confirmed by AviaPages CTO):
 *
 * REQUEST flags (must be true to receive data):
 *   airway_time_weather_impacted          → get wind-adjusted flight time
 *   airway_time                           → get airway flight time
 *   great_circle_time                     → get great circle time
 *   airway_fuel_weather_impacted          → get wind-adjusted fuel total
 *   airway_fuel_weather_impacted_detailed → get per-segment fuel breakdown
 *
 * RESPONSE fields (returned when flags are true):
 *   airway_time_weather_impacted → minutes (wind-adjusted, most accurate)
 *   airway_time                  → minutes (airway time, no wind)
 *   great_circle_time            → minutes (great circle estimate)
 *   airway_distance              → km
 *   great_circle_distance        → km
 *   airway_fuel_weather_impacted → kg (total fuel, wind-adjusted)
 *   airway_fuel_weather_impacted_detailed → array of segments
 *   request_id                   → integer (link to aviapages.com results)
 *   wind                         → object or value with wind data
 */
(function ($) {
    'use strict';

    const CFG = {
        ajaxUrl:    (typeof APC !== 'undefined') ? APC.ajaxUrl  : '',
        nonce:      (typeof APC !== 'undefined') ? APC.nonce    : '',
        currency:   (typeof APC !== 'undefined') ? APC.currency : 'USD',
        resultsUrl: 'https://aviapages.com/flight_route_calculation_result_new/',
    };

    /* ─── Formatters ─────────────────────────────── */

    function fmtMins(val) {
        const m = parseInt(val, 10);
        if (!val && val !== 0 || isNaN(m) || m < 0) return '—';
        const h = Math.floor(m / 60), r = m % 60;
        return h > 0 ? h + 'h ' + String(r).padStart(2, '0') + 'm' : m + 'm';
    }

    function fmtKm(val) {
        const n = parseFloat(val);
        if (val == null || isNaN(n)) return '—';
        return Math.round(n).toLocaleString() + ' km';
    }

    function fmtKg(val) {
        const n = parseFloat(val);
        if (val == null || isNaN(n)) return '—';
        return Math.round(n).toLocaleString() + ' kg';
    }

    function fmtMoney(val) {
        const n = parseFloat(val);
        if (val == null || isNaN(n)) return '—';
        try {
            return new Intl.NumberFormat('en-US', {
                style: 'currency', currency: CFG.currency, maximumFractionDigits: 0
            }).format(n);
        } catch(e) {
            return CFG.currency + ' ' + Math.round(n).toLocaleString();
        }
    }

    function esc(s) { return $('<div>').text(String(s ?? '')).html(); }

    /* ─── UI helpers ─────────────────────────────── */

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

    function ajax(method, action, data) {
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
                console.error('[APC]', action, status, xhr.status, (xhr.responseText || '').slice(0, 200));
                reject('Network error — please check your connection.');
            });
        });
    }

    const apiGet  = (action, data) => ajax('GET',  action, data);
    const apiPost = (action, data) => ajax('POST', action, data);

    /* ─── Airport autocomplete ───────────────────── */

    const acTimers = {};

    $(document).on('input', '.apc-airport-input', function () {
        const $inp  = $(this);
        const key   = $inp.data('field') || 'ac_' + Math.random();
        const q     = $inp.val().trim();
        const $wrap = $inp.closest('.apc-ac-wrap');
        const $list = $wrap.find('.apc-ac-list').not('.apc-ac-list--profiles');

        $wrap.find('.apc-airport-icao').val('');
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
                    $('<li>').attr({ role: 'option', 'data-type': 'airport' })
                        .html('<strong>' + esc(code) + '</strong> ' + esc(ap.name) +
                              ' <small>— ' + esc(ap.city) + ', ' + esc(ap.country) + '</small>')
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
        $wrap.find('.apc-airport-tz').val(ap.time_shift || '');
        $wrap.find('.apc-airport-lat').val(ap.lat || ap.latitude  || '');
        $wrap.find('.apc-airport-lng').val(ap.lng || ap.longitude || '');
        $wrap.find('.apc-ac-tag').text(code).show();
        $(this).closest('.apc-ac-list').empty().hide();

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
                (p.aircraft_type_name  || '').toLowerCase().includes(q) ||
                (p.name                || '').toLowerCase().includes(q)  ||
                (p.aircraft_class_name || '').toLowerCase().includes(q)  ||
                (p.aircraft_type_icao  || '').toLowerCase().includes(q))
            : all;   // empty query → show every aircraft type (scrollable list)

        $list.empty();
        if (!show.length) { $list.hide(); return; }

        show.forEach(p => {
            $('<li>').attr({ role: 'option', 'data-type': 'profile' })
                .html('<strong>' + esc(p.aircraft_type_name || p.name) + '</strong>' +
                      '&ensp;<small>' + esc(p.aircraft_class_name || '') + '</small>' +
                      '&ensp;<code style="font-size:.7rem;color:#b45309">' + esc(p.aircraft_type_icao) + '</code>')
                .data('prof', p)
                .appendTo($list);
        });
        $list.show();
    });

    $(document).on('click', '.apc-ac-list--profiles li[data-type="profile"]', function (e) {
        e.stopPropagation();
        const p = $(this).data('prof');
        $(this).closest('.apc-ac-wrap').find('#apc-profile-search')
               .val(p.aircraft_type_name || p.name);
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
        const swapVals = (sel) => {
            const $a = $row.find(sel).eq(0), $b = $row.find(sel).eq(1);
            if ($a.length && $b.length) { const v = $a.val(); $a.val($b.val()); $b.val(v); }
        };
        swapVals('.apc-airport-input');
        swapVals('.apc-airport-icao');
        swapVals('.apc-airport-tz');
        swapVals('.apc-airport-lat');
        swapVals('.apc-airport-lng');

        const $tags = $row.find('.apc-ac-tag');
        const [t0, t1, s0, s1] = [$tags.eq(0).text(), $tags.eq(1).text(),
                                    $tags.eq(0).is(':visible'), $tags.eq(1).is(':visible')];
        $tags.eq(0).text(t1); s1 ? $tags.eq(0).show() : $tags.eq(0).hide();
        $tags.eq(1).text(t0); s0 ? $tags.eq(1).show() : $tags.eq(1).hide();
        const newTz = $row.find('.apc-airport-tz').eq(0).val();
        $('#apc-tz-label').text(newTz ? '(' + newTz + ')' : '');
    });

    /* ─── Mode tabs ──────────────────────────────── */

    $(document).on('click', '.apc-tab', function () {
        const $t = $(this);
        $t.closest('.apc-mode-tabs').find('.apc-tab').removeClass('apc-tab--active');
        $t.addClass('apc-tab--active');
        $t.closest('.apc-widget').find('.apc-calc-submit').data('mode', $t.data('tab'));
    });

    /* ─── Fuel detail toggle ─────────────────────── */

    $(document).on('click', '#res-fuel-toggle', function () {
        const $detail = $('#res-fuel-detail');
        const isOpen  = $detail.is(':visible');
        $detail.slideToggle(200);
        $(this).text(isOpen ? '▼ Details' : '▲ Hide Details');
    });

    /* ─── Advanced toggle ────────────────────────── */

    $(document).on('click', '.apc-advanced-toggle', function () {
        const $p = $(this).closest('.apc-form').find('.apc-advanced-panel');
        $p.slideToggle(200);
        $(this).text($p.is(':visible') ? '✕ Close Advanced' : '⚙ Advanced Settings');
    });

    /* ═══════════════════════════════════════════════
     *  CALCULATOR SUBMIT
     *
     *  Sends ICAO codes + CTO-confirmed data flags.
     *  Parses response using the confirmed field names.
     * ═══════════════════════════════════════════════ */

    $(document).on('click', '.apc-calc-submit', async function () {
        const $btn    = $(this);
        const $widget = $btn.closest('.apc-widget');
        const mode    = $btn.data('mode') || 'price';

        hideAlert($widget);
        $('#apc-results-panel').hide();

        const fromIcao = $widget.find('[name="from_icao"]').val() || '';
        const toIcao   = $widget.find('[name="to_icao"]').val()   || '';
        const date     = $widget.find('[name="date"]').val()      || '';
        const time     = $widget.find('[name="time"]').val()      || '';
        const pax      = $widget.find('[name="pax"]').val()       || '4';
        const acIcao   = $widget.find('#apc-aircraft-icao').val() || '';
        const acName   = $widget.find('#apc-aircraft-name').val() || acIcao;
        const etops    = $widget.find('[name="etops"]').val()     || '';
        const payloadKg = $widget.find('[name="payload_kg"]').val()    || '';
        const extraFuel = $widget.find('[name="extra_fuel_kg"]').val() || '';

        if (!fromIcao) { showAlert($widget, 'Please select a departure airport from the suggestions.'); return; }
        if (!toIcao)   { showAlert($widget, 'Please select a destination airport from the suggestions.'); return; }
        if (fromIcao === toIcao) { showAlert($widget, 'Departure and destination cannot be the same.'); return; }
        if (!date)     { showAlert($widget, 'Please select a departure date.'); return; }
        if (!acIcao)   { showAlert($widget, 'Please select an aircraft type from the suggestions.'); return; }

        setBusy($btn, true);

        try {
            const action = mode === 'price' ? 'apc_flight_and_price' : 'apc_flight_only';
            const res = await apiPost(action, {
                from_icao: fromIcao, to_icao: toIcao,
                aircraft_icao: acIcao,
                date, time, pax,
                etops, payload_kg: payloadKg, extra_fuel_kg: extraFuel,
            });

            console.log('[APC] API response:', res);

            // Response is { flight: {...}, price: {...} }
            const f = res.flight || res;
            const p = res.price  || {};

            renderResults($widget, f, p, {
                fromIcao, toIcao, acName, date, mode
            });

            // Show results panel immediately (not animated) so Leaflet can
            // measure the map container dimensions correctly, then animate
            $('#apc-results-panel').show();
            $('html,body').animate({
                scrollTop: $('#apc-results-panel').offset().top - 80
            }, 400);

        } catch (msg) {
            showAlert($widget, typeof msg === 'string' ? msg : 'Calculation failed. Please check your inputs.');
            console.error('[APC] Error:', msg);
        } finally {
            setBusy($btn, false);
        }
    });

    /* ─── Render results ─────────────────────────── */
    /*
     * CONFIRMED response structure (from debug screenshot 2026-04-27):
     * {
     *   "fuel": {
     *     "airway_weather_impacted": 63,            ← total fuel kg (wind-adjusted)
     *     "airway_weather_impacted_block": 451,     ← block fuel kg
     *     "airway_weather_impacted_detailed": [     ← per-segment breakdown
     *       { "weight": 4063.95, "distance": 0, "duration": 5,
     *         "fuel_used": 22.5, "operation": "taxi", "final_altitude": 0 },
     *       { "weight": 4041.45, "distance": 5.469, "duration": 0.656,
     *         "fuel_used": 10.715, "operation": "climb", "final_altitude": 1000 },
     *       ...
     *     ]
     *   },
     *   "time": {
     *     "airway_weather_impacted": 13,  ← wind-adjusted time (minutes)
     *     "airway": 13,                   ← airway time (minutes)
     *     "great_circle": 2,              ← great circle time (minutes)
     *     "average_speed": 2              ← average speed time (minutes)
     *   },
     *   "airport": {
     *     "arrival_airport": "OMSJ",
     *     "departure_airport": "OMDB",
     *     "airway_distance": 10,          ← airway distance (nm or km)
     *     "great_circle_distance": 9      ← GC distance (nm or km)
     *   },
     *   "aircraft": "HondaJet",
     *   "request_id": 12345              ← link to aviapages results page
     * }
     */

    function renderResults($widget, f, p, meta) {
        // Convenience: extract the nested sub-objects
        const timeObj    = f.time    || {};  // flight times
        const fuelObj    = f.fuel    || {};  // fuel data
        const airportObj = f.airport || {};  // distance data

        // ── Route header ──────────────────────────────
        $('#res-from').text(airportObj.departure_airport || meta.fromIcao);
        $('#res-to').text(airportObj.arrival_airport     || meta.toIcao);
        $('#res-aircraft-label').text(f.aircraft || meta.acName || '');
        $('#res-date-label').text(meta.date || '');

        // AviaPages full results link
        const reqId = f.request_id || f.id;
        if (reqId) {
            $('#res-aviapages-link')
                .attr('href', CFG.resultsUrl + '?request_id=' + reqId)
                .show();
        } else {
            $('#res-aviapages-link').hide();
        }

        // ── Flight time ───────────────────────────────
        // time.airway_weather_impacted = wind-adjusted (most accurate, shown first)
        // time.airway                  = without wind
        // time.great_circle            = great circle estimate
        // time.average_speed           = average speed estimate
        const windAdjMins = timeObj.airway_weather_impacted;
        const airwayMins  = timeObj.airway;
        const gcMins      = timeObj.great_circle;
        const avgMins     = timeObj.average_speed;

        $('#res-flight-time').text(fmtMins(windAdjMins) || fmtMins(airwayMins) || '—');

        // Sub: show airway time if different from wind-adjusted
        if (airwayMins != null && airwayMins !== windAdjMins) {
            $('#res-airway-time').text('Airway: ' + fmtMins(airwayMins)
                + (avgMins != null ? ' · Avg: ' + fmtMins(avgMins) : ''));
        } else if (avgMins != null) {
            $('#res-airway-time').text('Avg speed: ' + fmtMins(avgMins));
        } else {
            $('#res-airway-time').text('');
        }

        // ── Distance ──────────────────────────────────
        // airport object confirmed keys (from debug + AviaPages results):
        //   airway_distance       = nmi (AviaPages shows "205 nmi / 381 km")
        //   great_circle_distance = nmi
        // AviaPages result page shows both nmi and km
        const airwayDist = airportObj.airway_distance;        // in nmi
        const gcDist     = airportObj.great_circle_distance;  // in nmi

        if (airwayDist != null) {
            const nmi = Math.round(airwayDist);
            const km  = Math.round(airwayDist * 1.852);
            $('#res-distance').text(km.toLocaleString() + ' km');
            $('#res-gc-distance').text(nmi.toLocaleString() + ' nmi');
        } else {
            $('#res-distance').text('—');
            $('#res-gc-distance').text('');
        }

        // ── Fuel ──────────────────────────────────────
        // fuel.airway_weather_impacted          = total fuel kg (wind-adjusted)
        // fuel.airway_weather_impacted_block    = block fuel
        // fuel.airway_weather_impacted_detailed = per-phase segments array
        const totalFuel    = fuelObj.airway_weather_impacted;
        const blockFuel    = fuelObj.airway_weather_impacted_block;
        const fuelDetailed = fuelObj.airway_weather_impacted_detailed;

        $('#res-fuel').text(totalFuel != null ? fmtKg(totalFuel) : '—');
        $('#res-fuel-sub').text(blockFuel != null ? 'Block: ' + fmtKg(blockFuel) : '');

        // Detailed fuel breakdown table
        // Each segment: { weight, distance, duration, fuel_used, operation, final_altitude }
        if (Array.isArray(fuelDetailed) && fuelDetailed.length) {
            let html = '<table style="width:100%;font-size:.8rem;border-collapse:collapse">' +
                '<thead><tr style="border-bottom:1px solid var(--apc-rule)">' +
                '<th style="text-align:left;padding:.35rem .6rem;color:var(--apc-muted)">Phase</th>' +
                '<th style="text-align:right;padding:.35rem .6rem;color:var(--apc-muted)">Duration (min)</th>' +
                '<th style="text-align:right;padding:.35rem .6rem;color:var(--apc-muted)">Distance (km)</th>' +
                '<th style="text-align:right;padding:.35rem .6rem;color:var(--apc-muted)">Fuel Used (kg)</th>' +
                '<th style="text-align:right;padding:.35rem .6rem;color:var(--apc-muted)">Alt (ft)</th>' +
                '</tr></thead><tbody>';

            fuelDetailed.forEach(seg => {
                const op   = seg.operation    || '—';
                const dur  = seg.duration     != null ? seg.duration.toFixed(1)  : '—';
                const dist = seg.distance     != null ? seg.distance.toFixed(1)  : '—';
                const fuel = seg.fuel_used    != null ? seg.fuel_used.toFixed(1) : '—';
                const alt  = seg.final_altitude != null ? seg.final_altitude      : '—';
                html += '<tr style="border-bottom:1px solid var(--apc-rule)">' +
                    '<td style="padding:.3rem .6rem;text-transform:capitalize">' + esc(op)   + '</td>' +
                    '<td style="text-align:right;padding:.3rem .6rem">'           + esc(String(dur))  + '</td>' +
                    '<td style="text-align:right;padding:.3rem .6rem">'           + esc(String(dist)) + '</td>' +
                    '<td style="text-align:right;padding:.3rem .6rem">'           + esc(String(fuel)) + '</td>' +
                    '<td style="text-align:right;padding:.3rem .6rem">'           + esc(String(alt))  + '</td>' +
                    '</tr>';
            });

            // Totals row
            const totalFuelUsed = fuelDetailed.reduce((s, seg) => s + (seg.fuel_used || 0), 0);
            const totalDist     = fuelDetailed.reduce((s, seg) => s + (seg.distance  || 0), 0);
            const totalDur      = fuelDetailed.reduce((s, seg) => s + (seg.duration  || 0), 0);
            html += '<tr style="font-weight:600;border-top:2px solid var(--apc-rule)">' +
                '<td style="padding:.4rem .6rem">TOTAL</td>' +
                '<td style="text-align:right;padding:.4rem .6rem">' + totalDur.toFixed(1) + '</td>' +
                '<td style="text-align:right;padding:.4rem .6rem">' + totalDist.toFixed(1) + '</td>' +
                '<td style="text-align:right;padding:.4rem .6rem">' + totalFuelUsed.toFixed(1) + '</td>' +
                '<td></td></tr>';

            html += '</tbody></table>';
            $('#res-fuel-table').html(html);
            $('#res-fuel-detail').stop(true, true).hide(); // collapsed by default
            $('#res-fuel-toggle')
                .attr('data-open', 'false')
                .html('▼ Show Fuel Breakdown by Phase')
                .show();
        } else {
            $('#res-fuel-detail').hide();
        }

        // ── Wind impact ───────────────────────────────
        // Wind impact = difference between wind-adjusted and non-wind time
        const windImpact = (windAdjMins != null && airwayMins != null)
            ? windAdjMins - airwayMins
            : null;

        if (windImpact !== null) {
            const sign = windImpact >= 0 ? '+' : '';
            $('#res-wind').text(sign + fmtMins(Math.abs(windImpact)));
            $('#res-wind-sub').text(windImpact > 0 ? 'headwind' : windImpact < 0 ? 'tailwind' : 'no wind impact');
        } else {
            // Fallback: check for top-level wind field
            const wind = f.wind;
            if (wind != null && typeof wind === 'object') {
                const spd = wind.speed_kts || wind.speed || wind.kts;
                const dir = wind.direction || wind.type || '';
                $('#res-wind').text(spd != null ? Math.round(spd) + ' kts' : '—');
                $('#res-wind-sub').text(String(dir).toLowerCase());
            } else if (wind != null && !isNaN(parseFloat(wind))) {
                const wm = parseFloat(wind);
                $('#res-wind').text((wm >= 0 ? '+' : '') + fmtMins(Math.abs(wm)));
                $('#res-wind-sub').text(wm > 0 ? 'headwind' : 'tailwind');
            } else {
                $('#res-wind').text('—');
                $('#res-wind-sub').text('');
            }
        }

        // ── Tech stops ────────────────────────────────
        const stops = f.tech_stops || f.technical_stops || [];
        if (Array.isArray(stops) && stops.length) {
            const labels = stops.map(s =>
                typeof s === 'object' ? (s.icao || s.name || JSON.stringify(s)) : String(s)
            );
            $('#res-stops-list').text(labels.join(' → '));
            $('#res-stops').show();
        } else {
            $('#res-stops').hide();
        }

        // ── Price breakdown ───────────────────────────
        if (meta.mode === 'price' && p && Object.keys(p).length) {
            const total = p.total_price || p.total || p.charter_price || p.price_total;
            const base  = p.base_price  || p.aircraft_cost || p.base_cost;
            const fees  = p.fees_total  || p.airport_fees  || p.fees;
            const taxes = p.taxes       || p.tax_total     || p.overflight_fees;

            $('#res-price-total').text(fmtMoney(total));
            $('#res-total-final').text(fmtMoney(total));
            $('#res-base-price').text(fmtMoney(base));
            $('#res-fees').text(fmtMoney(fees));
            $('#res-taxes').text(fmtMoney(taxes));
            $('#res-price-section').show();
        } else {
            $('#res-price-section').hide();
        }

        // ── Map ───────────────────────────────────────
        // Draw route map using airport coordinates stored at selection time
        const fromLat = parseFloat($widget.find('[name="from_lat"]').val());
        const fromLng = parseFloat($widget.find('[name="from_lng"]').val());
        const toLat   = parseFloat($widget.find('[name="to_lat"]').val());
        const toLng   = parseFloat($widget.find('[name="to_lng"]').val());

        if (!isNaN(fromLat) && !isNaN(fromLng) && !isNaN(toLat) && !isNaN(toLng)) {
            renderMap('res-map', fromLat, fromLng, toLat, toLng,
                airportObj.departure_airport || meta.fromIcao,
                airportObj.arrival_airport   || meta.toIcao);
            $('#res-map-wrap').show();
        } else {
            $('#res-map-wrap').hide();
        }
    }

    /* ─── Leaflet map ────────────────────────────── */

    let _leafletLoaded = false;
    let _leafletMap    = null;

    function renderMap(containerId, fromLat, fromLng, toLat, toLng, fromCode, toCode) {

        const initMap = () => {
            const el = document.getElementById(containerId);
            if (!el) return;

            const L = window.L;

            // Properly destroy previous Leaflet instance.
            // Must delete _leaflet_id from the DOM element — this is what
            // Leaflet checks to detect "already initialised" containers.
            // Do NOT use el.innerHTML = '' — that breaks things on re-init.
            if (_leafletMap) {
                try { _leafletMap.remove(); } catch(e) {}
                _leafletMap = null;
            }
            // Reset Leaflet's internal container marker (safe cross-browser)
            delete el._leaflet_id;

            _leafletMap = L.map(containerId, {
                zoomControl:        true,
                scrollWheelZoom:    false,
                attributionControl: true,
            });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/attributions">CARTO</a>',
                subdomains:  'abcd',
                maxZoom:     19,
            }).addTo(_leafletMap);

            // Great-circle arc
            const arcPoints = greatCirclePoints(fromLat, fromLng, toLat, toLng, 80);

            // Draw route line
            L.polyline(arcPoints, {
                color:     '#b45309',
                weight:    2.5,
                opacity:   0.85,
                dashArray: '8 5',
            }).addTo(_leafletMap);

            // Custom ICAO label markers
            const makeIcon = (code, isDep) => L.divIcon({
                className: '',
                html: '<div style="' +
                    'display:inline-flex;align-items:center;gap:.3rem;' +
                    'background:#1c1917;color:#fcd34d;' +
                    'font-size:.65rem;font-weight:700;letter-spacing:.05em;' +
                    'padding:.25rem .5rem;border-radius:5px;' +
                    'border:2px solid ' + (isDep ? '#b45309' : '#c9a84c') + ';' +
                    'white-space:nowrap;box-shadow:0 2px 10px rgba(0,0,0,.4);' +
                    '">' +
                    '<span style="width:7px;height:7px;border-radius:50%;background:' + (isDep ? '#ef4444' : '#22c55e') + ';flex-shrink:0"></span>' +
                    code +
                    '</div>',
                iconSize:   [0, 0],
                iconAnchor: [-4, 10],
            });

            L.marker([fromLat, fromLng], { icon: makeIcon(fromCode, true) })
             .bindPopup('<strong>' + fromCode + '</strong> · Departure')
             .addTo(_leafletMap);

            L.marker([toLat, toLng], { icon: makeIcon(toCode, false) })
             .bindPopup('<strong>' + toCode + '</strong> · Destination')
             .addTo(_leafletMap);

            // FIT BOUNDS — must call invalidateSize first because the
            // container may not have reached its final rendered size yet
            // (the results panel was just slid into view).
            setTimeout(() => {
                _leafletMap.invalidateSize();
                const bounds = L.latLngBounds(arcPoints).pad(0.15);
                _leafletMap.fitBounds(bounds);
            }, 320); // wait for slideDown animation to finish
        };

        if (_leafletLoaded && window.L) {
            initMap();
        } else {
            // Lazy-load Leaflet — CartoDB needs no API key, very fast
            if (!document.getElementById('leaflet-css')) {
                const link = document.createElement('link');
                link.id    = 'leaflet-css';
                link.rel   = 'stylesheet';
                link.href  = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
                document.head.appendChild(link);
            }

            if (!document.getElementById('leaflet-js')) {
                const script  = document.createElement('script');
                script.id     = 'leaflet-js';
                script.src    = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
                script.onload = () => { _leafletLoaded = true; initMap(); };
                document.head.appendChild(script);
            } else {
                // Script tag exists but may already be loaded
                const check = setInterval(() => {
                    if (window.L) { clearInterval(check); _leafletLoaded = true; initMap(); }
                }, 100);
            }
        }
    }

    /**
     * Spherical linear interpolation — accurate great-circle arc points.
     */
    function greatCirclePoints(lat1, lng1, lat2, lng2, n) {
        const R2D = 180 / Math.PI;
        const D2R = Math.PI / 180;

        const φ1 = lat1 * D2R, λ1 = lng1 * D2R;
        const φ2 = lat2 * D2R, λ2 = lng2 * D2R;

        const d = 2 * Math.asin(Math.sqrt(
            Math.sin((φ2 - φ1) / 2) ** 2 +
            Math.cos(φ1) * Math.cos(φ2) * Math.sin((λ2 - λ1) / 2) ** 2
        ));

        if (d < 0.0001) return [[lat1, lng1], [lat2, lng2]];

        const pts = [];
        for (let i = 0; i <= n; i++) {
            const f = i / n;
            const A = Math.sin((1 - f) * d) / Math.sin(d);
            const B = Math.sin(f * d)       / Math.sin(d);
            const x = A * Math.cos(φ1) * Math.cos(λ1) + B * Math.cos(φ2) * Math.cos(λ2);
            const y = A * Math.cos(φ1) * Math.sin(λ1) + B * Math.cos(φ2) * Math.sin(λ2);
            const z = A * Math.sin(φ1)                 + B * Math.sin(φ2);
            pts.push([
                Math.atan2(z, Math.sqrt(x * x + y * y)) * R2D,
                Math.atan2(y, x) * R2D,
            ]);
        }
        return pts;
    }

    /* ─── Fuel toggle ────────────────────────────── */

    $(document).on('click', '#res-fuel-toggle', function () {
        const $btn    = $(this);
        const isOpen  = $btn.attr('data-open') === 'true';
        // Scope inside .apc-results-panel — works even with multiple widgets on page
        const $panel  = $btn.closest('.apc-results-panel');
        const $detail = $panel.find('#res-fuel-detail');

        if (isOpen) {
            $detail.stop(true, true).slideUp(250);
            $btn.attr('data-open', 'false').html('▼ Show Fuel Breakdown by Phase');
        } else {
            $detail.stop(true, true).slideDown(250);
            $btn.attr('data-open', 'true').html('▲ Hide Fuel Breakdown');
        }
    });

    /* ─── Aircraft search widget ─────────────────── */

    async function initAircraftClasses() {
        const $sel = $('#jet-class');
        if (!$sel.length || $sel.find('option').length > 1) return;
        try {
            const res = await apiPost('apc_aircraft_classes', {});
            (res.results || []).forEach(c =>
                $('<option>').val(c.aircraft_class_id).text(c.name).appendTo($sel)
            );
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
            if (!jets.length) {
                $grid.html('<div class="apc-loading">No aircraft match your filters.</div>');
                return;
            }
            $grid.empty();
            jets.forEach(j => {
                const name  = j.aircraft_type_name || j.name || 'Aircraft';
                const cls   = j.aircraft_class_name || '';
                const pax   = j.pax_maximum || '—';
                const range = j.range_maximum ? Math.round(j.range_maximum).toLocaleString() + ' nm' : '—';
                const speed = j.cruise_speed_max ? Math.round(j.cruise_speed_max) + ' kts' : '—';
                $grid.append(
                    '<div class="apc-jet-card">' +
                    '<div class="apc-jet-card__head">' +
                    '<div class="apc-jet-card__name">' + esc(name) + '</div>' +
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
        $list.html('<div class="apc-loading"><span class="apc-spinner"></span>&ensp;Loading live empty legs…</div>');
        try {
            const res  = await apiPost('apc_empty_legs', {
                from_icao: $('[name="el_from"]').val() || '',
                to_icao:   $('[name="el_to"]').val()   || '',
                date_from: $('#el-date').val()         || '',
            });
            const legs = res.results || [];
            if (!legs.length) {
                $list.html('<div class="apc-loading">No empty legs found. New deals posted daily.</div>');
                return;
            }
            $list.empty();
            legs.forEach(el => {
                const dep  = (el.departure_airport   && el.departure_airport.icao)   || el.departure_airport_icao   || '—';
                const arr  = (el.destination_airport && el.destination_airport.icao) || el.destination_airport_icao || '—';
                const dCty = (el.departure_airport   && el.departure_airport.city_name)    || '';
                const aCty = (el.destination_airport && el.destination_airport.city_name)  || '';
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
            $widget.find('#apc-charter-body').fadeOut(300, function () {
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
