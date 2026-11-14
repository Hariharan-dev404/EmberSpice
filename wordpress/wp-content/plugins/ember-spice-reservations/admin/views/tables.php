<?php
/**
 * Admin View: Natural Organic Table Management
 *
 * @package EmberSpiceReservations
 */
if (!defined('ABSPATH')) exit;

$tables = ES_Table_Manager::get_tables();
$total_capacity = 0;
$active_count = 0;
foreach ($tables as $t) {
    if ($t->is_active) {
        $total_capacity += intval($t->capacity);
        $active_count++;
    }
}
?>
<div class="es-wrap">
    <div class="es-page-header">
        <div class="es-page-title">
            <div class="es-emblem">🪑</div>
            <div>
                <span class="es-eyebrow">Ember Spice Dining Rooms</span>
                <h1>Restaurant Tables &amp; Seating</h1>
                <p>Configure dining spaces &bull; <?php echo count($tables); ?> tables &bull; <?php echo $total_capacity; ?> total guest covers (<?php echo $active_count; ?> active tables)</p>
            </div>
        </div>
        <div class="es-header-actions">
            <button class="es-btn es-btn-primary" id="btn-add-table">+ Add New Table</button>
        </div>
    </div>

    <div class="es-tables-layout">
        <div class="es-section-card es-tables-list" style="background: transparent; border: none; padding: 0; box-shadow: none;">
            <?php if (empty($tables)): ?>
                <div class="es-section-card es-empty-state">
                    <span class="es-empty-icon">🌱</span>
                    <p>No dining tables configured yet. Click "Add New Table" to set up your restaurant seating.</p>
                </div>
            <?php else: ?>
            <div class="es-table-cards-grid">
                <?php foreach ($tables as $t): ?>
                <div class="es-table-card <?php echo $t->is_active ? '' : 'es-table-inactive'; ?>">
                    <div class="es-table-card-header">
                        <span class="es-table-number">Table <?php echo esc_html($t->table_number); ?></span>
                        <span class="es-status <?php echo $t->is_active ? 'es-status-confirmed' : 'es-status-cancelled'; ?>">
                            <?php echo $t->is_active ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="es-table-card-name"><?php echo esc_html($t->name); ?></div>
                    <div class="es-table-card-meta">
                        <div><strong>🪑 Seating Capacity:</strong> <?php echo intval($t->capacity); ?> Covers</div>
                        <div><strong>📍 Dining Section:</strong> <?php echo esc_html($t->location); ?></div>
                    </div>
                    <div class="es-table-card-actions">
                        <button class="es-btn es-btn-sm es-btn-secondary js-edit-table"
                            data-id="<?php echo $t->id; ?>"
                            data-number="<?php echo esc_attr($t->table_number); ?>"
                            data-name="<?php echo esc_attr($t->name); ?>"
                            data-capacity="<?php echo $t->capacity; ?>"
                            data-location="<?php echo esc_attr($t->location); ?>"
                            data-active="<?php echo $t->is_active; ?>">
                            Edit Table
                        </button>
                        <button class="es-btn es-btn-sm es-btn-ghost js-delete-table" data-id="<?php echo $t->id; ?>" data-name="<?php echo esc_attr($t->name); ?>">
                            Remove
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Table Modal -->
<div id="es-table-modal" class="es-modal-overlay" style="display:none;">
    <div class="es-modal es-modal-sm">
        <div class="es-modal-header">
            <h3 id="table-modal-title">Add Dining Table</h3>
            <button class="es-modal-close" data-target="es-table-modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="table-modal-id" value="0">
            <div class="es-form-row">
                <div class="es-form-group">
                    <label class="es-label">Table Number / Code <span class="es-required">*</span></label>
                    <input type="text" id="table-modal-number" class="es-input" placeholder="e.g. T-11">
                </div>
                <div class="es-form-group">
                    <label class="es-label">Seating Capacity <span class="es-required">*</span></label>
                    <input type="number" id="table-modal-capacity" class="es-input" min="1" max="30" value="4">
                </div>
            </div>
            <div class="es-form-group">
                <label class="es-label">Table Name / Description <span class="es-required">*</span></label>
                <input type="text" id="table-modal-name" class="es-input" placeholder="e.g. Garden Patio Table 11">
            </div>
            <div class="es-form-group">
                <label class="es-label">Restaurant Section</label>
                <select id="table-modal-location" class="es-select">
                    <option value="Main Dining Room">Main Dining Room</option>
                    <option value="Garden Patio">Garden Patio</option>
                    <option value="Hearth Lounge">Hearth Lounge</option>
                    <option value="Private Dining Suite">Private Dining Suite</option>
                </select>
            </div>
            <div class="es-form-group" style="margin-top: 10px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--es-forest);cursor:pointer;">
                    <input type="checkbox" id="table-modal-active" checked style="accent-color:var(--es-forest);"> Table is Active and Available for Guest Bookings
                </label>
            </div>
            <div id="table-modal-error" class="es-alert es-alert-error" style="display:none;"></div>
            <div id="table-modal-success" class="es-alert es-alert-success" style="display:none;"></div>
            <div class="es-modal-footer">
                <button id="table-modal-submit" class="es-btn es-btn-primary">Save Table Settings</button>
                <button class="es-btn es-btn-ghost es-modal-close-btn" data-target="es-table-modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
