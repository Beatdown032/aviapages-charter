/**
 * AviaPages Charter Suite — Frontend JS
 * Handles: airport autocomplete, flight/price calculators,
 *          aircraft search, empty legs, charter request form.
 */
(function ($) {
    'use strict';

    const ajax  = aviaConfig.ajaxUrl;
    const nonce = aviaConfig.nonce;
    const currency = aviaConfig.currency || 'USD';

    /* ══════════════════════════════════════════════════
     *  UTILITIES
     * ══════════════════════════════════════════════════ */

    function showError(selector, msg) {
        const $el = $(selector);
        $el.text(msg).show();
        setTimeout(() => $el.fadeOut(), 6000);
    }

    function fmtMins(mins) {
        if (!mins && mins !== 0) return '—';
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return h + 'h ' + (m < 10 ? '0' : '') + m + 'm';
    }

    function fmtMoney(val) {
        if (!val && val !== 0) return '—';
        return new Intl.NumberFormat('en-US', {
            style: 'currency', currency: currency, maximumFractionDigits: 0
        }).format(val);
    }

    function fmtNum(val, unit) {
        if (!val && val !== 0) return '—';
        return Math.round(val).toLocaleString() + (unit ? ' ' + unit : '');
    }

    function btnLoading($btn, loading) {
        $btn.find('.avia-btn-text').toggle(!loading);
        $btn.find('.avia-btn-loader').toggle(loading);
        $btn.prop('disabled', loading);
    }

    /* ══════════════════════════════════════════════════
     *  AIRPORT AUTOCOMPLETE
     * ══════════════════════════════════════════════════ */

    let acTimers = {};

    $(document).on('input', '.avia-airport-input', function () {
        const $input = $(this);
        const query  = $input.val().trim();
        const $wrap  = $input.closest('.avia-autocomplete-wrap');
        const $list  = $wrap.find('.avia-autocomplete-list');
        const target = $input.data('target');

        // Clear hidden val when typing
        $('[name="' + target + '"]').val('');

        if (query.length < 2) { $list.hide().empty(); return; }

        clearTimeout(acTimers[target]);
        acTimers[target] = setTimeout(() => {
            $.get(ajax, { action: 'avia_search_airports', nonce, q: query }, function (res) {
                $list.empty();
                if (!res.success || !res.data.length) { $list.hide(); return; }

                res.data.forEach(ap => {
                    $('<li>')
                        .text(ap.label)
                        .data('icao', ap.icao || ap.iata)
                        .appendTo($list);
                });

                $list.show();
            });
        }, 300);
    });

    $(document).on('click', '.avia-autocomplete-list li', function () {
        const $li   = $(this);
        const $wrap = $li.closest('.avia-autocomplete-wrap');
        const icao  = $li.data('icao');
        const label = $li.text();
        const $input = $wrap.find('.avia-airport-input');
        const target = $input.data('target');

        $input.val(label);
        $('[name="' + target + '"]').val(icao);
        $wrap.find('.avia-autocomplete-list').hide().empty();
    });

    // Close dropdowns on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.avia-autocomplete-wrap').length) {
            $('.avia-autocomplete-list').hide().empty();
        }
    });

    /* ══════════════════════════════════════════════════
     *  SWAP BUTTON
     * ══════════════════════════════════════════════════ */

    $(document).on('click', '.avia-swap-btn', function () {
        const $row     = $(this).closest('.avia-row');
        const $inputs  = $row.find('.avia-airport-input');
        const $hiddens = $row.find('.avia-airport-val');

        if ($inputs.length < 2) return;

        const t1 = $inputs.eq(0).val(), t2 = $inputs.eq(1).val();
        const v1 = $hiddens.eq(0).val(), v2 = $hiddens.eq(1).val();

        $inputs.eq(0).val(t2); $inputs.eq(1).val(t1);
        $hiddens.eq(0).val(v2); $hiddens.eq(1).val(v1);
    });

    /* ══════════════════════════════════════════════════
     *  AIRCRAFT PROFILE SEARCH (for calculators)
     * ══════════════════════════════════════════════════ */

    function initProfileSearch(searchId, selectId) {
        const $search = $('#' + searchId);
        const $select = $('#' + selectId);

        if (!$search.length) return;

        let profileTimer;
        $search.on('input', function () {
            const q = $search.val().trim();
            clearTimeout(profileTimer);
            profileTimer = setTimeout(() => {
                $.post(ajax, {
                    action: 'avia_search_aircraft',
                    nonce,
                    name: q
                }, function (res) {
                    // Use aircraft_profiles endpoint via a custom ajax response
                });

                // Actually use profiles endpoint via flight calc action
                $.get(ajax, {
                    action: 'avia_search_airports', // reuse for profile — we'll do direct
                    nonce
                });

                // Direct profile search
                $.ajax({
                    url: ajax,
                    method: 'POST',
                    data: {
                        action: 'avia_flight_calc',
                        nonce,
                        profile_search: q,
                        mode: 'profiles_only'
                    }
                });
            }, 350);
        });
    }

    /* ══════════════════════════════════════════════════
     *  LOAD AIRCRAFT PROFILES (via aircraft list call)
     * ══════════════════════════════════════════════════ */

    function loadProfiles(selectId, searchInputId) {
        const $select = $('#' + selectId);
        if (!$select.length) return;

        // Fetch aircraft list and populate select
        $.post(ajax, { action: 'avia_search_aircraft', nonce }, function (res) {
            if (!res.success) return;
            const jets = res.data.results || [];
            jets.forEach(j => {
                if (j.aircraft_profile_id) {
                    $('<option>').val(j.aircraft_profile_id)
                        .text(j.aircraft_type_name || j.name)
                        .appendTo($select);
                }
            });
        });

        if (searchInputId) {
            $('#' + searchInputId).on('input', function () {
                const q = $(this).val().toLowerCase();
                $select.find('option').each(function () {
                    const show = !q || $(this).text().toLowerCase().includes(q) || $(this).val() === '';
                    $(this).toggle(show);
                });
            });
        }
    }

    /* ══════════════════════════════════════════════════
     *  FLIGHT CALCULATOR
     * ══════════════════════════════════════════════════ */

    $(document).on('click', '.avia-calc-btn[data-mode="flight"]', function () {
        const $btn  = $(this);
        const $w    = $btn.closest('.avia-widget');
        const from  = $w.find('[name="from_icao"]').val();
        const to    = $w.find('[name="to_icao"]').val();
        const prof  = $w.find('[name="profile_id"]').val();
        const date  = $w.find('[name="dep_date"]').val();

        $w.find('.avia-error').hide();

        if (!from || !to || !prof) {
            showError($w.find('.avia-error'), 'Please select departure, destination, and aircraft type.');
            return;
        }

        btnLoading($btn, true);

        $.post(ajax, {
            action: 'avia_flight_calc',
            nonce,
            from, to,
            profile: prof,
            date
        }, function (res) {
            btnLoading($btn, false);

            if (!res.success) {
                showError($w.find('.avia-error'), res.data || 'Calculation failed. Check your inputs.');
                return;
            }

            const d = res.data;
            $w.find('#res-flight-time').text(fmtMins(d.flight_time));
            $w.find('#res-distance').text(fmtNum(d.distance_nm, 'nm'));
            $w.find('#res-fuel').text(fmtNum(d.fuel_used, 'kg'));
            $w.find('#res-wind').text(d.wind_impact ? (d.wind_impact > 0 ? '+' : '') + fmtMins(d.wind_impact) : '—');

            const routes = d.route_icao ? d.route_icao.join(' → ') : '';
            $w.find('#res-route').text(routes ? 'Route: ' + routes : '');

            $w.find('#avia-flight-result').slideDown(300);
        });
    });

    /* ══════════════════════════════════════════════════
     *  PRICE CALCULATOR
     * ══════════════════════════════════════════════════ */

    $(document).on('click', '.avia-calc-btn[data-mode="price"]', function () {
        const $btn  = $(this);
        const $w    = $btn.closest('.avia-widget');
        const from  = $w.find('[name="price_from"]').val();
        const to    = $w.find('[name="price_to"]').val();
        const prof  = $w.find('[name="price_profile_id"]').val();
        const date  = $w.find('[name="price_date"]').val();
        const pax   = $w.find('[name="price_pax"]').val();

        $w.find('.avia-error').hide();

        if (!from || !to || !prof) {
            showError($w.find('.avia-error'), 'Please fill in all fields.');
            return;
        }

        btnLoading($btn, true);

        $.post(ajax, {
            action: 'avia_price_calc',
            nonce,
            from, to, prof,
            profile: prof,
            date, pax
        }, function (res) {
            btnLoading($btn, false);

            if (!res.success) {
                showError($w.find('.avia-error'), res.data || 'Price calculation failed.');
                return;
            }

            const d = res.data;
            const total = d.total_price || d.price || d.total || 0;
            const base  = d.base_price  || d.cost  || 0;
            const fees  = d.fees_total  || d.fees  || 0;
            const comm  = d.commission  || 0;
            const time  = d.flight_time || d.flight_time_minutes || null;

            $w.find('.avia-price-value').text(fmtMoney(total));
            $w.find('#res-price-note').text('Incl. ' + (aviaConfig.commission || 15) + '% broker commission');
            $w.find('#res-base-cost').text(fmtMoney(base));
            $w.find('#res-fees').text(fmtMoney(fees));
            $w.find('#res-commission').text(fmtMoney(comm));
            $w.find('#res-price-time').text(fmtMins(time));

            $w.find('#avia-price-result').slideDown(300);
        });
    });

    /* ══════════════════════════════════════════════════
     *  AIRCRAFT SEARCH
     * ══════════════════════════════════════════════════ */

    function renderAircraftGrid(results, $grid) {
        $grid.empty();

        if (!results || !results.length) {
            $grid.html('<div class="avia-loading">No aircraft found matching your criteria.</div>');
            return;
        }

        results.forEach(j => {
            const name  = j.aircraft_type_name || j.name || 'Aircraft';
            const cls   = j.aircraft_class_name || '';
            const pax   = j.pax_maximum || '—';
            const range = j.range_maximum ? Math.round(j.range_maximum) + ' nm' : '—';
            const speed = j.cruise_speed_max ? Math.round(j.cruise_speed_max) + ' kts' : '—';
            const reg   = j.registration || '';

            $grid.append(`
                <div class="avia-jet-card">
                    <div class="avia-jet-card__head">
                        <div class="avia-jet-card__name">${esc(name)}</div>
                        ${cls ? `<span class="avia-jet-card__class">${esc(cls)}</span>` : ''}
                    </div>
                    <div class="avia-jet-card__body">
                        <div class="avia-jet-card__stats">
                            <div class="avia-jet-stat"><span class="avia-jet-stat__label">Pax</span><span class="avia-jet-stat__value">${esc(String(pax))}</span></div>
                            <div class="avia-jet-stat"><span class="avia-jet-stat__label">Range</span><span class="avia-jet-stat__value">${esc(range)}</span></div>
                            <div class="avia-jet-stat"><span class="avia-jet-stat__label">Speed</span><span class="avia-jet-stat__value">${esc(speed)}</span></div>
                            ${reg ? `<div class="avia-jet-stat"><span class="avia-jet-stat__label">Reg</span><span class="avia-jet-stat__value">${esc(reg)}</span></div>` : ''}
                        </div>
                    </div>
                    <div class="avia-jet-card__footer">
                        <a href="#avia-charter-form" class="avia-btn avia-btn--primary" style="font-size:0.75rem;padding:0.5rem 1rem">Request Quote →</a>
                    </div>
                </div>
            `);
        });
    }

    function esc(s) {
        return $('<div>').text(s).html();
    }

    // Load aircraft classes into filter
    $(document).ready(function () {
        const $classFilter = $('#filter-class');
        if ($classFilter.length) {
            $.post(ajax, { action: 'avia_aircraft_classes', nonce }, function (res) {
                if (res.success && res.data.results) {
                    res.data.results.forEach(c => {
                        $('<option>').val(c.aircraft_class_id).text(c.name).appendTo($classFilter);
                    });
                }
            });

            // Initial load
            searchAircraft();
        }
    });

    function searchAircraft() {
        const $grid = $('#avia-aircraft-grid');
        if (!$grid.length) return;

        $grid.html('<div class="avia-loading">Loading aircraft…</div>');

        $.post(ajax, {
            action: 'avia_search_aircraft',
            nonce,
            class: $('#filter-class').val(),
            pax:   $('#filter-pax').val()
        }, function (res) {
            if (!res.success) {
                $grid.html('<div class="avia-loading">Failed to load aircraft.</div>');
                return;
            }
            renderAircraftGrid(res.data.results || [], $grid);
        });
    }

    $(document).on('click', '#avia-search-jets-btn', searchAircraft);

    /* ══════════════════════════════════════════════════
     *  EMPTY LEGS
     * ══════════════════════════════════════════════════ */

    function renderEmptyLegs(results, $grid) {
        $grid.empty();

        if (!results || !results.length) {
            $grid.html('<div class="avia-loading" style="padding:1.5rem 0">No empty legs found. Try different filters.</div>');
            return;
        }

        results.forEach(el => {
            const dep   = el.departure_airport_icao  || el.departure_airport?.icao  || '—';
            const arr   = el.destination_airport_icao || el.destination_airport?.icao || '—';
            const depCity = el.departure_airport?.city_name  || '';
            const arrCity = el.destination_airport?.city_name || '';
            const date  = el.date || el.departure_date || '';
            const ac    = el.aircraft_type_name || el.aircraft?.aircraft_type_name || 'Charter Jet';
            const id    = el.id || '';

            $grid.append(`
                <div class="avia-el-card" data-id="${esc(String(id))}">
                    <div class="avia-el-route">
                        <div class="avia-el-airport">${esc(dep)}</div>
                        <div class="avia-el-city">${esc(depCity)}</div>
                    </div>
                    <div class="avia-el-arrow">→</div>
                    <div class="avia-el-route">
                        <div class="avia-el-airport">${esc(arr)}</div>
                        <div class="avia-el-city">${esc(arrCity)}</div>
                    </div>
                    <div class="avia-el-info">
                        <div class="avia-el-date">${esc(date)}</div>
                        <div class="avia-el-aircraft">${esc(ac)}</div>
                    </div>
                    <div class="avia-el-actions">
                        <a href="#avia-charter-form" class="avia-btn avia-btn--gold" style="font-size:0.7rem;padding:0.45rem 0.9rem">
                            Enquire
                        </a>
                    </div>
                </div>
            `);
        });
    }

    // Load initial empty legs
    $(document).ready(function () {
        const $grid = $('#avia-el-grid');
        if (!$grid.length) return;
        loadEmptyLegs($grid);
    });

    function loadEmptyLegs($grid) {
        if (!$grid) $grid = $('#avia-el-grid');
        $grid.html('<div class="avia-loading">Loading live empty legs…</div>');

        $.post(ajax, {
            action: 'avia_empty_legs',
            nonce,
            from_icao: $('[name="el_from"]').val(),
            to_icao:   $('[name="el_to"]').val(),
            date:      $('#el-date').val()
        }, function (res) {
            if (!res.success) {
                $grid.html('<div class="avia-loading">Could not load empty legs.</div>');
                return;
            }
            renderEmptyLegs(res.data.results || [], $grid);
        });
    }

    $(document).on('click', '#avia-el-search-btn', function () {
        loadEmptyLegs($('#avia-el-grid'));
    });

    /* ══════════════════════════════════════════════════
     *  CHARTER REQUEST FORM
     * ══════════════════════════════════════════════════ */

    $(document).on('click', '#avia-submit-request', function () {
        const $btn = $(this);

        const from  = $('#req_from').val();
        const to    = $('#req_to').val();
        const date  = $('#req_date').val();
        const pax   = $('#req_pax').val();
        const name  = $('#req_name').val().trim();
        const email = $('#req_email').val().trim();
        const phone = $('#req_phone').val().trim();
        const notes = $('#req_notes').val().trim();

        $('#avia-request-error').hide();

        if (!from || !to) {
            showError('#avia-request-error', 'Please select both departure and destination airports.');
            return;
        }
        if (!date) {
            showError('#avia-request-error', 'Please select a departure date.');
            return;
        }
        if (!name || !email) {
            showError('#avia-request-error', 'Please enter your name and email address.');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('#avia-request-error', 'Please enter a valid email address.');
            return;
        }

        btnLoading($btn, true);

        $.post(ajax, {
            action: 'avia_charter_request',
            nonce,
            from, to, date, pax, name, email, phone, notes
        }, function (res) {
            btnLoading($btn, false);

            if (!res.success) {
                showError('#avia-request-error', res.data || 'Submission failed. Please try again.');
                return;
            }

            $('#charter-request-form').fadeOut(300, function () {
                $('#avia-request-success').fadeIn(300);
            });
        });
    });

    /* ══════════════════════════════════════════════════
     *  PROFILE SELECTS (load all available jets)
     * ══════════════════════════════════════════════════ */

    $(document).ready(function () {
        // Load jets into both calculator selects
        const selects = [
            { sel: '#avia-profile-select',       search: '#avia-profile-search' },
            { sel: '#avia-price-profile-select',  search: '#avia-price-profile-search' },
        ];

        selects.forEach(cfg => {
            if (!$(cfg.sel).length) return;

            $.post(ajax, { action: 'avia_search_aircraft', nonce }, function (res) {
                if (!res.success) return;
                const jets = res.data.results || [];
                jets.forEach(j => {
                    const val  = j.aircraft_profile_id || '';
                    const text = (j.aircraft_type_name || j.name || 'Aircraft') +
                                 (j.aircraft_class_name ? ' (' + j.aircraft_class_name + ')' : '');
                    if (val) {
                        $('<option>').val(val).text(text).appendTo($(cfg.sel));
                    }
                });

                // Filter select by search input
                $(cfg.search).on('input', function () {
                    const q = $(this).val().toLowerCase();
                    $(cfg.sel + ' option').each(function () {
                        const show = !q || $(this).text().toLowerCase().includes(q) || $(this).val() === '';
                        $(this).toggle(show);
                    });
                    if (!$(cfg.sel + ' option:selected').is(':visible')) {
                        $(cfg.sel).val('');
                    }
                });
            });
        });
    });

})(jQuery);
