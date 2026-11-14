/**
 * Ember Spice Reservation Manager — Admin JS
 */
(function($) {
    'use strict';

    var nonce = esAdmin.nonce;
    var ajaxurl = esAdmin.ajaxurl;

    /* ======================
       Modal Helpers
       ====================== */
    function openModal(id) {
        $('#' + id).fadeIn(150);
        $('body').addClass('es-modal-open');
    }
    function closeModal(id) {
        $('#' + id).fadeOut(150);
        $('body').removeClass('es-modal-open');
    }

    // Close via X button or close-btn
    $(document).on('click', '.es-modal-close, .es-modal-close-btn', function() {
        var target = $(this).data('target');
        if (target) {
            closeModal(target);
        } else {
            $(this).closest('.es-modal-overlay').fadeOut(150);
            $('body').removeClass('es-modal-open');
        }
    });

    // Close on overlay click
    $(document).on('click', '.es-modal-overlay', function(e) {
        if ($(e.target).hasClass('es-modal-overlay')) {
            $(this).fadeOut(150);
            $('body').removeClass('es-modal-open');
        }
    });

    /* ======================
       View Reservation Detail
       ====================== */
    $(document).on('click', '.js-view-res', function() {
        var id = $(this).data('id');
        $('#es-modal-title').text('Loading…');
        $('#es-modal-body').html('<div class="es-loading">Loading reservation details…</div>');
        openModal('es-modal-overlay');

        $.get(ajaxurl, {
            action: 'es_admin_get_details',
            nonce: nonce,
            id: id
        }, function(resp) {
            if (!resp.success) {
                $('#es-modal-body').html('<div class="es-alert es-alert-error">' + resp.data.message + '</div>');
                return;
            }
            var r = resp.data.reservation;
            var log = resp.data.activity_log;
            var notifs = resp.data.notifications;
            var avail_tables = resp.data.available_tables;

            $('#es-modal-title').text('Reservation — ' + r.reservation_ref);

            // Build status badge
            var statusHtml = '<span class="es-status es-status-' + r.status + '">' + capitalize(r.status) + '</span>';

            // Table info
            var tableHtml = r.table_number
                ? 'Table ' + escHtml(r.table_number) + ' — ' + escHtml(r.table_name) + ' (' + escHtml(r.table_location) + ')'
                : '<em class="es-unassigned">Not yet assigned</em>';

            var html = '<div class="es-detail-grid">';
            html += '<div class="es-detail-row"><span class="es-detail-label">Reference</span><span class="es-detail-val"><code class="es-ref">' + escHtml(r.reservation_ref) + '</code></span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Status</span><span class="es-detail-val">' + statusHtml + '</span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Guest Name</span><span class="es-detail-val">' + escHtml(r.guest_name) + '</span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Email</span><span class="es-detail-val"><a href="mailto:' + escHtml(r.guest_email) + '">' + escHtml(r.guest_email) + '</a></span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Phone</span><span class="es-detail-val"><a href="tel:' + escHtml(r.guest_phone) + '">' + escHtml(r.guest_phone) + '</a></span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Date</span><span class="es-detail-val">' + escHtml(r.reservation_date) + '</span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Time</span><span class="es-detail-val">' + escHtml(r.reservation_time) + '</span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Guests</span><span class="es-detail-val">' + parseInt(r.guest_count) + ' Guest(s)</span></div>';
            html += '<div class="es-detail-row es-detail-full"><span class="es-detail-label">Assigned Table</span><span class="es-detail-val">' + tableHtml + '</span></div>';
            html += '<div class="es-detail-row es-detail-full"><span class="es-detail-label">Special Requests</span><span class="es-detail-val">' + (r.special_requests ? escHtml(r.special_requests) : '—') + '</span></div>';
            if (r.rejection_reason) {
                html += '<div class="es-detail-row es-detail-full"><span class="es-detail-label">Rejection Reason</span><span class="es-detail-val" style="color:#B86F48;font-weight:600;">' + escHtml(r.rejection_reason) + '</span></div>';
            }
            if (r.cancellation_reason) {
                html += '<div class="es-detail-row es-detail-full"><span class="es-detail-label">Cancellation Reason</span><span class="es-detail-val" style="color:#806B52;font-weight:600;">' + escHtml(r.cancellation_reason) + '</span></div>';
            }
            html += '<div class="es-detail-row"><span class="es-detail-label">Submitted</span><span class="es-detail-val" style="font-size:12px;color:#777568;">' + escHtml(r.created_at) + '</span></div>';
            html += '</div>';

            // Action buttons inside modal
            html += '<div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:16px;border-top:1px solid rgba(120,133,104,0.18);">';
            if (r.status === 'pending') {
                html += '<button class="es-btn es-btn-primary js-approve-res" data-id="' + r.id + '" data-date="' + escHtml(r.reservation_date) + '" data-time="' + escHtml(r.reservation_time) + '" data-guests="' + r.guest_count + '">✓ Approve &amp; Assign Table</button>';
                html += '<button class="es-btn es-btn-danger js-reject-res" data-id="' + r.id + '">✗ Decline Request</button>';
            }
            if (r.status === 'confirmed') {
                html += '<button class="es-btn es-btn-ghost js-cancel-res" data-id="' + r.id + '">Cancel Booking</button>';
            }
            html += '</div>';

            // Activity Log
            if (log && log.length) {
                html += '<div class="es-section-header" style="margin-top:24px;"><h2>Activity History</h2></div>';
                html += '<div class="es-activity-log">';
                $.each(log, function(i, entry) {
                    html += '<div class="es-activity-item">';
                    html += '<span class="es-activity-time">' + escHtml(entry.created_at) + '</span>';
                    html += '<span class="es-activity-action">' + escHtml(entry.action) + '</span>';
                    html += '<span class="es-activity-details">' + escHtml(entry.details) + ' — <em>' + escHtml(entry.user_name) + '</em></span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            // Notifications sent
            if (notifs && notifs.length) {
                html += '<div class="es-section-header" style="margin-top:20px;"><h2>Emails Sent</h2></div>';
                html += '<div class="es-activity-log">';
                $.each(notifs, function(i, n) {
                    var statusDot = n.status === 'sent' ? '✅' : '❌';
                    html += '<div class="es-activity-item">';
                    html += '<span class="es-activity-time">' + escHtml(n.sent_at) + '</span>';
                    html += '<span class="es-activity-action">' + statusDot + ' ' + escHtml(n.recipient_type) + '</span>';
                    html += '<span class="es-activity-details">' + escHtml(n.subject) + ' → ' + escHtml(n.recipient_email) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            $('#es-modal-body').html(html);
        });
    });

    /* ======================
       Approve Modal
       ====================== */
    $(document).on('click', '.js-approve-res', function() {
        var id = $(this).data('id');
        var date = $(this).data('date');
        var time = $(this).data('time');
        var guests = $(this).data('guests');

        // Close detail modal if open
        closeModal('es-modal-overlay');

        $('#approve-res-id').val(id);
        $('#approve-notes').val('');
        $('#approve-error').hide();
        $('#approve-table-select').html('<option value="">— Loading available tables… —</option>');

        openModal('es-approve-modal');

        // Fetch available tables
        $.get(ajaxurl, {
            action: 'es_admin_get_available_tables',
            nonce: nonce,
            date: date,
            time: time,
            guests: guests,
            res_id: id
        }, function(resp) {
            var $sel = $('#approve-table-select');
            if (!resp.success || !resp.data.tables.length) {
                $sel.html('<option value="">— No available tables for this date/time/capacity —</option>');
                return;
            }
            var opts = '<option value="">— Select a Table —</option>';
            $.each(resp.data.tables, function(i, t) {
                opts += '<option value="' + t.id + '">Table ' + escHtml(t.table_number) + ' — ' + escHtml(t.name) + ' (' + t.capacity + ' seats, ' + escHtml(t.location) + ')</option>';
            });
            $sel.html(opts);
        });
    });

    $('#approve-submit-btn').on('click', function() {
        var $btn = $(this);
        var id = $('#approve-res-id').val();
        var table_id = $('#approve-table-select').val();
        var notes = $('#approve-notes').val();

        $('#approve-error').hide();

        if (!table_id) {
            $('#approve-error').text('Please select an available table to assign.').show();
            return;
        }

        $btn.text('Approving…').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'es_admin_approve_reservation',
            nonce: nonce,
            id: id,
            table_id: table_id,
            notes: notes
        }, function(resp) {
            $btn.text('Approve & Send Confirmation Email').prop('disabled', false);
            if (resp.success) {
                closeModal('es-approve-modal');
                showToast(resp.data.message, 'success');
                setTimeout(function(){ location.reload(); }, 1800);
            } else {
                $('#approve-error').text(resp.data.message).show();
            }
        });
    });

    /* ======================
       Reject Modal
       ====================== */
    $(document).on('click', '.js-reject-res', function() {
        var id = $(this).data('id');
        closeModal('es-modal-overlay');
        $('#reject-res-id').val(id);
        $('#reject-reason').val('');
        $('#reject-error').hide();
        openModal('es-reject-modal');
    });

    $('#reject-submit-btn').on('click', function() {
        var $btn = $(this);
        var id = $('#reject-res-id').val();
        var reason = $('#reject-reason').val().trim();

        if (!reason) {
            $('#reject-error').text('Please provide a rejection reason so the guest understands.').show();
            return;
        }

        $btn.text('Rejecting…').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'es_admin_reject_reservation',
            nonce: nonce,
            id: id,
            reason: reason
        }, function(resp) {
            $btn.text('Reject & Notify Guest').prop('disabled', false);
            if (resp.success) {
                closeModal('es-reject-modal');
                showToast(resp.data.message, 'success');
                setTimeout(function(){ location.reload(); }, 1800);
            } else {
                $('#reject-error').text(resp.data.message).show();
            }
        });
    });

    /* ======================
       Cancel Modal
       ====================== */
    $(document).on('click', '.js-cancel-res', function() {
        var id = $(this).data('id');
        closeModal('es-modal-overlay');
        $('#cancel-res-id').val(id);
        $('#cancel-reason').val('');
        $('#cancel-error').hide();
        openModal('es-cancel-modal');
    });

    $('#cancel-submit-btn').on('click', function() {
        var $btn = $(this);
        var id = $('#cancel-res-id').val();
        var reason = $('#cancel-reason').val();

        $btn.text('Cancelling…').prop('disabled', true);
        $.post(ajaxurl, {
            action: 'es_admin_cancel_reservation',
            nonce: nonce,
            id: id,
            reason: reason
        }, function(resp) {
            $btn.text('Cancel Reservation').prop('disabled', false);
            if (resp.success) {
                closeModal('es-cancel-modal');
                showToast(resp.data.message, 'success');
                setTimeout(function(){ location.reload(); }, 1800);
            } else {
                $('#cancel-error').text(resp.data.message).show();
            }
        });
    });

    /* ======================
       Table Management
       ====================== */
    $('#btn-add-table').on('click', function() {
        $('#table-modal-title').text('Add New Table');
        $('#table-modal-id').val('0');
        $('#table-modal-number').val('');
        $('#table-modal-name').val('');
        $('#table-modal-capacity').val('4');
        $('#table-modal-location').val('Main Dining Room');
        $('#table-modal-active').prop('checked', true);
        $('#table-modal-error, #table-modal-success').hide();
        openModal('es-table-modal');
    });

    $(document).on('click', '.js-edit-table', function() {
        var $el = $(this);
        $('#table-modal-title').text('Edit Table');
        $('#table-modal-id').val($el.data('id'));
        $('#table-modal-number').val($el.data('number'));
        $('#table-modal-name').val($el.data('name'));
        $('#table-modal-capacity').val($el.data('capacity'));
        $('#table-modal-location').val($el.data('location'));
        $('#table-modal-active').prop('checked', $el.data('active') == 1);
        $('#table-modal-error, #table-modal-success').hide();
        openModal('es-table-modal');
    });

    $('#table-modal-submit').on('click', function() {
        var $btn = $(this);
        $btn.text('Saving…').prop('disabled', true);
        $('#table-modal-error, #table-modal-success').hide();

        $.post(ajaxurl, {
            action: 'es_admin_save_table',
            nonce: nonce,
            id: $('#table-modal-id').val(),
            table_number: $('#table-modal-number').val(),
            name: $('#table-modal-name').val(),
            capacity: $('#table-modal-capacity').val(),
            location: $('#table-modal-location').val(),
            is_active: $('#table-modal-active').is(':checked') ? 1 : 0
        }, function(resp) {
            $btn.text('Save Table').prop('disabled', false);
            if (resp.success) {
                $('#table-modal-success').text(resp.data.message).show();
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                $('#table-modal-error').text(resp.data.message).show();
            }
        });
    });

    $(document).on('click', '.js-delete-table', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Delete table "' + name + '"? This cannot be undone if the table has no active reservations.')) return;

        $.post(ajaxurl, {
            action: 'es_admin_delete_table',
            nonce: nonce,
            id: id
        }, function(resp) {
            if (resp.success) {
                showToast(resp.data.message, 'success');
                setTimeout(function(){ location.reload(); }, 1200);
            } else {
                showToast(resp.data.message, 'error');
            }
        });
    });

    /* ======================
       Toast Notification
       ====================== */
    function showToast(msg, type) {
        var $toast = $('<div>').addClass('es-toast es-toast-' + type).text(msg);
        $('body').append($toast);
        setTimeout(function(){ $toast.addClass('es-toast-visible'); }, 50);
        setTimeout(function(){ $toast.removeClass('es-toast-visible'); setTimeout(function(){ $toast.remove(); }, 300); }, 3000);
    }

    // Toast styles injected via JS
    $('<style>')
        .text('.es-toast{position:fixed;bottom:24px;right:24px;z-index:200000;background:#FFFCF5;border:1px solid rgba(120,133,104,0.35);color:#2A2C24;padding:14px 22px;border-radius:8px;font-size:13.5px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;box-shadow:0 12px 35px rgba(52,75,53,0.15);opacity:0;transition:opacity 0.25s ease,transform 0.25s ease;transform:translateY(8px);max-width:380px;line-height:1.5;}'
            + '.es-toast-visible{opacity:1;transform:translateY(0);}'
            + '.es-toast-success{border-color:#788568;background:#EBF0E6;color:#243525;}'
            + '.es-toast-error{border-color:#B86F48;background:#FDF1EA;color:#8C3912;}')
        .appendTo('head');

    /* ======================
       Utilities
       ====================== */
    function capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    function escHtml(str) {
        return $('<div>').text(str || '').html();
    }

})(jQuery);

