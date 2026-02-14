{{-- =====================================================================================
SEQUENCE APP SIDEBAR - Clean Template
===================================================================================== --}}
<div class="sidebar-container">
    <nav class="side-bar" id="sidebar-nav">
        <div class="side-bar-logo">
            <a href="{{ route('dashboard') }}">
                {{-- Full logo - shown when sidebar is expanded --}}
                <div class="logo-full d-flex align-items-center gap-2">
                    <img src="{{ asset(get_option('general')['login_page_logo'] ?? 'assets/images/icons/logo.svg') }}"
                        alt="Logo" style="height: 32px;"> {{-- Adjust height as needed --}}
                    <span class="fw-bold text-dark" style="font-size: 26px; letter-spacing: 0.5px;">Sequence</span>
                </div>
                {{-- Minimized logo - shown when sidebar is collapsed --}}
                <img src="{{ asset('assets/images/icons/logo.svg') }}" alt="Logo" class="logo-mini">
            </a>
            <button class="close-btn" id="sidebarCloseBtn"><i class="fas fa-times"></i></button>
        </div>
        <div class="side-bar-manu">
            <ul id="side-menu">
                @php
                    /** @var \App\Models\User|null $authUser */
                    $authUser = auth()->user();
                    $role = strtolower(trim((string) optional($authUser)->role));
                    $rolePages = (array) config('services.role_pages');
                    $allowed = (array) ($rolePages[$role] ?? []);
                    $superUserIds = collect(config('services.super_users.ids') ?? [])->map(fn($id) => (int) $id)->filter(fn($id) => $id > 0)->unique()->values()->all();
                    $isRootSuperUser = $role === 'super admin' && $authUser && in_array((int) $authUser->id, $superUserIds, true);
                    $canBusiness = in_array('business.register', $allowed, true) && $isRootSuperUser;
                    $canUser = in_array('user.register', $allowed, true);
                    $isAdminRole = in_array($role, ['super admin', 'admin'], true);
                @endphp

                {{-- Dashboard --}}
                @if($role === 'super admin' || $role === 'admin')
                    <li class="menu-title">Menu</li>
                    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            <span class="sidebar-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M2.63673 9.45808L3.33335 9.63225L3.71407 13.4171C3.92919 15.5557 4.03675 16.6249 4.75085 17.2708C5.46496 17.9167 6.53963 17.9167 8.68894 17.9167H11.3111C13.4604 17.9167 14.5351 17.9167 15.2492 17.2708C15.9633 16.6249 16.0709 15.5557 16.2859 13.4171L16.6667 9.63225L17.3634 9.45808C17.9334 9.31558 18.3334 8.80333 18.3334 8.21568C18.3334 7.79779 18.1294 7.40618 17.7871 7.16654L10.9558 2.38462C10.3819 1.98291 9.6181 1.98291 9.04427 2.38462L2.21293 7.16654C1.87058 7.40618 1.66669 7.79779 1.66669 8.21568C1.66669 8.80333 2.06663 9.31558 2.63673 9.45808Z"
                                        stroke="currentColor" stroke-width="1.25" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path
                                        d="M10 14.1667C11.1506 14.1667 12.0834 13.2339 12.0834 12.0833C12.0834 10.9327 11.1506 10 10 10C8.84943 10 7.91669 10.9327 7.91669 12.0833C7.91669 13.2339 8.84943 14.1667 10 14.1667Z"
                                        stroke="currentColor" stroke-width="1.25" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg></span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                @endif

                {{-- Utility Menu --}}
                @if($canBusiness || $canUser || $isRootSuperUser)
                    <li class="menu-title" style="margin-top: 15px;">Pages</li>
                    <li class="dropdown {{ request()->routeIs('business.register', 'user.register') ? 'active' : '' }}">
                        <a href="javascript:void(0);">
                            <span class="sidebar-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M12.9166 9.99998C12.9166 11.6108 11.6108 12.9166 9.99998 12.9166C8.38915 12.9166 7.08331 11.6108 7.08331 9.99998C7.08331 8.38915 8.38915 7.08331 9.99998 7.08331C11.6108 7.08331 12.9166 8.38915 12.9166 9.99998Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <path
                                        d="M17.5092 11.7471C17.9441 11.6298 18.1616 11.5712 18.2474 11.459C18.3334 11.3469 18.3334 11.1665 18.3334 10.8058V9.19435C18.3334 8.8336 18.3334 8.65318 18.2474 8.5411C18.1615 8.42893 17.9441 8.37026 17.5092 8.253C15.8839 7.81467 14.8666 6.11544 15.2861 4.50074C15.4014 4.05667 15.4591 3.83465 15.404 3.70442C15.3489 3.5742 15.1909 3.48446 14.8748 3.30498L13.4375 2.48896C13.1274 2.31284 12.9723 2.22478 12.8331 2.24353C12.6939 2.26228 12.5369 2.41896 12.2227 2.73229C11.0067 3.94541 8.99469 3.94536 7.77864 2.73221C7.46455 2.41887 7.3075 2.26221 7.16829 2.24345C7.02909 2.2247 6.874 2.31276 6.5638 2.48887L5.12655 3.30491C4.81046 3.48437 4.65241 3.57411 4.59734 3.70431C4.54225 3.83451 4.59991 4.05657 4.71523 4.50068C5.1345 6.11543 4.11645 7.81471 2.49087 8.25301C2.05595 8.37026 1.8385 8.42893 1.75259 8.54101C1.66669 8.65318 1.66669 8.8336 1.66669 9.19435V10.8058C1.66669 11.1665 1.66669 11.3469 1.75259 11.459C1.83848 11.5712 2.05595 11.6298 2.49087 11.7471C4.11619 12.1854 5.13342 13.8847 4.71395 15.4993C4.5986 15.9434 4.54091 16.1654 4.59599 16.2957C4.65107 16.4259 4.80912 16.5157 5.12523 16.6951L6.56248 17.5112C6.8727 17.6873 7.0278 17.7753 7.16702 17.7566C7.30624 17.7378 7.46325 17.5811 7.77728 17.2678C8.99394 16.0537 11.0074 16.0536 12.2241 17.2677C12.5381 17.5811 12.6951 17.7378 12.8344 17.7565C12.9735 17.7753 13.1287 17.6872 13.4389 17.5111L14.8761 16.695C15.1923 16.5156 15.3504 16.4258 15.4054 16.2956C15.4604 16.1653 15.4028 15.9433 15.2874 15.4993C14.8677 13.8847 15.8841 12.1855 17.5092 11.7471Z"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg></span>
                            <span>Utility</span>
                        </a>
                        <ul class="dropdown-menu">
                            @if($canBusiness)
                                <li class="{{ request()->routeIs('business.register') ? 'active' : '' }}"><a
                                        href="{{ route('business.register') }}"><span class="sidebar-icon"><i
                            class="fas fa-building"></i></span>Business Register</a></li>@endif
                            @if($canUser)
                                <li class="{{ request()->routeIs('user.register') ? 'active' : '' }}"><a
                                        href="{{ route('user.register') }}"><span class="sidebar-icon"><i
                            class="fas fa-users-cog"></i></span>User Register</a></li>@endif
                        </ul>
                    </li>
                @endif
            </ul>
        </div>

        {{-- Bottom Pinned Section - Clear Cache & Logout --}}
        <div class="sidebar-bottom-pinned">
            <div class="sidebar-bottom-title">Other</div>
            <ul>
                <li>
                    <a href="{{ route('admin.cache.clear') }}" id="clearCacheLink" data-csrf="{{ csrf_token() }}"
                        role="button">
                        <span class="sidebar-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3.33337 10C3.33337 6.31808 6.31814 3.33331 10 3.33331C12.7615 3.33331 15.1066 5.05448 16.0718 7.5M16.6667 10C16.6667 13.6819 13.6819 16.6666 10 16.6666C7.2385 16.6666 4.8934 14.9455 3.92823 12.5"
                                    stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
                                <path d="M13.3334 7.5H16.25C16.4801 7.5 16.6667 7.31345 16.6667 7.08333V4.16666"
                                    stroke="currentColor" stroke-width="1.25" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M6.66663 12.5H3.74996C3.51984 12.5 3.33329 12.6866 3.33329 12.9167V15.8333"
                                    stroke="currentColor" stroke-width="1.25" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg></span>
                        <span>Clear Cache</span>
                    </a>
                </li>
                <li style="list-style: none;">
                    <a href="{{ route('admin.logout') }}" id="logoutLink" class="sidebar-logout-btn"
                        style="position: relative; z-index: 10; display: flex !important; pointer-events: auto !important;">
                        <span class="sidebar-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83331M17.5 10H7.5M7.5 2.5H6.5C4.29086 2.5 2.5 4.29086 2.5 6.5V13.5C2.5 15.7091 4.29086 17.5 6.5 17.5H7.5"
                                    stroke="currentColor" stroke-width="1.25" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg></span>
                        <span style="color: inherit;">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>

