<?php if (!defined('ABSPATH')) exit; ?>

<!-- Reservation Detail Modal -->
<div id="es-modal-overlay" class="es-modal-overlay" style="display:none;">
    <div class="es-modal">
        <div class="es-modal-header">
            <h3 id="es-modal-title">Reservation Details</h3>
            <button class="es-modal-close" id="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body" id="es-modal-body">
            <div class="es-loading">Loading...</div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="es-approve-modal" class="es-modal-overlay" style="display:none;">
    <div class="es-modal es-modal-sm">
        <div class="es-modal-header">
            <h3>Approve Reservation &bull; Table Allocation</h3>
            <button class="es-modal-close" data-target="es-approve-modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="approve-res-id" value="">
            <div class="es-form-group">
                <label class="es-label">Assign Restaurant Table <span class="es-required">*</span></label>
                <select id="approve-table-select" class="es-select">
                    <option value="">— Loading available tables… —</option>
                </select>
                <small class="es-help">Only tables with sufficient seating capacity and zero timing conflicts are displayed.</small>
            </div>
            <div class="es-form-group">
                <label class="es-label">Host Notes (Optional)</label>
                <textarea id="approve-notes" class="es-textarea" rows="2" placeholder="Private seating notes for the host and server team…"></textarea>
            </div>
            <div id="approve-error" class="es-alert es-alert-error" style="display:none;"></div>
            <div class="es-modal-footer">
                <button id="approve-submit-btn" class="es-btn es-btn-primary">Confirm &amp; Send Confirmation Email</button>
                <button class="es-btn es-btn-ghost es-modal-close-btn" data-target="es-approve-modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="es-reject-modal" class="es-modal-overlay" style="display:none;">
    <div class="es-modal es-modal-sm">
        <div class="es-modal-header">
            <h3>Decline Reservation Request</h3>
            <button class="es-modal-close" data-target="es-reject-modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="reject-res-id" value="">
            <p class="es-modal-desc">Please provide a polite, thoughtful explanation (e.g., dining room capacity reached, hearth maintenance) that will be emailed to the guest.</p>
            <div class="es-form-group">
                <label class="es-label">Reason for Declining <span class="es-required">*</span></label>
                <textarea id="reject-reason" class="es-textarea" rows="3" placeholder="Explain kindly to the guest why this seating window cannot be accommodated…"></textarea>
            </div>
            <div id="reject-error" class="es-alert es-alert-error" style="display:none;"></div>
            <div class="es-modal-footer">
                <button id="reject-submit-btn" class="es-btn es-btn-danger">Decline &amp; Send Notice</button>
                <button class="es-btn es-btn-ghost es-modal-close-btn" data-target="es-reject-modal">Return</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="es-cancel-modal" class="es-modal-overlay" style="display:none;">
    <div class="es-modal es-modal-sm">
        <div class="es-modal-header">
            <h3>Cancel Confirmed Reservation</h3>
            <button class="es-modal-close" data-target="es-cancel-modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="cancel-res-id" value="">
            <p class="es-modal-desc">The allocated table will be returned to the open pool and a polite cancellation email will be dispatched to the guest.</p>
            <div class="es-form-group">
                <label class="es-label">Cancellation Reason (Optional)</label>
                <textarea id="cancel-reason" class="es-textarea" rows="2" placeholder="Guest requested cancellation, schedule conflict, etc.…"></textarea>
            </div>
            <div id="cancel-error" class="es-alert es-alert-error" style="display:none;"></div>
            <div class="es-modal-footer">
                <button id="cancel-submit-btn" class="es-btn es-btn-danger">Release Table &amp; Cancel</button>
                <button class="es-btn es-btn-ghost es-modal-close-btn" data-target="es-cancel-modal">Keep Reservation</button>
            </div>
        </div>
    </div>
</div>
