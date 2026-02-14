@php
    // Parameters accepted when included:
    $__icon = $icon ?? 'file';
    $__title = $title ?? '';
    $__refreshId = $refreshId ?? 'btnGridRefresh';
    $__reloadIconId = $reloadIconId ?? 'reloadIcon';
    $__gridId = $gridId ?? null;
    $__bodyHeight = $bodyHeight ?? '500px';
    $__wrapCard = $wrapCard ?? false; // when true, render the outer <div class="card"> wrapper
    $__emptyEntity = $emptyEntity ?? null; // optional default entity name for empty record template
    $__hideAddButton = $hideAddButton ?? false; // when true, remove Add button from toolbar
    $__showFilter = $showFilter ?? false; // when true, include Filter button in toolbar
    $__showUserAvatar = $showUserAvatar ?? true; // show current user's avatar at header right
    $__avatarSize = $avatarSize ?? 32; // avatar size in px
    $__overlayMode = $overlayMode ?? 'card'; // 'card' = overlay inside card, 'page' = full viewport
@endphp

@if($__wrapCard)
    <div class="card flex-grow-1 d-flex flex-column" style="flex:1 1 auto;min-height:0;position:relative;">
@endif

    <div id="cardLoadingOverlay" aria-hidden="false" data-overlay-mode="{{ $__overlayMode }}"
        style="display:none;visibility:hidden;opacity:0;">
        <div class="loading-content-wrapper">
            <div class="loading-spinner" role="status" aria-live="polite">
                <svg width="64" height="64" viewBox="0 0 135 135" xmlns="http://www.w3.org/2000/svg"
                    fill="{{ config('services.theme.color') }}" aria-hidden="true">
                    <path
                        d="M67.447 58c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10zm9.448 9.447c0 5.523 4.477 10 10 10 5.522 0 10-4.477 10-10s-4.478-10-10-10c-5.523 0-10 4.477-10 10zm-9.448 9.448c-5.523 0-10 4.477-10 10 0 5.522 4.477 10 10 10s10-4.478 10-10c0-5.523-4.477-10-10-10zM58 67.447c0-5.523-4.477-10-10-10s-10 4.477-10 10 4.477 10 10 10 10-4.477 10-10z">
                        <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="-360 67 67"
                            dur="2.5s" repeatCount="indefinite" />
                    </path>
                    <path
                        d="M28.19 40.31c6.627 0 12-5.374 12-12 0-6.628-5.373-12-12-12-6.628 0-12 5.372-12 12 0 6.626 5.372 12 12 12zm30.72-19.825c4.686 4.687 12.284 4.687 16.97 0 4.686-4.686 4.686-12.284 0-16.97-4.686-4.687-12.284-4.687-16.97 0-4.687 4.686-4.687 12.284 0 16.97zm35.74 7.705c0 6.627 5.37 12 12 12 6.626 0 12-5.373 12-12 0-6.628-5.374-12-12-12-6.63 0-12 5.372-12 12zm19.822 30.72c-4.686 4.686-4.686 12.284 0 16.97 4.687 4.686 12.285 4.686 16.97 0 4.687-4.686 4.687-12.284 0-16.97-4.685-4.687-12.283-4.687-16.97 0zm-7.704 35.74c-6.627 0-12 5.37-12 12 0 6.626 5.373 12 12 12s12-5.374 12-12c0-6.63-5.373-12-12-12zm-30.72 19.822c-4.686-4.686-12.284-4.686-16.97 0-4.686 4.687-4.686 12.285 0 16.97 4.686 4.687 12.284 4.687 16.97 0 4.687-4.685 4.687-12.283 0-16.97zm-35.74-7.704c0-6.627-5.372-12-12-12-6.626 0-12 5.373-12 12s5.374 12 12 12c6.628 0 12-5.373 12-12zm-19.823-30.72c4.687-4.686 4.687-12.284 0-16.97-4.686-4.686-12.284-4.686-16.97 0-4.687 4.686-4.687 12.284 0 16.97 4.686 4.687 12.284 4.687 16.97 0z">
                        <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="360 67 67" dur="8s"
                            repeatCount="indefinite" />
                    </path>
                </svg>
                <span class="loading-text"
                    style="margin-top:8px;font-size:0.9rem;color:var(--bs-body-color,#212529);">Loading...</span>
            </div>
        </div>
    </div>

    @if($__title)
        @php
            $authUser = Auth::user();
            $raw = $authUser?->photo;
            // Prefer model accessor if available (already S3-aware)
            $avatarUrl = $authUser && isset($authUser->photo_url) ? $authUser->photo_url : null;
            if (!$avatarUrl) {
                if (!empty($raw)) {
                    $key = 'upload/user_images/' . ltrim((string) $raw, '/');
                    if ((bool) env('AWS_SIGNED_URLS', false)) {
                        try {
                            $disk = \Illuminate\Support\Facades\Storage::disk('s3');
                            if (is_callable([$disk, 'temporaryUrl'])) {
                                $avatarUrl = (string) call_user_func([$disk, 'temporaryUrl'], $key, now()->addMinutes((int) env('AWS_SIGNED_URL_TTL', 60)));
                            } else {
                                $avatarUrl = (string) url($key);
                            }
                        } catch (\Throwable $e) {
                            $avatarUrl = (string) url($key);
                        }
                    } else {
                        $base = (string) (config('filesystems.disks.s3.url') ?? '');
                        $avatarUrl = $base !== '' ? rtrim($base, '/') . '/' . ltrim($key, '/') : (string) url($key);
                    }
                } else {
                    $avatarUrl = (string) url('upload/no_image.jpg');
                }
            }
            $authName = $authUser?->name ?? 'User';
        @endphp
        <div class="card-header d-flex align-items-center justify-content-between py-2" style="min-height:48px;">
            <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                @php
                    // Support auto-detection of Font Awesome or manual via iconClass/iconIsFa.
                    $__isFa = ($iconIsFa ?? false) || Str::startsWith($__icon, 'fa');
                    $__finalIcon = ($__isFa && !($iconClass ?? false)) ? $__icon : ($iconClass ?? null);
                @endphp
                @if($__isFa)
                    <i class="{{ $__finalIcon ?? $__icon }} me-2" style="font-size:18px; width:18px; text-align:center;"></i>
                @else
                    <i data-feather="{{ $__icon }}" class="me-2" style="width:18px;height:18px;"></i>
                @endif
                <span class="d-inline-block">{{ $__title }}</span>
                <button type="button" class="btn btn-outline-success btn-sm p-0 ms-2" id="{{ $__refreshId }}"
                    title="Reload Data" aria-label="Reload"
                    style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;">
                    <i data-feather="refresh-ccw" id="{{ $__reloadIconId }}" style="width:18px;height:18px;"></i>
                </button>
            </h5>

            @if($__showUserAvatar)
                <div class="d-flex align-items-center gap-2">
                    <img src="{{ $avatarUrl }}" alt="{{ $authName }}" class="rounded-circle"
                        style="width:{{ $__avatarSize }}px;height:{{ $__avatarSize }}px;object-fit:cover;"
                        title="{{ $authName }}" />
                </div>
            @endif
        </div>
    @endif

    @if($__gridId)
        <div class="card-body position-relative" style="height:{{ $__bodyHeight }};min-height:0;">
            <div id="{{ $__gridId }}" style="height:100%;min-height:0;"></div>
        </div>
    @endif

    <script>
        (function () {
            'use strict';

            // Shared helper: format Date objects (or parseable date strings) for export/display
            // Returns empty string for falsy input, otherwise returns dd-MMM-yyyy hh:mm:ss AM/PM
            window.formatDateForExport = function (value) {
                if (!value) return '';
                const d = (value instanceof Date) ? value : new Date(value);
                if (isNaN(d.getTime())) return String(value);
                const day = String(d.getDate()).padStart(2, '0');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const mon = months[d.getMonth()];
                const year = d.getFullYear();
                let hours = d.getHours();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12; hours = hours ? hours : 12;
                const hh = String(hours).padStart(2, '0');
                const mm = String(d.getMinutes()).padStart(2, '0');
                const ss = String(d.getSeconds()).padStart(2, '0');
                return `${day}-${mon}-${year} ${hh}:${mm}:${ss} ${ampm}`;
            };

            // AppUtils namespace (ensure exists)
            window.AppUtils = window.AppUtils || {};

            // ------- Small, reusable template helpers -------
            // HTML escape utility for safely rendering text content
            window.AppUtils.escapeHtml = function (str) {
                if (str == null) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            // Track last successful (non-error) URL for friendly 503 back navigation.
            // Any page that includes this shared script will store its current URL,
            // unless the response status was an error page.
            try {
                if (window.location && window.location.href) {
                    var path = window.location.pathname || '';
                    // Only track app pages, skip common error paths if any are routed separately
                    if (!/\/\d{3}$/.test(path)) {
                        try { sessionStorage.setItem('wg-last-ok-url', window.location.href); } catch (_) { }
                    }
                }
            } catch (_) { }

            // Render star icons for ratings (0-5). Returns an HTML string of 5 stars.
            window.AppUtils.renderStars = function (rawValue, size) {
                var rating = parseInt(rawValue || 0, 10);
                rating = isNaN(rating) ? 0 : Math.max(0, Math.min(5, rating));
                var out = '';
                var px = (size || 14);
                for (var i = 1; i <= 5; i++) {
                    out += '<span style="color:' + (i <= rating ? '#f59e0b' : '#d1d5db') + '; font-size:' + px + 'px; line-height:1;">★</span>';
                }
                return out;
            };

            // Compute an image URL from a row object. If relative, prefix with baseUrl.
            // Falls back to noImage when value missing. Accepts optional baseUrl/noImage overrides.
            window.AppUtils.computeImageUrl = function (row, baseUrl, noImage) {
                var val = (row && (row.image_url || row.image || row.photo || row.avatar)) || '';
                var fallback = (typeof noImage === 'string' && noImage) ? noImage : '';
                if (!val) return fallback;
                if (/^https?:\/\//i.test(val)) return val;
                if (val[0] === '/') return val;
                var base = (typeof baseUrl === 'string' && baseUrl) ? baseUrl : (window.location ? window.location.origin : '');
                return String(base || '').replace(/\/$/, '') + '/' + String(val).replace(/^\//, '');
            };

            // ------- Common overlay helpers and constants -------
            // Minimum time (ms) the overlay should remain visible during any fetch
            window.AppUtils.MIN_FETCH_TIME = 500; // 0.5 seconds (can be overridden per page)

            // Get the default overlay element used by shared card wrapper
            function getDefaultOverlay() {
                return document.getElementById('cardLoadingOverlay');
            }

            // Show the loading overlay (optionally pass a specific element)
            window.AppUtils.showOverlay = function (el) {
                const overlay = el || getDefaultOverlay();
                if (!overlay) return;
                overlay.style.display = 'flex';
                overlay.style.visibility = 'visible';
                overlay.style.opacity = '1';
                overlay.setAttribute('aria-hidden', 'false');
            };

            // Hide the loading overlay (optionally pass a specific element)
            window.AppUtils.hideOverlay = function (el) {
                const overlay = el || getDefaultOverlay();
                if (!overlay) return;
                overlay.style.display = 'none';
                overlay.style.visibility = 'hidden';
                overlay.style.opacity = '0';
                overlay.setAttribute('aria-hidden', 'true');
            };

            // ------- Audit info helpers (Created/Updated left in modal footer) -------
            // Provides a tiny controller bound to three DOM nodes: wrapper, created span, updated span.
            // Usage on a page:
            //   const ctrl = AppUtils.AuditInfo.make('#auditInfoWrap', '#createdInfo', '#updatedInfo');
            //   ctrl.set({ createdAt: row.created_at, updatedAt: row.updated_at, createdBy: row.created_by, updatedBy: row.updated_by });
            //   ctrl.showFor('edit'|'view'|'create');
            (function () {
                function toEl(x) { return (typeof x === 'string') ? document.querySelector(x) : x; }
                function fmt(dt) {
                    try {
                        if (!dt) return '';
                        const d = (dt instanceof Date) ? dt : new Date(dt);
                        return new Intl.DateTimeFormat(undefined, {
                            day: '2-digit', month: 'short', year: 'numeric',
                            hour: '2-digit', minute: '2-digit', hour12: true
                        }).format(d);
                    } catch (e) { return String(dt || ''); }
                }

                function make(wrapEl, createdEl, updatedEl) {
                    const wrap = toEl(wrapEl);
                    const cEl = toEl(createdEl);
                    const uEl = toEl(updatedEl);
                    const uWrap = uEl ? uEl.parentElement : null;

                    function set({ createdAt, updatedAt, createdBy, updatedBy } = {}) {
                        if (!cEl || !uEl) return;
                        const createdText = createdBy ? `${createdBy} - ${fmt(createdAt)}` : (createdAt ? fmt(createdAt) : '');
                        cEl.textContent = createdText;

                        // Show/hide the created row (icon + text) only when we have created info
                        try {
                            const createdRow = cEl.parentElement;
                            if (createdRow) {
                                createdRow.style.display = createdText ? '' : 'none';
                            }
                        } catch (_) { }

                        const showUpdated = !!updatedAt;
                        const updatedText = updatedBy ? `${updatedBy} - ${fmt(updatedAt)}` : (showUpdated ? fmt(updatedAt) : '');
                        uEl.textContent = updatedText;

                        if (uWrap) uWrap.style.display = showUpdated ? '' : 'none';
                    }

                    function showFor(mode) {
                        if (!wrap) return;
                        wrap.style.display = (mode === 'create') ? 'none' : '';
                    }

                    return { set, showFor };
                }

                function init(mount) {
                    const mountEl = toEl(mount);
                    if (!mountEl) return null;
                    const tpl = document.getElementById('auditInfoTemplate');
                    if (!tpl || !tpl.content) return null;
                    const clone = tpl.content.cloneNode(true);
                    mountEl.innerHTML = '';
                    mountEl.appendChild(clone);
                    const wrap = mountEl.querySelector('#auditInfoWrap');
                    const cEl = mountEl.querySelector('#createdInfo');
                    const uEl = mountEl.querySelector('#updatedInfo');
                    const ctrl = make(wrap, cEl, uEl);
                    try { if (window.feather) feather.replace(); } catch (_) { }
                    return ctrl;
                }

                window.AppUtils.AuditInfo = { make, init };
            })();

            // Returns an HTML string used as Syncfusion Grid's emptyRecordTemplate.
            window.AppUtils.emptyRecordTemplate = function (entityName) {
                entityName = entityName || @json($__emptyEntity ?? 'Items');
                function pluralizeName(name) {
                    if (!name) return '';

                    var s = String(name).trim();
                    // Remove trailing " Register" (case-insensitive)
                    s = s.replace(/\s*Register$/i, '').trim();
                    if (!s) return '';

                    var parts = s.split(' ');
                    var last = parts.pop();
                    var pluralLast;
                    if (/y$/i.test(last)) {
                        pluralLast = last.replace(/y$/i, 'ies');
                    } else if (/s$/i.test(last)) {
                        pluralLast = last; // already plural-like
                    } else {
                        pluralLast = last + 's';
                    }

                    parts.push(pluralLast);
                    return parts.join(' ');
                }

                var pluralEntity = pluralizeName(entityName);
                var title = 'No ' + pluralEntity + ' Found!';
                var message = 'Add your first ' + (String(entityName).toLowerCase().replace(/\s*Register$/i, '').trim()) + ' to get started';
                return '<div style="text-align:center;padding:40px 20px;">' +
                    '<div style="font-size:48px;color:#ccc;margin-bottom:16px;">📋</div>' +
                    '<h5 style="color:#6c757d;margin-bottom:8px;">' + title + '</h5>' +
                    '<p style="color:#adb5bd;margin:0;">' + message + '</p>' +
                    '</div>';
            };

            // Backwards-compatible global function
            window.emptyRecordTemplate = window.AppUtils.emptyRecordTemplate;

            // ------- Common Business Picker (Super Admin) -------
            // Renders a dropdown in the page header to select a business and notifies the page on changes.
            // Usage:
            // AppUtils.BusinessPicker.init({
            //   headerSelector: '.card-header.d-flex',
            //   noImageUrl: '.../upload/no_image.jpg',
            //   listUrl: '/businesses/list',
            //   addButtonHandler: (show) => { /* show/hide Add button */ },
            //   onBusinessChanged: (business, state) => { /* page-specific load */ },
            //   onClear: () => { /* page-specific reset */ }
            // });
            (function () {
                function q(sel) { return document.querySelector(sel); }
                function ensurePickerStyles() {
                    if (document.getElementById('businessPickerStyles')) return;
                    const style = document.createElement('style');
                    style.id = 'businessPickerStyles';
                    style.textContent = '\n.business-picker-btn { background-color:#fff; border-radius:999px; border-width:1px; transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }\n.business-picker-btn img { border-radius:50%; border:1px solid #e9ecef; background-color:#fff; }\n/* Empty state: keep white background with danger-colored outline/text (matches index page) */\n.business-picker-btn.business-picker-empty { background-color:#fff; color:#dc3545; border-color:#dc3545; box-shadow:0 1px 2px rgba(0,0,0,0.04); }\n.business-picker-btn.business-picker-empty:hover,\n.business-picker-btn.business-picker-empty:focus { background-color:rgba(220,53,69,0.24); color:#dc3545; border-color:#dc3545; box-shadow:0 6px 18px rgba(0,0,0,0.06); }\n.business-picker-btn.business-picker-empty:hover img,\n.business-picker-btn.business-picker-empty:focus img { border-color:rgba(220,53,69,0.18); }\n';
                    document.head.appendChild(style);
                }
                function ensureRightContainer(header) {
                    // Standardize on a generic container class for reuse across pages
                    var right = header.querySelector('.header-right');
                    if (!right) {
                        right = document.createElement('div');
                        right.className = 'd-flex align-items-center gap-2 header-right';
                        header.appendChild(right);
                    }
                    return right;
                }

                function setAddVisibility(handler, show) {
                    try { if (typeof handler === 'function') handler(!!show); } catch (_) { }
                }

                function buildDropdown({ container, buttonId, noImageUrl }) {
                    const wrap = document.createElement('div');
                    wrap.className = 'dropdown';
                    container.appendChild(wrap);

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.id = buttonId || 'businessPickerBtn';
                    // Default to danger outline when no business is selected; page can switch to secondary on selection
                    btn.className = 'btn btn-outline-danger btn-sm dropdown-toggle d-flex align-items-center';
                    btn.classList.add('business-picker-btn');
                    btn.setAttribute('data-bs-toggle', 'dropdown');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.innerHTML = '<img id="businessPickerBtnImg" src="' + (noImageUrl || '') + '" class="rounded-circle me-2" style="width:24px;height:24px;object-fit:cover;" />'
                        + '<span id="businessPickerBtnText" class="text-truncate" style="max-width: 200px;">Select a business</span>'
                        + '<span id="businessPickerBtnFlags" class="ms-2"></span>';
                    wrap.appendChild(btn);

                    const menu = document.createElement('div');
                    menu.className = 'dropdown-menu dropdown-menu-end p-0';
                    menu.id = (buttonId || 'businessPickerBtn') + 'Menu';
                    menu.innerHTML = '<div class="list-group list-group-flush" style="max-height: 300px; overflow:auto;"></div>';
                    wrap.appendChild(menu);

                    return { wrap, btn, menu };
                }

                function renderList({ menu, list, noImageUrl, onSelect }) {
                    const listEl = menu.querySelector('.list-group');
                    listEl.innerHTML = '';

                    const clearBtn = document.createElement('button');
                    clearBtn.type = 'button';
                    clearBtn.className = 'list-group-item list-group-item-action d-flex align-items-center';
                    clearBtn.innerHTML = '<i class="fa-solid fa-circle-xmark me-2 text-danger" style="font-size:16px;"></i><span>Clear selection</span>';
                    clearBtn.addEventListener('click', function () {
                        if (typeof onSelect === 'function') onSelect(null);
                    });
                    listEl.appendChild(clearBtn);

                    (list || []).forEach(function (c) {
                        const isActive = (c.is_active === 1 || c.is_active === true || c.is_active === '1');
                        const isLocked = (c.is_locked === 1 || c.is_locked === true || c.is_locked === '1');
                        const imgSrc = c.image_url || c.image || c.logo || noImageUrl || '';

                        const suffix = (isLocked ? ' <span class="ms-1">🔒</span>' : '') + (!isActive ? ' <span class="ms-1">⛔</span>' : '');
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action d-flex align-items-center';
                        item.innerHTML = '<img src="' + imgSrc + '" class="rounded-circle me-2" style="width:20px;height:20px;object-fit:cover;" />'
                            + '<span class="flex-grow-1 text-truncate" style="max-width: 260px;">' + (window.AppUtils && AppUtils.escapeHtml ? AppUtils.escapeHtml(String(c.name || 'Business')) : (String(c.name || 'Business'))) + '</span>'
                            + suffix;

                        item.addEventListener('click', function () {
                            if (typeof onSelect === 'function') onSelect(c);
                        });

                        listEl.appendChild(item);
                    });

                    try { if (window.feather) feather.replace(); } catch (_) { }
                }

                function hideDropdown(btn, menu) {
                    try {
                        if (window.bootstrap && bootstrap.Dropdown) {
                            const instance = bootstrap.Dropdown.getOrCreateInstance(btn);
                            instance.hide();
                            return;
                        }
                    } catch (_) { }

                    if (menu && menu.classList) {
                        menu.classList.remove('show');
                    }
                    if (btn && btn.setAttribute) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                }

                function init(options) {
                    options = options || {};

                    if (!options.listUrl) return null;

                    ensurePickerStyles();

                    const header = q(options.headerSelector || '.card-header.d-flex');

                    if (!header) return null;

                    const right = ensureRightContainer(header);
                    const { btn, menu } = buildDropdown({ container: right, buttonId: options.buttonId, noImageUrl: options.noImageUrl });
                    const btnText = btn.querySelector('#businessPickerBtnText');
                    const btnImg = btn.querySelector('#businessPickerBtnImg');
                    const btnFlags = btn.querySelector('#businessPickerBtnFlags');

                    let businessesCache = [];

                    function applySelection(business) {
                        const isActive = !!(business && (business.is_active === 1 || business.is_active === true || business.is_active === '1'));
                        const isLocked = !!(business && (business.is_locked === 1 || business.is_locked === true || business.is_locked === '1'));

                        if (business) {
                            btn.dataset.id = String(business.id || '');
                            btn.dataset.active = isActive ? '1' : '0';
                            btn.dataset.locked = isLocked ? '1' : '0';
                            if (btnText) btnText.textContent = String(business.name || 'Business');
                            if (btnImg) btnImg.src = business.image_url || business.image || business.logo || options.noImageUrl || '';
                            if (btnFlags) btnFlags.innerHTML = (isLocked ? '<span title="Locked">🔒</span>' : '') + (!isActive ? '<span class="ms-1" title="Inactive">⛔</span>' : '');
                            btn.classList.remove('btn-outline-danger');
                            btn.classList.add('btn-outline-secondary');
                            btn.classList.remove('business-picker-empty');
                        } else {
                            btn.dataset.id = '';
                            btn.dataset.active = '';
                            btn.dataset.locked = '';
                            if (btnText) btnText.textContent = 'Select a business';
                            if (btnImg) btnImg.src = options.noImageUrl || '';
                            if (btnFlags) btnFlags.innerHTML = '';
                            btn.classList.remove('btn-outline-secondary');
                            btn.classList.add('btn-outline-danger');
                            btn.classList.add('business-picker-empty');
                        }

                        return { isActive, isLocked };
                    }

                    function handleSelection(business, opts) {
                        const silent = !!(opts && opts.silent);
                        hideDropdown(btn, menu);
                        const state = applySelection(business);
                        const allowAdd = !!(business && state.isActive && !state.isLocked);
                        setAddVisibility(options.addButtonHandler, allowAdd);

                        if (silent) {
                            return;
                        }

                        if (business) {
                            if (typeof options.onBusinessChanged === 'function') {
                                options.onBusinessChanged(business, { isActive: state.isActive, isLocked: state.isLocked, listUrl: options.listUrl });
                            }
                        } else if (typeof options.onClear === 'function') {
                            options.onClear();
                        }
                    }

                    // Ensure toolbar button is hidden initially
                    setAddVisibility(options.addButtonHandler, false);
                    applySelection(null);

                    try {
                        fetch(options.listUrl, { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json())
                            .then(arr => {
                                businessesCache = Array.isArray(arr) ? arr : [];

                                // Client-side sort: locale-aware, case-insensitive, numeric-aware
                                try {
                                    const collator = (typeof Intl !== 'undefined' && Intl.Collator) ? new Intl.Collator(undefined, { sensitivity: 'base', numeric: true }) : null;
                                    businessesCache.sort(function (a, b) {
                                        const an = a && a.name ? String(a.name) : '';
                                        const bn = b && b.name ? String(b.name) : '';
                                        if (collator) return collator.compare(an, bn);
                                        return an.localeCompare(bn, undefined, { sensitivity: 'base', numeric: true });
                                    });
                                } catch (_) {
                                    // If sorting fails for any reason, fall back to unsorted list
                                }

                                renderList({ menu, list: businessesCache, noImageUrl: options.noImageUrl, onSelect: function (business) { handleSelection(business); } });

                                if (options.selectedBusinessId) {
                                    const selected = businessesCache.find(c => Number(c.id) === Number(options.selectedBusinessId));
                                    if (selected) {
                                        handleSelection(selected, { silent: true });
                                    }
                                }
                            })
                            .catch(() => { /* ignore */ });
                    } catch (_) { }

                    return {
                        button: btn,
                        menu,
                        setSelectedBusinessId(id, opts) {
                            if (!businessesCache.length) return;
                            if (id == null) {
                                handleSelection(null, opts);
                                return;
                            }
                            const selected = businessesCache.find(c => Number(c.id) === Number(id));
                            handleSelection(selected || null, opts);
                        }
                    };
                }

                window.AppUtils.BusinessPicker = { init };
            })();

            // ------- Grid helpers (Syncfusion EJ2) -------
            window.AppUtils.GridHelpers = {
                // Global defaults for grid helpers (can be overridden once for all pages if ever needed)
                defaults: {
                    actionToggle: {
                        isActiveField: 'is_active',
                        openSelector: '.e-open',
                        closeSelector: '.e-close',
                        modifySelector: '.e-modify, .cmd-edit'
                    }
                },
                // Base/default grid options reused across pages
                baseGridOptions() {
                    return {
                        dataSource: [],
                        enablePersistence: false,
                        allowPaging: true,
                        allowResizing: false,
                        allowSorting: true,
                        autoFitColumns: true,
                        allowFiltering: true,
                        allowGrouping: false,
                        showColumnChooser: true,
                        filterSettings: { type: 'Excel' },
                        pageSettings: { pageSize: 10, pageSizes: [10, 25, 50, 100] },
                        height: '100%',
                        allowExcelExport: true,
                        allowPdfExport: true,
                        allowPrint: true,
                        toolbar: window.AppUtils && window.AppUtils.GridHelpers ? window.AppUtils.GridHelpers.standardToolbar() : []
                    };
                },

                // rowDataBound handler factory to toggle action buttons per row based on an active flag
                // Options:
                //   isActiveField: field name on data indicating active status (default 'is_active')
                //   openSelector: selector for the "Activate" button (default '.e-open')
                //   closeSelector: selector for the "Inactive" button (default '.e-close')
                //   modifySelector: selector(s) for the modify button (default '.e-modify, .cmd-edit')
                rowDataBoundToggleActionsFactory(options = {}) {
                    // Merge: hard defaults <- global defaults <- call-time overrides
                    var hard = { isActiveField: 'is_active', openSelector: '.e-open', closeSelector: '.e-close', modifySelector: '.e-modify, .cmd-edit' };
                    var globalDef = (window.AppUtils && window.AppUtils.GridHelpers && window.AppUtils.GridHelpers.defaults && window.AppUtils.GridHelpers.defaults.actionToggle) || {};
                    var cfg = Object.assign({}, hard, globalDef, (options || {}));
                    function isActiveValue(v) {
                        return !!(v === true || v === 1 || v === '1');
                    }
                    return function (args) {
                        try {
                            if (!args || !args.data || !args.row) return;
                            var d = args.data;
                            var active = isActiveValue(d && Object.prototype.hasOwnProperty.call(d, cfg.isActiveField) ? d[cfg.isActiveField] : d);

                            var openEl = args.row.querySelector(cfg.openSelector);
                            var closeEl = args.row.querySelector(cfg.closeSelector);
                            if (openEl && closeEl) {
                                if (active) {
                                    openEl.style.display = 'none';
                                    closeEl.style.display = '';
                                } else {
                                    closeEl.style.display = 'none';
                                    openEl.style.display = '';
                                }
                            }

                            var modifyEl = args.row.querySelector(cfg.modifySelector);
                            if (modifyEl) {
                                modifyEl.style.display = active ? '' : 'none';
                            }
                        } catch (_) { }
                    };
                },

                // Standard toolbar configuration used across grids
                standardToolbar() {
                    return [
                        'Search',
                        'ColumnChooser',
                        @if(!($__hideAddButton))
                            { text: '', tooltipText: 'Add', id: 'toolbarAdd', prefixIcon: 'e-icons e-circle-add' },
                        @endif
                        { text: '', tooltipText: 'Show Group By Header', id: 'toolbarGroupToggle', prefixIcon: 'e-icons e-group-2' },
                        { text: '', tooltipText: 'Resize: OFF (Autofit)', id: 'toolbarAutofitToggle', prefixIcon: 'e-icons e-auto-fit-window' },
                        { text: '', tooltipText: 'Print', id: 'toolbarPrint', prefixIcon: 'e-icons e-print' },
                        { text: '', tooltipText: 'Excel Export', id: 'toolbarExcelExport', prefixIcon: 'e-icons e-export-excel' },
                        { text: '', tooltipText: 'CSV Export', id: 'toolbarCsvExport', prefixIcon: 'e-icons e-export-csv' },
                        { text: '', tooltipText: 'PDF Export', id: 'toolbarPdfExport', prefixIcon: 'e-icons e-export-pdf' },
                        @if($__showFilter)
                            { text: '', tooltipText: 'Filter', id: 'toolbarFilter', prefixIcon: 'e-icons e-filter' },
                        @endif
                ];
                },

                // DataBound handler: if autofit is OFF, auto-fit columns after every data load
                dataBoundAutoFitFactory(gridGetter) {
                    return function () {
                        try {
                            const g = typeof gridGetter === 'function' ? gridGetter() : gridGetter;
                            if (!g) return;
                            if (!g._autofitEnabled && typeof g.autoFitColumns === 'function') {
                                g.autoFitColumns();
                            }
                        } catch (_) { }
                    };
                },

                // Utility: sanitize text for export (remove control chars, zero-width/bidi marks, smart punctuation, invalid surrogates, normalize newlines)
                sanitizeForExport(value, opts = {}) {
                    try {
                        if (value == null) return '';
                        let s = String(value);
                        const options = Object.assign({
                            stripHtml: true,
                            replaceNewlinesWith: ' ',
                            removeControlChars: true,
                            removeZeroWidth: true,
                            mapSmartPunctuation: true,
                            decodeBasicEntities: true,
                            removeInvalidSurrogates: true,
                            normalize: 'NFC',
                            trim: true
                        }, opts || {});

                        // Optionally strip HTML tags
                        if (options.stripHtml) {
                            s = s.replace(/<[^>]*>/g, '');
                        }
                        // Replace CR/LF with space (PDF exporter can choke on mixed linebreaks)
                        if (typeof options.replaceNewlinesWith === 'string') {
                            const rep = options.replaceNewlinesWith;
                            s = s.replace(/\r\n|\r|\n/g, rep);
                        }
                        // Decode a few common HTML entities (after tag stripping to keep things simple)
                        if (options.decodeBasicEntities) {
                            s = s
                                .replace(/&nbsp;/gi, ' ')
                                .replace(/&amp;/gi, '&')
                                .replace(/&lt;/gi, '<')
                                .replace(/&gt;/gi, '>')
                                .replace(/&quot;/gi, '"')
                                .replace(/&#39;/gi, "'");
                        }

                        // Remove ASCII control chars except tab if any slipped through
                        if (options.removeControlChars) {
                            s = s.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
                        }
                        // Remove zero-width and bidi/invisible formatting characters that can break PDF text rendering
                        // Includes: ZWSP/ZWNJ/ZWJ/WORD JOINER/BOM + LRM/RLM + LRE/RLE/PDF/LRO/RLO + isolates
                        if (options.removeZeroWidth) {
                            s = s.replace(/[\u200B-\u200D\u2060\uFEFF\u200E\u200F\u202A-\u202E\u2066-\u2069]/g, '');
                        }
                        // Map smart punctuation to ASCII to avoid glyph-missing issues with built-in PDF fonts
                        if (options.mapSmartPunctuation) {
                            s = s
                                .replace(/[\u2018\u2019\u201B\u2032]/g, "'") // single quotes, prime
                                .replace(/[\u201C\u201D\u2033]/g, '"') // double quotes, double prime
                                .replace(/[\u2013\u2014\u2212]/g, '-')   // en dash, em dash, minus
                                .replace(/[\u2026]/g, '...')              // ellipsis
                                .replace(/[\u00A0]/g, ' ');               // NBSP
                        }
                        // Replace invalid UTF-16 surrogate pairs which can break PDF text drawing
                        if (options.removeInvalidSurrogates) {
                            // Lone high surrogates (no following low)
                            s = s.replace(/[\uD800-\uDBFF](?![\uDC00-\uDFFF])/g, '�');
                            // Lone low surrogates (no preceding high). Avoid lookbehind for Safari compatibility.
                            s = s.replace(/(^|[^\uD800-\uDBFF])(\uDC00|[\uDC01-\uDFFF])/g, '$1�');
                        }
                        // Normalize unicode composition if available
                        try {
                            if (options.normalize && typeof s.normalize === 'function') {
                                s = s.normalize(options.normalize);
                            }
                        } catch (_) { }

                        if (options.trim) s = s.trim();
                        return s;
                    } catch (_) {
                        try { return String(value); } catch (__) { return ''; }
                    }
                },

                // pdfQueryCellInfo handler factory for formatting date fields in exports and sanitizing text fields
                pdfQueryCellInfoFactory({ dateFields = [], sanitizeFields = [], sanitizeOptions = {} } = {}) {
                    return function (args) {
                        try {
                            if (!args || !args.column) return;

                            const field = args.column.field;
                            if (!field) return;

                            // Normalize Active fields (boolean-ish) and template-like columns
                            if (field === 'is_active') {
                                const v = args.data && (args.data.is_active === true || args.data.is_active === 1 || args.data.is_active === '1');
                                // Only show Inactive; leave Active blank
                                args.value = v ? '' : 'Inactive';
                                return;
                            }
                            // Treat 'status' same as 'is_active' for export
                            if (field === 'status') {
                                const v = args.data && (args.data.status === true || args.data.status === 1 || args.data.status === '1');
                                args.value = v ? '' : 'Inactive';
                                return;
                            }
                            if (field === 'is_locked') {
                                const v = args.data && (args.data.is_locked === true || args.data.is_locked === 1 || args.data.is_locked === '1');
                                // Show Locked when true; blank when false
                                args.value = v ? 'Locked' : '';
                                return;
                            }

                            if (field === 'dmp') {
                                const v = args.data && (args.data.dmp === true || args.data.dmp === 1 || args.data.dmp === '1');
                                args.value = v ? '' : 'Yes';
                                return;
                            }

                            if (field === 'kg') {
                                const v = args.data && (args.data.kg === true || args.data.kg === 1 || args.data.kg === '1');
                                args.value = v ? '' : 'Yes';
                                return;
                            }

                            if (dateFields.includes(field)) {
                                args.value = window.formatDateForExport(args.value);
                                return;
                            }

                            if (sanitizeFields.includes(field)) {
                                const util = window.AppUtils && window.AppUtils.GridHelpers ? window.AppUtils.GridHelpers : null;
                                const s = util && typeof util.sanitizeForExport === 'function' ? util.sanitizeForExport(args.value, sanitizeOptions) : String(args.value || '');
                                args.value = s;
                                return;
                            }

                            // Fallback: if this is a template column and the value looks like HTML, sanitize to plain text
                            if (args.column.template && typeof args.value === 'string' && /<[^>]+>/.test(args.value)) {
                                const util = window.AppUtils && window.AppUtils.GridHelpers ? window.AppUtils.GridHelpers : null;
                                const s = util && typeof util.sanitizeForExport === 'function' ? util.sanitizeForExport(args.value, sanitizeOptions) : String(args.value || '');
                                args.value = s;
                                return;
                            }
                        } catch (_) { }
                    };
                },

                // excel/csv export cell formatter (mirrors PDF rules + sanitization)
                excelQueryCellInfoFactory({ dateFields = [], sanitizeFields = [], sanitizeOptions = {} } = {}) {
                    return function (args) {
                        try {
                            if (!args || !args.column) return;
                            const field = args.column.field;

                            if (!field) return;

                            if (field === 'is_active') {
                                const v = args.data && (args.data.is_active === true || args.data.is_active === 1 || args.data.is_active === '1');
                                // Only show Inactive; leave Active blank
                                args.value = v ? '' : 'Inactive';
                                return;
                            }
                            // Treat 'status' same as 'is_active' for export
                            if (field === 'status') {
                                const v = args.data && (args.data.status === true || args.data.status === 1 || args.data.status === '1');
                                args.value = v ? '' : 'Inactive';
                                return;
                            }
                            if (field === 'is_locked') {
                                const v = args.data && (args.data.is_locked === true || args.data.is_locked === 1 || args.data.is_locked === '1');
                                // Show Locked when true; blank when false
                                args.value = v ? 'Locked' : '';
                                return;
                            }

                            if (field === 'dmp') {
                                const v = args.data && (args.data.dmp === true || args.data.dmp === 1 || args.data.dmp === '1');
                                args.value = v ? '' : 'Yes';
                                return;
                            }

                            if (field === 'kg') {
                                const v = args.data && (args.data.kg === true || args.data.kg === 1 || args.data.kg === '1');
                                args.value = v ? '' : 'Yes';
                                return;
                            }

                            if (dateFields.includes(field)) {
                                args.value = window.formatDateForExport(args.value);
                                return;
                            }

                            if (sanitizeFields.includes(field)) {
                                const util = window.AppUtils && window.AppUtils.GridHelpers ? window.AppUtils.GridHelpers : null;
                                const s = util && typeof util.sanitizeForExport === 'function' ? util.sanitizeForExport(args.value, sanitizeOptions) : String(args.value || '');
                                args.value = s;
                                return;
                            }

                            // Fallback for template columns with HTML content
                            if (args.column.template && typeof args.value === 'string' && /<[^>]+>/.test(args.value)) {
                                const util = window.AppUtils && window.AppUtils.GridHelpers ? window.AppUtils.GridHelpers : null;
                                const s = util && typeof util.sanitizeForExport === 'function' ? util.sanitizeForExport(args.value, sanitizeOptions) : String(args.value || '');
                                args.value = s;
                                return;
                            }
                        } catch (_) { }
                    };
                },

                // Optional: beforePdfExport handler to attach PDF theme/fonts; if a custom TTF base64 is provided
                // options: { fontBase64?: string, fontSize?: number }
                beforePdfExportFactory({ fontBase64 = null, fontSize = 10 } = {}) {
                    return function (args) {
                        try {
                            if (!args) return;
                            if (fontBase64 && window.ej && ej.exporter && ej.exporter.PdfTrueTypeFont) {
                                const FontCtor = ej.exporter.PdfTrueTypeFont;
                                const font = new FontCtor(fontBase64, fontSize);
                                const theme = { header: { font: font }, record: { font: font }, caption: { font: font } };
                                args.exportProperties = Object.assign({}, (args.exportProperties || {}), { theme });
                            }
                        } catch (_) { /* silently continue if font embedding not available */ }
                    };
                },

                // actionBegin handler: capture search text for highlighting
                actionBeginSearchKeyUpdaterFactory(gridGetter) {
                    return function (args) {
                        try {
                            if (args && args.requestType === 'searching') {
                                const g = typeof gridGetter === 'function' ? gridGetter() : gridGetter;
                                if (!g) return;
                                g._searchKey = (args.searchString || '').toLowerCase();
                            }
                        } catch (_) { }
                    };
                },

                // queryCellInfo handler: highlight matched search text
                queryCellInfoHighlighterFactory(gridGetter) {
                    return function (args) {
                        try {
                            const g = typeof gridGetter === 'function' ? gridGetter() : gridGetter;
                            const key = (g && g._searchKey) ? g._searchKey : '';
                            if (!key) return;
                            // Skip highlighting on templated columns to avoid breaking custom HTML formatting
                            if (args && args.column && args.column.template) return;
                            const raw = args && args.data ? args.data[args.column.field] : undefined;
                            if (raw == null) return;
                            const cellText = String(raw);
                            const parsed = cellText.toLowerCase();
                            const needle = key.toLowerCase();
                            if (!needle || !parsed.includes(needle)) return;

                            // Replace occurrences in plain-text cells (non-template) using a case-insensitive regex
                            const esc = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const re = new RegExp(esc, 'gi');
                            const original = args.cell.innerText || '';
                            if (!original) return;
                            args.cell.innerHTML = original.replace(re, function (m) { return "<span class='customcss'>" + m + '</span>'; });
                        } catch (_) { }
                    };
                },

                // toolbarClick handler providing common actions for Group toggle, Autofit toggle and exports
                toolbarClickFactory(gridGetter) {
                    return function (args) {
                        const g = typeof gridGetter === 'function' ? gridGetter() : gridGetter;
                        if (!g) return;
                        const show = window.AppUtils.showOverlay;
                        const hide = window.AppUtils.hideOverlay;

                        if (args.item && args.item.id === 'toolbarFilter') {
                            try {
                                const modal = document.getElementById('gridFilterModal');
                                if (modal && window.bootstrap) { window.bootstrap.Modal.getOrCreateInstance(modal).show(); }
                            } catch (_) { }
                        } else if (args.item && args.item.id === 'toolbarGroupToggle') {
                            let isGrouped = g.allowGrouping;
                            if (!isGrouped) {
                                g.allowGrouping = true;
                                args.item.text = '';
                                args.item.tooltipText = 'Hide Group By Header';
                                args.item.prefixIcon = 'e-icons e-ungroup-1';
                            } else {
                                g.allowGrouping = false;
                                args.item.text = '';
                                args.item.tooltipText = 'Show Group By Header';
                                args.item.prefixIcon = 'e-icons e-group-2';
                            }
                        } else if (args.item && args.item.id === 'toolbarAutofitToggle') {
                            g._autofitEnabled = !g._autofitEnabled;
                            if (g._autofitEnabled) {
                                // ON: User can resize columns manually
                                g.allowResizing = true;
                                args.item.tooltipText = 'Resize: ON (Manual)';
                                args.item.prefixIcon = 'e-icons e-protect-workbook';
                            } else {
                                // OFF: Columns auto-fit
                                g.allowResizing = false;
                                args.item.tooltipText = 'Resize: OFF (Autofit)';
                                args.item.prefixIcon = 'e-icons e-auto-fit-window';
                                if (typeof g.autoFitColumns === 'function') {
                                    g.autoFitColumns();
                                }
                            }
                        } else if (args.item && (args.item.id === 'toolbarPrint' || args.item.id === 'toolbarExcelExport' || args.item.id === 'toolbarCsvExport' || args.item.id === 'toolbarPdfExport')) {
                            // Get command column index: prefer columns that actually define `commands`.
                            // Fallback to explicit title 'Manage Records' only if no command column is found.
                            function getCommandColumnIndex(grid) {
                                const cols = (grid && grid.columns) ? grid.columns : (grid && typeof grid.getColumns === 'function' ? grid.getColumns() : []);
                                if (!Array.isArray(cols) || !cols.length) return -1;

                                // 1) Primary: column that declares commands
                                let idx = cols.findIndex(col => col && Array.isArray(col.commands) && col.commands.length);
                                if (idx >= 0) return idx;

                                // 2) Secondary: header text explicitly named 'Manage Records'
                                idx = cols.findIndex(col => col && col.headerText === 'Manage Records');
                                if (idx >= 0) return idx;

                                // 3) No match
                                return -1;
                            }

                            function getExportExcludedColumnIndexes(grid, actionId) {
                                const cols = (grid && grid.columns) ? grid.columns : [];
                                // For excel/csv/pdf hide any column explicitly marked exportExclude === true.
                                const isPrint = actionId === 'toolbarPrint';
                                if (isPrint) return [];
                                const idxs = [];
                                cols.forEach((col, i) => {
                                    if (col && col.exportExclude === true) idxs.push(i);
                                });
                                return idxs;
                            }

                            // Identify columns with blank/empty header text to hide on Print only
                            function getBlankHeaderColumnIndexes(grid) {
                                const cols = (grid && grid.columns) ? grid.columns : [];
                                const idxs = [];
                                cols.forEach((col, i) => {
                                    const ht = (col && typeof col.headerText === 'string') ? col.headerText.trim() : '';
                                    if (!ht) idxs.push(i);
                                });
                                return idxs;
                            }

                            function toggleColumnsVisibility(grid, idxs, visible) {
                                if (!grid || !Array.isArray(idxs) || !grid.columns) return;
                                try {
                                    idxs.forEach(i => {
                                        const col = grid.columns[i];
                                        if (!col) return;
                                        if (typeof col._originalVisibility === 'undefined') {
                                            col._originalVisibility = col.visible;
                                        }
                                        col.visible = visible;
                                    });
                                    if (typeof grid.refresh === 'function') grid.refresh();
                                } catch (_) { }
                            }

                            const exportActions = {
                                'toolbarPrint': () => g.print && g.print(),
                                'toolbarExcelExport': () => g.excelExport && g.excelExport(),
                                'toolbarCsvExport': () => g.csvExport && g.csvExport(),
                                'toolbarPdfExport': () => {
                                    try {
                                        return g.pdfExport && g.pdfExport();
                                    } catch (e) {
                                        if (window.toastr) toastr.error('PDF export failed.');
                                        console.error('PDF export failed:', e);
                                        return null;
                                    }
                                }
                            };

                            try {
                                const cmdIdx = getCommandColumnIndex(g);
                                const excludeIdxs = getExportExcludedColumnIndexes(g, args.item.id);
                                const blankHeaderIdxs = (args.item.id === 'toolbarPrint') ? getBlankHeaderColumnIndexes(g) : [];
                                const toHide = [];
                                if (cmdIdx >= 0) toHide.push(cmdIdx);
                                // Hide export-excluded columns only for excel/csv/pdf
                                if (excludeIdxs.length && args.item.id !== 'toolbarPrint') {
                                    toHide.push(...excludeIdxs);
                                }
                                // Hide columns with blank header text on Print only
                                if (blankHeaderIdxs.length && args.item.id === 'toolbarPrint') {
                                    toHide.push(...blankHeaderIdxs);
                                }

                                show();
                                toggleColumnsVisibility(g, toHide, false);

                                const action = exportActions[args.item.id];
                                let result;
                                try { result = action ? action() : null; } catch (e) { result = null; throw e; }

                                const restore = function () {
                                    try {
                                        // Restore original visibility for toggled columns
                                        toHide.forEach(i => {
                                            const col = g.columns[i];
                                            if (col && typeof col._originalVisibility !== 'undefined') {
                                                col.visible = col._originalVisibility;
                                            }
                                        });
                                        if (typeof g.refresh === 'function') g.refresh();
                                    } catch (_) { }
                                    try { hide(); } catch (_) { }
                                };

                                if (result && typeof result.then === 'function') {
                                    result
                                        .catch(function (err) {
                                            try {
                                                if (window.toastr) toastr.error('Export failed.');
                                                console.error('Export promise rejected:', err);
                                            } catch (_) { }
                                        })
                                        .finally(restore);
                                } else {
                                    // Fallback: timed restore
                                    setTimeout(restore, 700);
                                }
                            } catch (e) {
                                console.error('Export/Print handling failed:', e);
                                try { hide(); } catch (_) { }
                                if (window.toastr) toastr.error('Export/Print failed.');
                            }
                        }
                    };
                },

                // Fetch data from URL and bind to grid. Options:
                //   dateFields: array of field names to coerce to Date
                //   minFetchTime: override minimum overlay time
                //   onBefore: callback invoked before fetch
                //   onAfter: callback invoked after setting data
                loadDataToGrid(grid, url, options) {
                    if (!grid || !url) return Promise.resolve();
                    const opts = options || {};
                    const dateFields = opts.dateFields || [];
                    const minTime = typeof opts.minFetchTime === 'number' ? opts.minFetchTime : window.AppUtils.MIN_FETCH_TIME;
                    const show = window.AppUtils.showOverlay;
                    const hide = window.AppUtils.hideOverlay;

                    // Avoid overlapping loads
                    grid._isLoading = grid._isLoading || false;
                    if (grid._isLoading) return Promise.resolve();
                    grid._isLoading = true;

                    const fetchStartTime = Date.now();
                    if (typeof opts.onBefore === 'function') {
                        try { opts.onBefore(); } catch (_) { }
                    }

                    // Show overlay if this isn't the very first implicit load or when explicitly requested
                    show();

                    return fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            // try { if (window && window.console && console.log) console.log('Grid data:', data); } catch(_) {}
                            const parsed = Array.isArray(data) ? data.map(item => {
                                const copy = { ...item };
                                dateFields.forEach(f => {
                                    if (copy[f]) copy[f] = new Date(copy[f]);
                                });
                                return copy;
                            }) : [];

                            // Debug: expose parsed payload for quick inspection (safe)
                            // try { if (window && window.console && console.log) console.log('Grid parsed data:', parsed); } catch(_) {}
                            // try { if (window && window.console && console.log) console.log('Date Fields:', dateFields); } catch(_) {}

                            const elapsed = Date.now() - fetchStartTime;
                            const remaining = Math.max(minTime - elapsed, 0);
                            return new Promise(resolve => setTimeout(resolve, remaining)).then(() => {
                                grid.dataSource = parsed;
                                if (typeof opts.onAfter === 'function') {
                                    try { opts.onAfter(parsed); } catch (_) { }
                                }
                            });
                        })
                        .catch(err => {
                            console.error('Grid load error:', err);
                            if (window.toastr) toastr.error('Failed to load data');
                        })
                        .finally(() => {
                            hide();
                            grid._isLoading = false;
                        });
                }
            };

            // ------- Toastr helper (centralized notifications) -------
            // Usage: AppUtils.notify('Saved!', { type: 'success', positionClass: 'toast-bottom-right', escapeHtml: false })
            window.AppUtils.notify = function (message, opts) {
                try {
                    if (!window.toastr) return;
                    const options = opts || {};
                    const type = (options.type || 'info').toLowerCase();
                    const positionClass = options.positionClass || 'toast-bottom-right';

                    // sentences to new lines
                    let formattedMessage = (message || '').toString();
                    if (options.multiLine !== false && /[\.!\?]\s/.test(formattedMessage)) {
                        // Split after . ! ? if followed by space, preserving the punctuation
                        formattedMessage = formattedMessage.replace(/([\.!\?])\s/g, '$1<br>');
                        options.escapeHtml = false;
                    }

                    const prevPos = (window.toastr.options && window.toastr.options.positionClass) ? window.toastr.options.positionClass : null;
                    const prevEscape = window.toastr.options.escapeHtml;
                    const prevTimeout = window.toastr.options.timeOut;

                    if (positionClass) { window.toastr.options.positionClass = positionClass; }
                    // Allow HTML if we formatted or if caller explicitly sets escapeHtml to false
                    if (options.escapeHtml === false) { window.toastr.options.escapeHtml = false; }

                    // Allow custom timeout
                    if (typeof options.timeout === 'number') { window.toastr.options.timeOut = options.timeout; }

                    if (typeof window.toastr[type] === 'function') {
                        window.toastr[type](formattedMessage);
                    } else {
                        window.toastr.info(formattedMessage);
                    }

                    if (prevPos) { window.toastr.options.positionClass = prevPos; }
                    if (typeof prevEscape !== 'undefined') { window.toastr.options.escapeHtml = prevEscape; }
                    if (typeof prevTimeout !== 'undefined') { window.toastr.options.timeOut = prevTimeout; }
                } catch (_) { }
            };

            // Backwards-compatible toast aliases used by some pages
            window.AppUtils.toastSuccess = function (message, opts) {
                try { window.AppUtils.notify(message, Object.assign({ type: 'success' }, opts || {})); } catch (_) { }
            };
            window.AppUtils.toastError = function (message, opts) {
                try { window.AppUtils.notify(message, Object.assign({ type: 'error' }, opts || {})); } catch (_) { }
            };
            window.AppUtils.toastInfo = function (message, opts) {
                try { window.AppUtils.notify(message, Object.assign({ type: 'info' }, opts || {})); } catch (_) { }
            };

            // ------- Form helpers (AJAX submit + validation wiring) -------
            // Provides a single attachAjaxSubmit(form, options) to handle common POST/422 flows.
            // Options:
            //   submitBtn: HTMLElement (button to disable/enable during submit)
            //   method: 'POST' | 'PUT' | ... (default 'POST')
            //   headers: additional headers (Accept: application/json is added by default)
            //   getMode: () => 'edit' | 'create'  (used to remove 'id' from FormData in create)
            //   beforeSubmit: (ctx) => void
            //   onSuccess: (json, ctx) => void
            //   onError: (error, ctx) => void       // network or non-OK, non-422
            //   onValidation: (data, ctx) => void   // 422 JSON from server
            //   errorTargets: { [field]: selector|string } // map field to error container
            //   focusFirstInvalid: boolean (default true)
            //   submitWhenInvalid: boolean (default false) // if true, still submits to server even if form.checkValidity() is false, to surface server-side messages
            (function () {
                function cssEscapeSafe(s) {
                    try { return (window.CSS && CSS.escape) ? CSS.escape(String(s)) : String(s).replace(/(["'\\#\.\[\]:,=])/g, '\\$1'); } catch (_) { return String(s); }
                }

                function findFieldContainers(input) {
                    if (!input) return { feedback: null };
                    // Prefer explicitly associated invalid-feedback element next to the input
                    const sib = input.parentElement ? input.parentElement.querySelector('.invalid-feedback') : null;
                    return { feedback: sib };
                }

                function setFieldError(input, message, explicitTarget) {
                    if (!input) return;
                    try {
                        input.classList.add('is-invalid');
                        if (typeof input.setCustomValidity === 'function') input.setCustomValidity(message || 'Invalid');
                    } catch (_) { }

                    let targetEl = null;
                    if (explicitTarget) {
                        targetEl = (typeof explicitTarget === 'string') ? document.querySelector(explicitTarget) : explicitTarget;
                    }
                    if (!targetEl) {
                        const { feedback } = findFieldContainers(input);
                        targetEl = feedback;
                    }
                    if (!targetEl) {
                        // Fallback by id convention: <field>_error
                        const fallbackId = input.id ? `${input.id}_error` : null;
                        if (fallbackId) targetEl = document.getElementById(fallbackId);
                    }
                    if (targetEl) {
                        try { targetEl.textContent = message || 'Invalid value.'; } catch (_) { }
                    }
                }

                function clearFieldError(input) {
                    if (!input) return;
                    try {
                        input.classList.remove('is-invalid');
                        if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
                    } catch (_) { }
                }

                function normalizeErrorKeyToSelectors(key) {
                    const sel = [];
                    const raw = String(key);
                    sel.push(`[name="${cssEscapeSafe(raw)}"]`);
                    sel.push(`#${cssEscapeSafe(raw)}`);
                    // Best-effort dot->bracket transform: a.b.c -> a[b][c], a.0.b -> a[0][b]
                    try {
                        const bracket = raw.replace(/\.(\d+)(?=\.|$)/g, '[$1]').replace(/\.(\w+)/g, '[$1]');
                        if (bracket !== raw) sel.push(`[name="${cssEscapeSafe(bracket)}"]`);
                    } catch (_) { }
                    return sel;
                }

                function attachAjaxSubmit(form, options) {
                    if (!form) return function () { };
                    const opts = options || {};
                    const method = (opts.method || 'POST').toUpperCase();
                    const submitBtn = opts.submitBtn || null;
                    const focusFirstInvalid = (opts.focusFirstInvalid !== false);
                    const submitWhenInvalid = !!opts.submitWhenInvalid;

                    // Clear a field's invalid state once user interacts with it
                    function isFormControl(el) {
                        return !!el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
                    }

                    function onInteract(e) {
                        const target = e && e.target ? e.target : null;
                        if (!isFormControl(target)) return;
                        clearFieldError(target);
                        // Optionally remove form-level was-validated if no invalid fields remain
                        try {
                            const stillInvalid = form.querySelector('.is-invalid');
                            if (!stillInvalid) form.classList.remove('was-validated');
                        } catch (_) { }
                    }

                    form.addEventListener('input', onInteract, true);
                    form.addEventListener('change', onInteract, true);

                    async function onSubmit(e) {
                        e.preventDefault();
                        const isValid = form.checkValidity();
                        if (!isValid && !submitWhenInvalid) {
                            e.stopPropagation();
                            form.classList.add('was-validated');
                            if (focusFirstInvalid) {
                                const firstInvalid = form.querySelector('.is-invalid, .form-control:invalid, input:invalid, textarea:invalid');
                                if (firstInvalid && firstInvalid.focus) firstInvalid.focus({ preventScroll: true });
                            }
                            return;
                        }
                        // Determine mode first and run beforeSubmit (supports async). If it returns false, cancel submit.
                        const mode = (typeof opts.getMode === 'function') ? opts.getMode() : (form.dataset.mode || 'create');
                        try {
                            if (typeof opts.beforeSubmit === 'function') {
                                const result = opts.beforeSubmit({ form, submitBtn, mode });
                                if (result && typeof result.then === 'function') {
                                    const awaited = await result;
                                    if (awaited === false) return; // cancel
                                } else if (result === false) {
                                    return; // cancel
                                }
                            }
                        } catch (_) { }

                        // Capture (possibly updated) action after beforeSubmit runs
                        const actionUrl = form.action;
                        const payload = new FormData(form);
                        if (mode !== 'edit') { payload.delete('id'); }

                        const headers = Object.assign({ 'Accept': 'application/json' }, (opts.headers || {}));

                        try {
                            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('disabled'); }

                            const resp = await fetch(actionUrl, { method, headers, body: payload });

                            if (resp.status === 422) {
                                let data = {};
                                try { data = await resp.json(); } catch (_) { }
                                const errs = (data && data.errors) ? data.errors : {};

                                // Clear previous errors for a fresh pass
                                try {
                                    const prevInvalids = form.querySelectorAll('.is-invalid');
                                    prevInvalids.forEach(i => clearFieldError(i));
                                } catch (_) { }

                                const errorTargets = opts.errorTargets || {};
                                const invalidInputs = [];
                                Object.keys(errs).forEach(function (field) {
                                    const messages = Array.isArray(errs[field]) ? errs[field] : [String(errs[field] || 'Invalid')];
                                    const selectors = normalizeErrorKeyToSelectors(field);
                                    let inputs = [];
                                    for (let i = 0; i < selectors.length; i++) {
                                        const list = form.querySelectorAll(selectors[i]);
                                        if (list && list.length) { inputs = Array.from(list); break; }
                                    }
                                    if (!inputs.length) return;

                                    const explicitTarget = errorTargets[field] || null;
                                    inputs.forEach((inp, idx) => {
                                        const msg = messages[idx] || messages[0] || 'Invalid value.';
                                        setFieldError(inp, msg, explicitTarget);
                                        invalidInputs.push(inp);
                                    });
                                });

                                form.classList.add('was-validated');

                                // Optional toast honoring alert-type and positionClass
                                if (data && data.message) {
                                    const type = (data['alert-type'] || 'error');
                                    const pos = data['positionClass'] || 'toast-bottom-right';
                                    window.AppUtils.notify(data.message, { type, positionClass: pos });
                                }

                                if (focusFirstInvalid && invalidInputs.length) {
                                    try { invalidInputs[0].focus({ preventScroll: true }); if (invalidInputs[0].select) invalidInputs[0].select(); } catch (_) { }
                                }

                                if (typeof opts.onValidation === 'function') {
                                    try { opts.onValidation(data, { form, submitBtn, mode }); } catch (_) { }
                                }
                                return;
                            }

                            if (!resp.ok) {
                                // Non-422 error: try to surface server message
                                let dataOrText = null, message = 'Operation failed.', type = 'error', pos = 'toast-bottom-right';
                                try {
                                    // Attempt JSON first
                                    dataOrText = await resp.json();
                                    if (dataOrText && typeof dataOrText === 'object') {
                                        message = dataOrText.message || message;
                                        type = (dataOrText['alert-type'] || type);
                                        pos = (dataOrText['positionClass'] || pos);
                                    }
                                } catch (_) {
                                    try {
                                        dataOrText = await resp.text();
                                        if (dataOrText) message = String(dataOrText).slice(0, 400);
                                    } catch (_) { }
                                }

                                if (typeof opts.onError === 'function') {
                                    try { opts.onError({ message, response: resp, data: dataOrText }, { form, submitBtn, mode }); } catch (_) { }
                                } else {
                                    window.AppUtils.notify(message, { type, positionClass: pos });
                                }
                                return;
                            }

                            let json = {};
                            try { json = await resp.json(); } catch (_) { }

                            // Some APIs return 200 with success=false
                            if (json && json.success === false) {
                                const message = json.message || 'Operation failed.';
                                const type = (json['alert-type'] || 'error');
                                const pos = (json['positionClass'] || 'toast-bottom-right');
                                if (typeof opts.onError === 'function') {
                                    try { opts.onError({ message, data: json }, { form, submitBtn, mode }); } catch (_) { }
                                } else {
                                    window.AppUtils.notify(message, { type, positionClass: pos });
                                }
                                return;
                            }

                            if (typeof opts.onSuccess === 'function') {
                                try { opts.onSuccess(json, { form, submitBtn, mode }); } catch (_) { }
                            } else {
                                const type = (json && json['alert-type']) ? String(json['alert-type']).toLowerCase() : 'success';
                                const pos = (json && json['positionClass']) ? json['positionClass'] : 'toast-bottom-right';
                                const message = (json && json.message) ? json.message : (mode === 'edit' ? 'Updated successfully' : 'Saved successfully');
                                window.AppUtils.notify(message, { type, positionClass: pos });
                            }
                        } catch (error) {
                            if (typeof opts.onError === 'function') {
                                try { opts.onError(error, { form, submitBtn }); } catch (_) { }
                            } else {
                                if (window.toastr) window.AppUtils.notify('Operation failed. Please try again.', { type: 'error' });
                            }
                        } finally {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('disabled'); }
                            if (typeof opts.afterFinally === 'function') {
                                try { opts.afterFinally({ form, submitBtn }); } catch (_) { }
                            }
                        }
                    }

                    form.addEventListener('submit', onSubmit);
                    // Return a small disposer if the page needs to detach
                    return function detach() {
                        try { form.removeEventListener('submit', onSubmit); } catch (_) { }
                        try { form.removeEventListener('input', onInteract, true); } catch (_) { }
                        try { form.removeEventListener('change', onInteract, true); } catch (_) { }
                    };
                }

                window.AppUtils.FormHelpers = { attachAjaxSubmit };
            })();

            // ------- Input helpers (formatting/binding) -------
            (function () {
                window.AppUtils.InputHelpers = window.AppUtils.InputHelpers || {};

                // Convert a string to Title Case, but DO NOT force lowercase on existing caps.
                // Only capitalize a lowercase letter that follows a word boundary; preserve user-entered casing.
                function toCamelWords(str) {
                    if (str == null) return '';
                    var s = String(str);

                    // Helper: boundary check (space or common separators)
                    function isBoundary(ch) { return /[\s\-_/\.]/.test(ch); }

                    // Helper: letter checks (with unicode try/fallback)
                    function isLetter(ch) {
                        try { return /\p{L}/u.test(ch); } catch (_) { return /[A-Za-z]/.test(ch); }
                    }
                    function isLower(ch) { return ch === ch.toLocaleLowerCase() && ch !== ch.toLocaleUpperCase(); }

                    var out = '';
                    var afterBoundary = true; // start is a boundary

                    for (var i = 0; i < s.length; i++) {
                        var ch = s[i];
                        if (afterBoundary && isLetter(ch) && isLower(ch)) {
                            out += ch.toLocaleUpperCase();
                        } else {
                            out += ch; // preserve user-entered case
                        }
                        afterBoundary = isBoundary(ch);
                    }
                    return out;
                }

                // Bind live Title Case formatting while preserving user-entered caps
                function bindCamelCase(el) {
                    if (!el) return;
                    el.addEventListener('input', function () {
                        const start = el.selectionStart, end = el.selectionEnd;
                        const v = el.value || '';
                        const t = toCamelWords(v);
                        if (v !== t) {
                            el.value = t;
                            try { el.setSelectionRange(start, end); } catch (_) { }
                        }
                    });
                }

                window.AppUtils.InputHelpers.toCamelWords = toCamelWords;
                window.AppUtils.InputHelpers.bindCamelCase = bindCamelCase;
            })();

            // ------- Actions helpers (Delete with confirm, CSRF-aware) -------
            (function () {
                window.AppUtils.Actions = window.AppUtils.Actions || {};

                function getCsrfToken() {
                    try {
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta && meta.content) return meta.content;
                    } catch (_) { }
                    try {
                        var inp = document.querySelector('input[name="_token"]');
                        if (inp && inp.value) return inp.value;
                    } catch (_) { }
                    try { return (window.Laravel && window.Laravel.csrfToken) ? window.Laravel.csrfToken : null; } catch (_) { return null; }
                }

                async function confirmDialog(options) {
                    var opts = options || {};
                    var title = opts.title || 'Are you sure?';
                    var text = opts.text || 'This action cannot be undone.';
                    var confirmButtonText = opts.confirmButtonText || 'Yes, delete it!';
                    var confirmButtonColor = opts.confirmButtonColor || '#3085d6';
                    var cancelButtonColor = opts.cancelButtonColor || '#e34b4bff';

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        var res = await window.Swal.fire({
                            title: title,
                            text: text,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: confirmButtonColor,
                            cancelButtonColor: cancelButtonColor,
                            confirmButtonText: confirmButtonText
                        });
                        return !!(res && res.isConfirmed);
                    }
                    return window.confirm(title + '\n' + text);
                }

                async function deleteResource({ url, method = 'POST', data = {}, confirm = { title: 'Are you sure?', text: 'Delete This Data?' }, successMessage = 'Deleted successfully.', onSuccess, onError }) {
                    try {
                        if (confirm) {
                            var confirmed = await confirmDialog(typeof confirm === 'object' ? confirm : {});
                            if (!confirmed) return { cancelled: true };
                        }

                        var fd = new FormData();
                        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
                        var csrf = getCsrfToken();
                        if (csrf) fd.append('_token', csrf);

                        var resp = await fetch(url, {
                            method: method,
                            headers: { 'Accept': 'application/json' },
                            body: fd
                        });

                        if (!resp.ok) {
                            try {
                                var j = await resp.json();
                                var msg = (j && j.message) ? j.message : 'Delete failed.';
                                window.AppUtils.notify(msg, { type: 'error' });
                            } catch (_) {
                                window.AppUtils.notify('Delete failed.', { type: 'error' });
                            }
                            if (typeof onError === 'function') { try { onError(new Error('Network error')); } catch (_) { } }
                            return { ok: false };
                        }

                        var json = {};
                        try { json = await resp.json(); } catch (_) { }

                        if (json && json.success === false) {
                            var emsg = json.message || 'Delete failed.';
                            window.AppUtils.notify(emsg, { type: 'error' });
                            if (typeof onError === 'function') { try { onError(new Error(emsg)); } catch (_) { } }
                            return { ok: false, json };
                        }

                        // success
                        var msg = (json && json.message) ? json.message : successMessage;
                        window.AppUtils.notify(msg, { type: 'success' });
                        if (typeof onSuccess === 'function') { try { onSuccess(json); } catch (_) { } }
                        return { ok: true, json };
                    } catch (err) {
                        window.AppUtils.notify('Delete failed.', { type: 'error' });
                        if (typeof onError === 'function') { try { onError(err); } catch (_) { } }
                        return { ok: false, error: err };
                    }
                }

                window.AppUtils.confirm = confirmDialog;
                window.AppUtils.Actions.deleteResource = deleteResource;
            })();

            // Auto-run some basic checks and bind the refresh button
            document.addEventListener('DOMContentLoaded', function () {
                try {
                    // Initialize feather icons if available
                    if (typeof feather !== 'undefined') {
                        try { feather.replace(); } catch (_) { }
                    }
                    // Heartbeat: check session liveness every 60s
                    try {
                        if (!window.__sessionHeartbeatAttached) {
                            window.__sessionHeartbeatAttached = true;

                            function checkAuthHeartbeat() {
                                if (window.__sessionEnded) return;
                                fetch('/__auth-check?_ts=' + Date.now(), {
                                    method: 'GET',
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                    credentials: 'same-origin',
                                    cache: 'no-store'
                                })
                                    .then(function (r) { return r.ok ? r.json() : { authenticated: false, redirect: '/' }; })
                                    .then(function (j) {
                                        if (!j || j.authenticated !== true) {
                                            window.__sessionEnded = true;
                                            try {
                                                var payload = {
                                                    message: 'Your session has ended. Please login again.',
                                                    type: 'error',
                                                    positionClass: 'toast-bottom-right',
                                                    timeout: 5000
                                                };
                                                localStorage.setItem('toast-next', JSON.stringify(payload));
                                            } catch (_) { }
                                            var redir = (j && j.redirect) ? j.redirect : '/login';
                                            window.location.href = redir;
                                        }
                                    })
                                    .catch(function () { /* ignore network blips */ });
                            }

                            // Perform an immediate check, then schedule subsequent checks
                            try { checkAuthHeartbeat(); } catch (_) { }
                            setInterval(checkAuthHeartbeat, 60000);
                        }
                    } catch (_) { }

                    // Check for Syncfusion EJ2 Grid availability and expose a flag
                    var ejReady = !(typeof ej === 'undefined' || !ej.grids || !ej.grids.Grid);
                    window.AppUtils = window.AppUtils || {};
                    window.AppUtils.librariesReady = ejReady;
                    if (!ejReady) {
                        console.error('Syncfusion EJ2 Grid not loaded.');
                        if (window.toastr) toastr.error('Syncfusion library not loaded.');
                    }

                    var btn = document.getElementById(@json($__refreshId));
                    if (btn) {
                        btn.addEventListener('click', function () {
                            try {
                                var icon = document.getElementById(@json($__reloadIconId));
                                if (icon) {
                                    icon.style.animation = 'spin 0.5s linear';
                                    setTimeout(function () { icon.style.animation = ''; }, 500);
                                }
                                if (window.AppPage && typeof window.AppPage.onRefresh === 'function') {
                                    window.AppPage.onRefresh();
                                }
                            } catch (_) { }
                        });
                    }

                    // If a Filter toolbar button exists, ensure it can be styled when filters differ from defaults
                    try {
                        const filterBtn = document.getElementById('toolbarFilter');
                        if (filterBtn) {
                            // Expose a tiny helper for pages/components to toggle colored icon state
                            window.AppUtils = window.AppUtils || {};
                            window.AppUtils.__setFilterButtonActive = function (active) {
                                try {
                                    filterBtn.classList.toggle('filter-active', !!active);
                                    filterBtn.classList.toggle('text-primary', !!active);
                                } catch (_) { }
                            };
                        }
                    } catch (_) { }
                } catch (_) { }
            });

        })();
    </script>

    <style>
        /* Common styles shared across admin grids */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        #cardLoadingOverlay {
            display: none;
            align-items: center;
            justify-content: center;
        }

        #cardLoadingOverlay[data-overlay-mode="card"] {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            pointer-events: auto;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            opacity: 0;
            visibility: hidden;
        }

        #cardLoadingOverlay[data-overlay-mode="page"] {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            /* Dark dimming */
            z-index: 2147483647;
            pointer-events: auto;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            opacity: 0;
            visibility: hidden;
        }

        #cardLoadingOverlay[data-overlay-mode="page"] .loading-content-wrapper {
            background: rgba(255, 255, 255, 0.65);
            /* Semi-transparent white */
            backdrop-filter: blur(8px);
            /* Glassmorphism effect */
            -webkit-backdrop-filter: blur(8px);
            padding: 30px 50px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        #cardLoadingOverlay[data-overlay-mode="page"] .loading-text {
            color: var(--bs-body-color, #212529) !important;
        }

        .loading-spinner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Highlight class for search matches */
        .customcss {
            background-color: #fff3cd;
            color: inherit;
            padding: 0 2px;
            border-radius: 2px;
        }

        /* Reusable grid helpers */
        .grid-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            box-sizing: border-box;
            background: #fff;
            border: 2px solid var(--bs-border-color, #dee2e6);
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
        }

        /* Ensure command columns don't appear in system/browser print as a safety net */
        @media print {
            .e-grid .e-commandcolumn {
                display: none !important;
            }
        }
    </style>

    <style>
        /* Visual state when filters are non-default: color icon/text only (no background) */
        .e-toolbar .e-toolbar-items .e-toolbar-item #toolbarFilter.filter-active,
        #toolbarFilter.filter-active {
            color: #0d6efd !important;
            /* bootstrap primary */
        }

        #toolbarFilter.text-primary .e-icons,
        #toolbarFilter.filter-active .e-icons {
            color: #0d6efd !important;
        }
    </style>

    <template id="auditInfoTemplate">
        <div id="auditInfoWrap" class="text-muted small flex-column" style="display:none; min-height:36px;">
            <div class="align-items-center mb-1" title="Created Info">
                <i data-feather="plus-square" style="width:16px;height:16px;"></i>
                <span id="createdInfo"></span>
            </div>
            <div class="align-items-center updated-wrap" id="updatedInfoParent" style="display:none;"
                title="Last Updated Info">
                <i data-feather="edit" style="width:16px;height:16px;"></i>
                <span id="updatedInfo"></span>
            </div>
        </div>
    </template>

    @if($__wrapCard)
        </div>
    @endif