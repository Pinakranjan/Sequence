@php
    // Reusable Grid Filter Modal Component
    // Usage in a page:
    //   @include('components.grid_filter')
    //   Then in page JS:
    //   const gf = AppUtils.GridFilter.init({
    //     modalId: 'gridFilterModal',
    //     buttonId: 'toolbarFilter',
    //     storageKey: 'grid.products.filters',
    //     defaults: { preset: 'last30', includeActive: true, includeInactive: true, includeExpired: true, includeNotExpired: true },
    //     dateField: 'tare_dt',
    //     expiryField: 'expiry_dt',
    //     activeField: 'is_active',
    //     onApply: () => { /* reload + apply filters */ }
    //   });
@endphp

<!-- Common Grid Filter Modal -->
<div class="modal fade" id="gridFilterModal" tabindex="-1" aria-labelledby="gridFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:540px;">
        <div class="modal-content">
            <div class="modal-header py-2" style="min-height:42px;">
                <h5 class="modal-title fs-6" id="gridFilterModalLabel">
                    <i class="fa-solid fa-filter me-2"></i>
                    <span>Filter</span>
                    <small id="gridFilterPresetLabel" class="ms-1"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Date Range</label>
                    <div class="input-group">
                        <input type="text" id="gridFilterRange" class="form-control text-center" autocomplete="off" placeholder="Select date range" readonly />
                        <button type="button" id="gridFilterPresetBtn" class="btn btn-light border" data-bs-toggle="dropdown" title="Presets" aria-label="Presets">
                            <i class="fa-solid fa-calendar-days" id="gridFilterPresetIcon"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="gridFilterPresetMenu">
                            <li><a class="dropdown-item" data-preset="thisweek" href="#">This Week</a></li>
                            <li><a class="dropdown-item" data-preset="thismonth" href="#">This Month</a></li>
                            <li><a class="dropdown-item" data-preset="lastmonth" href="#">Last Month</a></li>
                            <li><a class="dropdown-item" data-preset="lastyear" href="#">Last Year</a></li>
                            <li><a class="dropdown-item" data-preset="last30" href="#">Last 30 Days</a></li>
                            <li><hr class="dropdown-divider"/></li>
                            <li><a class="dropdown-item" data-preset="custom" href="#">Custom Range</a></li>
                        </ul>
                    </div>
                    <div id="gridFilterRangeError" class="form-text text-danger d-none">Select date range</div>
                    
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gf_active" checked>
                            <label class="form-check-label" for="gf_active">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gf_inactive" checked>
                            <label class="form-check-label" for="gf_inactive">Inactive</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Expiry</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gf_notexpired" checked>
                            <label class="form-check-label" for="gf_notexpired">Not Expired</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="gf_expired" checked>
                            <label class="form-check-label" for="gf_expired">Expired</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" id="gridFilterResetBtn">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset
                </button>
                <div>
                    <button type="button" class="btn btn-outline-danger me-2" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="gridFilterApplyBtn">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    'use strict';
    // Lightweight date helpers (consistent local dates)
    function startOfDay(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
    function addDays(d, n){ const x = new Date(d); x.setDate(x.getDate()+n); return x; }
    function endOfDay(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 23,59,59,999); }
    function ymd(d){ const y=d.getFullYear(); const m=('0'+(d.getMonth()+1)).slice(-2); const dd=('0'+d.getDate()).slice(-2); return y+'-'+m+'-'+dd; }
    function displayDMY(d){ const dd=('0'+d.getDate()).slice(-2); const mm=('0'+(d.getMonth()+1)).slice(-2); const yy=String(d.getFullYear()).slice(-2); return dd+'-'+mm+'-'+yy; }

    function computePreset(preset){
        const today = startOfDay(new Date());
        const dow = today.getDay(); // 0 Sun .. 6 Sat
        const monOffset = (dow === 0 ? -6 : (1 - dow));
        const presets = {
            thisweek: { from: addDays(today, monOffset), to: today },
            thismonth: { from: new Date(today.getFullYear(), today.getMonth(), 1), to: today },
            lastmonth: (function(){ const s = new Date(today.getFullYear(), today.getMonth()-1, 1); const e = new Date(today.getFullYear(), today.getMonth(), 0); return { from: s, to: e }; })(),
            lastyear: { from: new Date(today.getFullYear()-1,0,1), to: new Date(today.getFullYear()-1,11,31) },
            last30: { from: addDays(today, -29), to: today },
            custom: null
        };
        return presets[(preset||'').toLowerCase()] || presets.last30;
    }

    function parseRangeValue(value){
        if (!value) return null;
        const parts = String(value).split('to').map(s=>s.trim());
        if (parts.length !== 2) return null;
        const a = new Date(parts[0]); const b = new Date(parts[1]);
        if (isNaN(a) || isNaN(b)) return null;
        return { from: startOfDay(a), to: endOfDay(b) };
    }

    function ensureAtLeastOneCheckbox(a, b){
        function attach(x, y){
            if (!x || !y) return; // guard against missing elements
            x.addEventListener('change', function(){
                // If user unchecked the only-checked option, flip to the other
                if (!a.checked && !b.checked) {
                    y.checked = true;
                    try { y.dispatchEvent(new Event('change', { bubbles: true })); } catch(_) {}
                }
            });
        }
        attach(a, b); attach(b, a);
    }

    function normalizeState(s){
        const def = { preset: 'last30', includeActive: true, includeInactive: true, includeExpired: true, includeNotExpired: true };
        s = s || {};
        return {
            preset: s.preset || def.preset,
            from: s.from ? new Date(s.from) : null,
            to: s.to ? new Date(s.to) : null,
            includeActive: (s.includeActive !== false),
            includeInactive: (s.includeInactive !== false),
            includeExpired: (s.includeExpired !== false),
            includeNotExpired: (s.includeNotExpired !== false)
        };
    }

    window.AppUtils = window.AppUtils || {};
    window.AppUtils.GridFilter = {
        init(options){
            const cfg = Object.assign({
                modalId: 'gridFilterModal',
                buttonId: 'toolbarFilter',
                storageKey: 'grid.filters',
                defaults: { preset: 'last30', includeActive: true, includeInactive: true, includeExpired: true, includeNotExpired: true },
                dateField: 'created_at', // field to compare range
                expiryField: 'expiry_dt',
                activeField: 'is_active',
                onApply: null
            }, options || {});

            const els = {
                modal: document.getElementById(cfg.modalId),
                range: document.getElementById('gridFilterRange'),
                presetLabel: document.getElementById('gridFilterPresetLabel'),
                presetMenu: document.getElementById('gridFilterPresetMenu'),
                presetBtn: document.getElementById('gridFilterPresetBtn'),
                presetIcon: document.getElementById('gridFilterPresetIcon'),
                rangeError: document.getElementById('gridFilterRangeError'),
                active: document.getElementById('gf_active'),
                inactive: document.getElementById('gf_inactive'),
                expired: document.getElementById('gf_expired'),
                notexpired: document.getElementById('gf_notexpired'),
                applyBtn: document.getElementById('gridFilterApplyBtn'),
                resetBtn: document.getElementById('gridFilterResetBtn'),
                toolbarBtn: document.getElementById(cfg.buttonId)
            };

            // Track current preset key to avoid relying on label text
            let currentPreset = 'last30';

            // Ensure checkbox constraints
            ensureAtLeastOneCheckbox(els.active, els.inactive);
            ensureAtLeastOneCheckbox(els.expired, els.notexpired);

            // Mutual toggle handled via change-event logic in ensureAtLeastOneCheckbox

            // Setup range picker (flatpickr if available)
            let fp = null;
            if (window.flatpickr && els.range) {
                fp = flatpickr(els.range, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-y',
                    allowInput: false,
                    disableMobile: true,
                    clickOpens: true,
                    onChange: function(selectedDates){
                        // Hide error once a full range is selected
                        try {
                            if (els.rangeError) {
                                const hasRange = Array.isArray(selectedDates) && selectedDates.length >= 2;
                                if (hasRange) els.rangeError.classList.add('d-none');
                            }
                            if (els.range) els.range.classList.remove('is-invalid');
                        } catch(_) {}
                    }
                });
            }

            function save(state){
                try {
                    localStorage.setItem(cfg.storageKey, JSON.stringify(state || {}));
                } catch(_) {}
            }
            function load(){ try { const j = JSON.parse(localStorage.getItem(cfg.storageKey)||'null'); return j || null; } catch(_) { return null; } }

            function setPresetLabel(p){
                if (!els.presetLabel) return;
                const map = { thisweek: 'This Week', thismonth: 'This Month', lastmonth: 'Last Month', lastyear: 'Last Year', last30: 'Last 30 Days', custom: 'Custom Range' };
                const text = '(' + (map[p] || 'Last 30 Days') + ')';
                els.presetLabel.textContent = text;
                // Title/preset coloring: default (last30) regular; non-default blue
                const isDefault = (p === 'last30' || p === 'last30days');
                els.presetLabel.classList.toggle('text-primary', !isDefault);
                if (els.presetIcon) els.presetIcon.classList.toggle('text-primary', !isDefault);
            }

            function setRange(from, to){
                if (fp) {
                    fp.setDate([from, to], true, 'Y-m-d');
                } else if (els.range) {
                    els.range.value = ymd(from) + ' to ' + ymd(to);
                }
            }

            function getRange(){
                let from=null, to=null;
                if (fp) {
                    const d = fp.selectedDates || [];
                    if (d.length >= 2) { from = startOfDay(d[0]); to = endOfDay(d[1]); }
                } else if (els.range && els.range.value) {
                    const p = parseRangeValue(els.range.value); if (p) { from = p.from; to = p.to; }
                }
                return { from, to };
            }

            function markActivePreset(p){
                try {
                    if (!els.presetMenu) return;
                    els.presetMenu.querySelectorAll('[data-preset]').forEach(a => a.classList.remove('active'));
                    const item = els.presetMenu.querySelector('[data-preset="' + p + '"]');
                    if (item) item.classList.add('active');
                } catch(_) {}
            }

            function setRangeEnabled(enabled){
                try {
                    if (!els.range) return;
                    els.range.disabled = !enabled;
                    if (fp) { fp.set('clickOpens', !!enabled); fp.set('allowInput', !!enabled); }
                    if (!enabled) {
                        // Clear any inline error when disabling
                        if (els.rangeError) els.rangeError.classList.add('d-none');
                        els.range.classList.remove('is-invalid');
                    }
                    // no badge to toggle
                } catch(_) {}
            }

            function applyPreset(preset){
                currentPreset = (preset || 'last30').toLowerCase();
                const pr = computePreset(currentPreset);
                if (pr) setRange(pr.from, pr.to);
                setPresetLabel(currentPreset);
                markActivePreset(currentPreset);
                setRangeEnabled(currentPreset === 'custom');
            }

            // Bind preset menu
            if (els.presetMenu) {
                els.presetMenu.addEventListener('click', function(e){
                    const a = e.target.closest('[data-preset]'); if (!a) return;
                    e.preventDefault();
                    const p = a.getAttribute('data-preset');
                    if (p === 'custom') {
                        applyPreset('custom');
                        // Clear any preselected range for custom to let user pick
                        try { if (fp) fp.clear(); else if (els.range) els.range.value = ''; } catch(_) {}
                        // Do not show error until user attempts to Apply
                        if (els.rangeError) els.rangeError.classList.add('d-none');
                        if (els.range) els.range.classList.remove('is-invalid');
                        if (els.range) els.range.focus();
                        return;
                    }
                    applyPreset(p);
                    // Switching away from custom clears error state
                    if (els.rangeError) els.rangeError.classList.add('d-none');
                    if (els.range) els.range.classList.remove('is-invalid');
                });
            }

            // Restore state or defaults
            const saved = normalizeState(load() || cfg.defaults || {});
            const preset = (saved.preset || 'last30');
            currentPreset = (preset || 'last30').toLowerCase();
            els.active.checked = !!saved.includeActive;
            els.inactive.checked = !!saved.includeInactive;
            els.expired.checked = !!saved.includeExpired;
            els.notexpired.checked = !!saved.includeNotExpired;
            if (saved.from && saved.to) {
                setRange(saved.from, saved.to);
                setPresetLabel(currentPreset);
                markActivePreset(currentPreset);
                setRangeEnabled(currentPreset === 'custom');
            }
            else { applyPreset(currentPreset); }

            // Initialize toolbar button color from saved state (non-default => active)
            try { const stNow = { preset: currentPreset, includeActive: els.active.checked, includeInactive: els.inactive.checked, includeExpired: els.expired.checked, includeNotExpired: els.notexpired.checked }; setButtonActive(!isDefaultState(stNow)); } catch(_) {}

            function getState(){
                const r = getRange();
                return {
                    preset: currentPreset,
                    from: r.from ? ymd(r.from) : null,
                    to: r.to ? ymd(r.to) : null,
                    includeActive: !!els.active.checked,
                    includeInactive: !!els.inactive.checked,
                    includeExpired: !!els.expired.checked,
                    includeNotExpired: !!els.notexpired.checked
                };
            }

            function isDefaultState(st){
                const presetKey = (st && st.preset) ? String(st.preset).toLowerCase() : '';
                const isDefaultPreset = presetKey === 'last30days' || presetKey === 'last30';
                const allChecked = !!(st && st.includeActive && st.includeInactive && st.includeExpired && st.includeNotExpired);
                return isDefaultPreset && allChecked;
            }

            function setButtonActive(active){ try { if (window.AppUtils && typeof AppUtils.__setFilterButtonActive === 'function') AppUtils.__setFilterButtonActive(active); else if (els.toolbarBtn) els.toolbarBtn.classList.toggle('filter-active', !!active); } catch(_) {} }

            // Apply (with unchanged detection)
            if (els.applyBtn) {
                els.applyBtn.addEventListener('click', function(){
                    const st = getState();
                    // Validate: If Custom Range selected, a valid range must be chosen
                    if ((st.preset === 'custom') && (!st.from || !st.to)) {
                        try {
                            if (els.rangeError) els.rangeError.classList.remove('d-none');
                            if (els.range) els.range.classList.add('is-invalid');
                        } catch(_) {}
                        return; // do not save/close/apply
                    }

                    // Detect unchanged state vs previously saved (previous saved values are raw strings, NOT Date objects)
                    const prevRawLoaded = load() || cfg.defaults || {};
                    const prev = {
                        preset: String(prevRawLoaded.preset || 'last30').toLowerCase(),
                        from: prevRawLoaded.from ? String(prevRawLoaded.from) : null,
                        to: prevRawLoaded.to ? String(prevRawLoaded.to) : null,
                        includeActive: (prevRawLoaded.includeActive !== false),
                        includeInactive: (prevRawLoaded.includeInactive !== false),
                        includeExpired: (prevRawLoaded.includeExpired !== false),
                        includeNotExpired: (prevRawLoaded.includeNotExpired !== false)
                    };
                    const curr = {
                        preset: String(st.preset || 'last30').toLowerCase(),
                        from: st.from ? String(st.from) : null,
                        to: st.to ? String(st.to) : null,
                        includeActive: !!st.includeActive,
                        includeInactive: !!st.includeInactive,
                        includeExpired: !!st.includeExpired,
                        includeNotExpired: !!st.includeNotExpired
                    };
                    const unchanged = (curr.preset === prev.preset
                        && curr.from === prev.from
                        && curr.to === prev.to
                        && curr.includeActive === prev.includeActive
                        && curr.includeInactive === prev.includeInactive
                        && curr.includeExpired === prev.includeExpired
                        && curr.includeNotExpired === prev.includeNotExpired);
                    if (unchanged) {
                        // Unchanged – notify as a warning in bottom-right and keep modal open
                        try {
                            if (window.toastr) {
                                try { toastr.options = Object.assign({}, toastr.options || {}, { positionClass: 'toast-bottom-right', timeOut: 3000, closeButton: true }); } catch(_) {}
                                toastr.warning('No filter changes to apply.', 'Filter Unchanged');
                            } else if (window.AppUtils && typeof AppUtils.notify === 'function') {
                                // Prefer a warning type if AppUtils supports it; position may depend on implementation
                                AppUtils.notify('No filter changes to apply.', { type: 'warning' });
                            } else {
                                alert('Filter unchanged – no changes to apply.');
                            }
                        } catch(_) {}
                        // Minor visual cue if custom unchanged
                        try { if (st.preset === 'custom' && els.range) els.range.classList.add('is-invalid'); } catch(_) {}
                        return;
                    }

                    save(st);
                    const nonDefault = !isDefaultState(st);
                    setButtonActive(nonDefault);
                    setPresetLabel(st.preset || 'last30');
                    try { if (typeof cfg.onApply === 'function') cfg.onApply(st); } catch(_) {}
                    try { bootstrap.Modal.getOrCreateInstance(els.modal).hide(); } catch(_) {}
                });
            }

            // Reset (sets defaults and triggers onApply)
            if (els.resetBtn) {
                els.resetBtn.addEventListener('click', function(){
                    const def = normalizeState(cfg.defaults || {});
                    applyPreset(def.preset || 'last30');
                    els.active.checked = !!def.includeActive;
                    els.inactive.checked = !!def.includeInactive;
                    els.expired.checked = !!def.includeExpired;
                    els.notexpired.checked = !!def.includeNotExpired;
                    const st = getState();
                    save(st);
                    setButtonActive(false);
                    setPresetLabel(st.preset || 'last30');
                    try { if (typeof cfg.onApply === 'function') cfg.onApply(st); } catch(_) {}
                    try { bootstrap.Modal.getOrCreateInstance(els.modal).hide(); } catch(_) {}
                });
            }

            // Clicking toolbar button opens modal
            if (els.toolbarBtn && els.modal) {
                els.toolbarBtn.addEventListener('click', function(){
                    bootstrap.Modal.getOrCreateInstance(els.modal).show();
                });
            }

            // Restore UI from saved state whenever the modal opens (so closing without Apply doesn't persist)
            function restoreUiFromSaved(){
                const s = normalizeState(load() || cfg.defaults || {});
                currentPreset = (s.preset || 'last30').toLowerCase();
                els.active.checked = !!s.includeActive;
                els.inactive.checked = !!s.includeInactive;
                els.expired.checked = !!s.includeExpired;
                els.notexpired.checked = !!s.includeNotExpired;
                // Clear any inline error on open
                try { if (els.rangeError) els.rangeError.classList.add('d-none'); if (els.range) els.range.classList.remove('is-invalid'); } catch(_) {}
                if (s.from && s.to) {
                    try { setRange(new Date(s.from), new Date(s.to)); } catch(_) {}
                } else {
                    // For non-custom presets, ensure range reflects preset
                    if (currentPreset !== 'custom') {
                        const pr = computePreset(currentPreset); if (pr) setRange(pr.from, pr.to);
                    } else {
                        // Clear range for custom until user selects
                        try { if (fp) fp.clear(); else if (els.range) els.range.value = ''; } catch(_) {}
                    }
                }
                setRangeEnabled(currentPreset === 'custom');
                setPresetLabel(currentPreset);
                markActivePreset(currentPreset);
            }

            try {
                els.modal.addEventListener('show.bs.modal', restoreUiFromSaved);
            } catch(_) {}

            // Expose instance helpers
            function isExpired(dateStr){
                if (!dateStr) return false;
                const d = new Date(dateStr); if (isNaN(d)) return false;
                const today = startOfDay(new Date());
                const x = startOfDay(d);
                return x < today;
            }

            function applyFilters(rows){
                const st = normalizeState(load() || cfg.defaults || {});
                const from = st.from ? startOfDay(new Date(st.from)) : null;
                const to = st.to ? endOfDay(new Date(st.to)) : null;
                return (Array.isArray(rows) ? rows : []).filter(function(r){
                    // Date range check on configured field
                    try {
                        if (cfg.dateField && (from || to)) {
                            const raw = r[cfg.dateField];
                            if (raw) {
                                const d = (raw instanceof Date) ? raw : new Date(raw);
                                if (!isNaN(d)) {
                                    if (from && d < from) return false;
                                    if (to && d > to) return false;
                                }
                            }
                        }
                    } catch(_) {}

                    // Active/Inactive set
                    try {
                        const v = r[cfg.activeField];
                        const active = !!(v === true || v === 1 || v === '1');
                        if (active && !st.includeActive) return false;
                        if (!active && !st.includeInactive) return false;
                    } catch(_) {}

                    // Expired vs Not Expired based on expiry field
                    try {
                        const e = r[cfg.expiryField];
                        const exp = (function(){
                            if (!e) return false;
                            const dt = (e instanceof Date) ? e : new Date(e);
                            if (isNaN(dt)) return false;
                            const today = new Date(); const t0 = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                            const d0 = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
                            return d0 < t0;
                        })();
                        if (exp && !st.includeExpired) return false;
                        if (!exp && !st.includeNotExpired) return false;
                    } catch(_) {}

                    return true;
                });
            }

            function resetToDefaults(){
                const def = normalizeState(cfg.defaults || {});
                applyPreset(def.preset || 'last30');
                els.active.checked = !!def.includeActive;
                els.inactive.checked = !!def.includeInactive;
                els.expired.checked = !!def.includeExpired;
                els.notexpired.checked = !!def.includeNotExpired;
                const st = getState();
                save(st);
                setButtonActive(false);
                return st;
            }

            // Build query string for server-side filtering
            function buildQueryString(){
                const st = getState();
                const p = new URLSearchParams();
                if (st.from) p.set('from', st.from);
                if (st.to) p.set('to', st.to);
                p.set('active', st.includeActive ? '1' : '0');
                p.set('inactive', st.includeInactive ? '1' : '0');
                p.set('expired', st.includeExpired ? '1' : '0');
                p.set('notexpired', st.includeNotExpired ? '1' : '0');
                return p.toString();
            }

            return { getState, applyFilters, setButtonActive, applyPreset, resetToDefaults, isDefault: () => isDefaultState(getState()), buildQueryString };
        }
    };
})();
</script>

<style>
/* Show a tick for the active preset in the dropdown */
#gridFilterPresetMenu .dropdown-item.active::before {
    content: '✓';
    color: var(--bs-primary);
    margin-right: 8px;
}

/* Stronger disabled styling for the date input */
#gridFilterRange:disabled {
    background-color: #f1f5f9; /* slate-100 */
    color: #6b7280; /* slate-500 */
    opacity: 1; /* keep text readable */
    cursor: not-allowed;
}
#gridFilterRange.is-invalid {
    border-color: var(--bs-danger);
}
</style>
