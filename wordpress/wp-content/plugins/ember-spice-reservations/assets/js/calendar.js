/**
 * Ember Spice Reservation Manager — Interactive Admin Calendar
 */
(function($) {
    'use strict';

    var nonce = esAdmin.nonce;
    var ajaxurl = esAdmin.ajaxurl;
    var today = new Date(esAdmin.today);

    var currentDate = new Date(today);
    var currentView = 'month';
    var allEvents = [];

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    var DAYS   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    /* ======================
       Init
       ====================== */
    function init() {
        renderCalendar();
        bindNav();
        loadEvents();
    }

    function bindNav() {
        $('#es-cal-prev').on('click', function() {
            if (currentView === 'month') {
                currentDate.setMonth(currentDate.getMonth() - 1);
            } else {
                currentDate.setDate(currentDate.getDate() - 7);
            }
            renderCalendar();
            loadEvents();
        });

        $('#es-cal-next').on('click', function() {
            if (currentView === 'month') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else {
                currentDate.setDate(currentDate.getDate() + 7);
            }
            renderCalendar();
            loadEvents();
        });

        $('.js-cal-view').on('click', function() {
            currentView = $(this).data('view');
            if (currentView === 'today') { currentDate = new Date(today); currentView = 'month'; }
            $('.js-cal-view').removeClass('active');
            $(this).addClass('active');
            renderCalendar();
            loadEvents();
        });
    }

    /* ======================
       Render calendar grid
       ====================== */
    function renderCalendar() {
        var $grid = $('#es-cal-grid');
        var $title = $('#es-cal-title');
        $grid.empty();

        var year  = currentDate.getFullYear();
        var month = currentDate.getMonth();

        $title.text(MONTHS[month] + ' ' + year);

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var prevMonthDays = new Date(year, month, 0).getDate();

        // Build 42 cells
        for (var i = 0; i < 42; i++) {
            var dayNum, isCurrentMonth = true;

            if (i < firstDay) {
                dayNum = prevMonthDays - firstDay + i + 1;
                isCurrentMonth = false;
            } else if (i - firstDay >= daysInMonth) {
                dayNum = i - firstDay - daysInMonth + 1;
                isCurrentMonth = false;
            } else {
                dayNum = i - firstDay + 1;
            }

            var dateStr = '';
            if (isCurrentMonth) {
                var mm = (month + 1).toString().padStart(2,'0');
                var dd = dayNum.toString().padStart(2,'0');
                dateStr = year + '-' + mm + '-' + dd;
            }

            var isToday = dateStr === esAdmin.today;

            var $cell = $('<div>')
                .addClass('es-cal-day' + (!isCurrentMonth ? ' es-cal-other-month' : '') + (isToday ? ' es-cal-today' : ''))
                .attr('data-date', dateStr);

            $cell.append($('<div>').addClass('es-cal-day-num').text(dayNum));
            $grid.append($cell);
        }
    }

    /* ======================
       Load Events from AJAX
       ====================== */
    function loadEvents() {
        var year  = currentDate.getFullYear();
        var month = currentDate.getMonth();

        var start = year + '-' + (month + 1).toString().padStart(2,'0') + '-01';
        var end   = year + '-' + (month + 1).toString().padStart(2,'0') + '-' + new Date(year, month + 1, 0).getDate().toString().padStart(2,'0');

        $.get(ajaxurl, {
            action: 'es_admin_calendar_events',
            nonce: nonce,
            start: start,
            end: end
        }, function(resp) {
            if (!resp.success) return;
            allEvents = resp.data.events;
            plotEvents();
        });
    }

    /* ======================
       Plot Events on grid
       ====================== */
    function plotEvents() {
        // Clear existing event spans
        $('.es-cal-event').remove();

        $.each(allEvents, function(i, ev) {
            var $cell = $('.es-cal-day[data-date="' + ev.date + '"]');
            if (!$cell.length) return;

            var $event = $('<span>')
                .addClass('es-cal-event')
                .attr('data-id', ev.id)
                .text(ev.time + ' ' + ev.guest_name)
                .css('background-color', ev.color)
                .on('click', function(e) {
                    e.stopPropagation();
                    showPopup(ev, e);
                });

            $cell.append($event);
        });

        // Click on day cell = show all reservations for that day
        $('.es-cal-day').off('click').on('click', function() {
            var date = $(this).data('date');
            if (!date) return;
            var dayEvents = allEvents.filter(function(e){ return e.date === date; });
            showDayPopup(dayEvents, date);
        });
    }

    /* ======================
       Event Popup
       ====================== */
    function showPopup(ev, e) {
        var html = '<div style="font-family:Georgia,serif;font-weight:700;color:#344B35;font-size:15px;margin-bottom:6px;">' + escHtml(ev.guest_name) + '</div>';
        html += '<div style="font-size:12.5px;color:#777568;margin-bottom:4px;">⏰ ' + escHtml(ev.time) + ' &bull; 👥 ' + ev.guests + ' Guests</div>';
        html += '<div style="font-size:12.5px;color:#777568;margin-bottom:8px;">📍 ' + escHtml(ev.table) + '</div>';
        html += '<div style="font-size:12px;margin-bottom:12px;"><span class="es-status es-status-' + ev.status + '">' + capitalize(ev.status) + '</span></div>';
        html += '<button class="es-btn es-btn-forest es-btn-sm js-view-res" data-id="' + ev.id + '">View Details</button>';

        $('#es-cal-popup-content').html(html);
        $('#es-cal-popup').css({
            top: e.pageY - 40 + 'px',
            left: Math.min(e.pageX + 10, window.innerWidth - 320) + 'px',
            display: 'block',
            pointerEvents: 'auto'
        });
    }

    function showDayPopup(events, date) {
        if (!events.length) return;
        var d = new Date(date);
        var dateLabel = DAYS[d.getDay()] + ', ' + MONTHS[d.getMonth()] + ' ' + (d.getDate() + 1) + ', ' + d.getFullYear();
        var html = '<div style="font-family:Georgia,serif;font-weight:700;color:#344B35;font-size:14px;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid rgba(120,133,104,0.2);">' + dateLabel + '</div>';
        $.each(events, function(i, ev) {
            html += '<div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(120,133,104,0.12);">';
            html += '<span style="width:10px;height:10px;border-radius:50%;background:' + ev.color + ';flex-shrink:0;"></span>';
            html += '<div style="flex:1;font-size:12.5px;">';
            html += '<strong style="color:#344B35;">' + escHtml(ev.time) + ' — ' + escHtml(ev.guest_name) + '</strong><br>';
            html += '<span style="color:#777568;font-size:11.5px;">👥 ' + ev.guests + ' Guests &bull; ' + escHtml(ev.table) + '</span>';
            html += '</div>';
            html += '<button class="es-btn es-btn-sm es-btn-secondary js-view-res" data-id="' + ev.id + '" style="flex-shrink:0;">View</button>';
            html += '</div>';
        });

        $('#es-cal-popup-content').html(html);
        var $cell = $('.es-cal-day[data-date="' + date + '"]');
        var offset = $cell.offset();
        $('#es-cal-popup').css({
            top: offset.top + $cell.outerHeight() + 2 + 'px',
            left: Math.min(offset.left, $(window).width() - 320) + 'px',
            display: 'block',
            pointerEvents: 'auto'
        });
    }

    // Close popup
    $(document).on('click', '#es-cal-popup-close', function() {
        $('#es-cal-popup').hide();
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#es-cal-popup').length && !$(e.target).hasClass('es-cal-event') && !$(e.target).hasClass('es-cal-day')) {
            $('#es-cal-popup').hide();
        }
    });

    /* ======================
       Utilities
       ====================== */
    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function escHtml(str) {
        return $('<div>').text(str || '').html();
    }

    /* Init */
    if ($('#es-calendar-month').length) {
        init();
    }

})(jQuery);