{{-- Overlay and Sidebar Scripts --}}
<style>
    #global-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: none;
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    #global-overlay .overlay-card {
        background: #ff7a2a;
        color: #fff;
        padding: 10px 16px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 220px;
    }

    #global-overlay .spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 255, 255, 0.95);
        border-top-color: transparent;
        border-radius: 50%;
        animation: sidebarSpin .8s linear infinite;
    }

    #global-overlay .overlay-message {
        color: #fff;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
    }

    @keyframes sidebarSpin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    (function () {
        // Dropdown toggle for sidebar menus
        document.querySelectorAll('.side-bar-manu .dropdown > a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var parent = this.parentElement;
                document.querySelectorAll('.side-bar-manu .dropdown.active').forEach(function (el) {
                    if (el !== parent) el.classList.remove('active');
                });
                parent.classList.toggle('active');
            });
        });

        // Close button for mobile sidebar
        var closeBtn = document.getElementById('sidebarCloseBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                document.body.setAttribute('data-sidebar', 'hidden');
            });
        }

        // Wire up the toggle menu button in header
        function setupToggleButton() {
            var toggleBtn = document.getElementById('sidebarToggleBtn') || document.querySelector('.button-toggle-menu');
            if (toggleBtn) {
                var newBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newBtn, toggleBtn);
                newBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var body = document.body;
                    if (body.getAttribute('data-sidebar') === 'hidden') {
                        body.setAttribute('data-sidebar', 'default');
                    } else {
                        body.setAttribute('data-sidebar', 'hidden');
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupToggleButton);
        } else {
            setupToggleButton();
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('#sidebarToggleBtn') || e.target.closest('.button-toggle-menu')) {
                e.preventDefault();
                e.stopPropagation();
                var body = document.body;
                if (body.getAttribute('data-sidebar') === 'hidden') {
                    body.setAttribute('data-sidebar', 'default');
                } else {
                    body.setAttribute('data-sidebar', 'hidden');
                }
            }
        });

        if (window.feather) feather.replace();

        function getCSRF() { var meta = document.querySelector('meta[name="csrf-token"]'); return meta ? meta.getAttribute('content') : undefined; }
        function ensureOverlay() {
            var el = document.getElementById('global-overlay');
            if (!el) { el = document.createElement('div'); el.id = 'global-overlay'; el.innerHTML = '<div class="overlay-card"><div class="spinner"></div><div class="overlay-message">Processing…</div></div>'; document.body.appendChild(el); }
            return el;
        }
        function showOverlay(message) { var el = ensureOverlay(); var msg = el.querySelector('.overlay-message'); if (msg) msg.textContent = message || 'Processing…'; el.style.display = 'flex'; }
        function hideOverlay() { var el = document.getElementById('global-overlay'); if (el) el.style.display = 'none'; }

        // Clear Cache
        var clearLink = document.getElementById('clearCacheLink');
        if (clearLink) {
            clearLink.addEventListener('click', function (e) {
                e.preventDefault();
                var url = this.getAttribute('href'), token = this.getAttribute('data-csrf') || getCSRF(), startedAt = Date.now();
                showOverlay('Clearing cache…');
                fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token || '', 'Accept': 'application/json' } })
                    .then(function (res) { if (res.ok) return res.json().catch(function () { return {}; }); throw res; })
                    .then(function () { setTimeout(function () { hideOverlay(); if (window.toastr) toastr.success('Cache cleared successfully.'); else alert('Cache cleared.'); }, Math.max(0, 1200 - (Date.now() - startedAt))); })
                    .catch(function () { setTimeout(function () { hideOverlay(); if (window.toastr) toastr.error('Failed to clear cache.'); else alert('Failed.'); }, Math.max(0, 1200 - (Date.now() - startedAt))); });
            });
        }

        // Logout
        var logoutLink = document.getElementById('logoutLink');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (e) {
                e.preventDefault();
                var href = this.getAttribute('href');
                showOverlay('Logging out…');
                if (window.toastr) toastr.info('Logging out…', '', { timeOut: 1800 });
                try { localStorage.setItem('toast-next', JSON.stringify({ type: 'success', message: 'Logged out successfully.', timeout: 3000 })); } catch (err) { }
                setTimeout(function () { window.location.href = href; }, 2000);
            });
        }
    })();
</script>