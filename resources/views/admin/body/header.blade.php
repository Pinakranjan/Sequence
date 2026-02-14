<div class="topbar-custom">
    <div class="container-fluid">
        <div class="d-flex justify-content-between">
            <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                <li>
                    <button class="button-toggle-menu nav-link ps-0" id="sidebarToggleBtn" type="button"
                        style="border: none; background: none; cursor: pointer;">
                        <i class="fas fa-bars" style="font-size: 20px; color: #5b5b5b;"></i>
                    </button>
                </li>
                <li class="d-none d-lg-block">
                    <div class="position-relative topbar-search">
                        <input type="text" class="form-control bg-light bg-opacity-75 border-light ps-4"
                            placeholder="Search...">
                        <i
                            class="mdi mdi-magnify fs-16 position-absolute text-muted top-50 translate-middle-y ms-2"></i>
                    </div>
                </li>
            </ul>

            <ul class="list-unstyled topnav-menu mb-0 d-flex align-items-center">
                <li class="d-none d-sm-flex">
                    <button type="button" class="btn nav-link" data-action="fullscreen">
                        <i data-feather="maximize" class="align-middle fullscreen noti-icon"></i>
                    </button>
                </li>



                @php
                    $me = Auth::user();
                    $business = null;
                    $usedUsers = null;
                    $approvedUsers = null;
                    if ($me && $me->company_id) {
                        $business = App\Models\Utility\Business::find($me->company_id);
                        if ($business) {
                            $usedUsers = App\Models\User::where('company_id', $business->id)->count();
                            $approvedUsers = (int) ($business->approved_users ?? 0);
                        }
                    }
                    // compute badge class
                    $usageBadgeClass = 'bg-secondary';
                    if (!is_null($approvedUsers)) {
                        if ($approvedUsers === 0) {
                            $usageBadgeClass = 'bg-secondary';
                        } else if ($usedUsers >= $approvedUsers) {
                            $usageBadgeClass = 'bg-danger';
                        } else if ($approvedUsers > 0 && ($usedUsers / max($approvedUsers, 1)) >= 0.8) {
                            $usageBadgeClass = 'bg-warning';
                        } else {
                            $usageBadgeClass = 'bg-success';
                        }
                    }
                @endphp

                @if($business)
                    <!-- Compact chip for small screens: show avatar and usage badge -->
                    <li class="d-flex d-md-none align-items-center me-2">
                        <div class="d-flex align-items-center gap-2 rounded-pill px-2 py-1 border"
                            style="border-color:#ffb3b3;">
                            @if(!empty($business->image))
                                <img src="{{ $business->image_url }}" alt="Business Logo" class="rounded-circle"
                                    style="width:28px;height:28px;object-fit:cover;">
                            @endif
                            @if(!is_null($usedUsers) && !is_null($approvedUsers))
                                <span class="badge {{ $usageBadgeClass }} text-white fw-bold px-2">{{ $usedUsers }} /
                                    {{ $approvedUsers }}</span>
                            @endif
                        </div>
                    </li>

                    <!-- Full chip for md+ screens: avatar, business name, and usage badge -->
                    <li class="d-none d-md-flex align-items-center me-2">
                        <div class="d-flex align-items-center gap-2 rounded-pill px-2 py-1 border"
                            style="border-color:#ffb3b3;">
                            @if(!empty($business->image))
                                <img src="{{ $business->image_url }}" alt="Business Logo" class="rounded-circle"
                                    style="width:28px;height:28px;object-fit:cover;">
                            @endif
                            <span class="fw-semibold">{{ $business->name }}</span>
                            @if(!is_null($usedUsers) && !is_null($approvedUsers))
                                <span class="badge {{ $usageBadgeClass }} text-white fw-bold px-2">{{ $usedUsers }} /
                                    {{ $approvedUsers }}</span>
                            @endif
                        </div>
                    </li>
                @endif

                <li class="dropdown notification-list topbar-dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <i data-feather="bell" class="noti-icon"></i>
                        <span class="badge bg-danger rounded-circle noti-icon-badge">2</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-lg">

                        <!-- item-->
                        <div class="dropdown-item noti-title">
                            <h5 class="m-0">
                                <span class="float-end">
                                    <a href="" class="text-dark">
                                        <small>Clear All</small>
                                    </a>
                                </span>Notification
                            </h5>
                        </div>

                        <div class="noti-scroll" data-simplebar>
                            <!-- item-->
                            <a href="javascript:void(0);"
                                class="dropdown-item notify-item text-muted link-primary active">
                                <div class="notify-icon">
                                    <img src="{{ asset('backend/assets/images/users/user-12.jpg') }}"
                                        class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="notify-details">Carl Steadham</p>
                                    <small class="text-muted">5 min ago</small>
                                </div>
                                <p class="mb-0 user-msg">
                                    <small class="fs-14">Completed <span class="text-reset">Improve workflow in
                                            Figma</span></small>
                                </p>
                            </a>

                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item notify-item text-muted link-primary">
                                <div class="notify-icon">
                                    <img src="{{ asset('backend/assets/images/users/user-2.jpg') }}"
                                        class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="notify-content">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <p class="notify-details">Olivia McGuire</p>
                                        <small class="text-muted">1 min ago</small>
                                    </div>
                                </div>
                            </a>
                            {{--
                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item notify-item text-muted link-primary">
                                <div class="notify-icon">
                                    <img src="assets/images/users/user-3.jpg" class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="notify-content">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <p class="notify-details">Travis Williams</p>
                                        <small class="text-muted">7 min ago</small>
                                    </div>
                                    <p class="noti-mentioned p-2 rounded-2 mb-0 mt-2"><span
                                            class="text-primary">@Patryk</span> Please make sure that you're....</p>
                                </div>
                            </a>

                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item notify-item text-muted link-primary">
                                <div class="notify-icon">
                                    <img src="assets/images/users/user-8.jpg" class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="notify-details">Violette Lasky</p>
                                    <small class="text-muted">5 min ago</small>
                                </div>
                                <p class="mb-0 user-msg">
                                    <small class="fs-14">Completed <span class="text-reset">Create new
                                            components</span></small>
                                </p>
                            </a>

                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item notify-item text-muted link-primary">
                                <div class="notify-icon">
                                    <img src="assets/images/users/user-5.jpg" class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="notify-details">Ralph Edwards</p>
                                    <small class="text-muted">5 min ago</small>
                                </div>
                                <p class="mb-0 user-msg">
                                    <small class="fs-14">Completed <span class="text-reset">Improve workflow in
                                            React</span></small>
                                </p>
                            </a>

                            <!-- item-->
                            <a href="javascript:void(0);" class="dropdown-item notify-item text-muted link-primary">
                                <div class="notify-icon">
                                    <img src="assets/images/users/user-6.jpg" class="img-fluid rounded-circle" alt="" />
                                </div>
                                <div class="notify-content">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <p class="notify-details">Jocab jones</p>
                                        <small class="text-muted">7 min ago</small>
                                    </div>
                                    <p class="noti-mentioned p-2 rounded-2 mb-0 mt-2"><span
                                            class="text-reset">@Patryk</span> Please make sure that you're....</p>
                                </div>
                            </a>--}}
                        </div>

                        <!-- All-->
                        <a href="javascript:void(0);"
                            class="dropdown-item text-center text-primary notify-item notify-all">
                            View all
                            <i class="fe-arrow-right"></i>
                        </a>
                    </div>
                </li>

                @php
                    $id = Auth::user()->id;
                    $profileData = App\Models\User::find($id);
                    $rawRole = trim((string) ($profileData->role ?? 'User'));
                    // Normalize role variants to a consistent label
                    $roleLabel = match (strtolower($rawRole)) {
                        'super admin' => 'Super Admin',
                        'admin' => 'Admin',
                        default => 'User',
                    };
                    // Map role to a Bootstrap background color class
                    $roleBadgeClass = match ($roleLabel) {
                        'Super Admin' => 'bg-danger',
                        'Admin' => 'bg-success',
                        default => 'bg-secondary',
                    };
                @endphp
                <style>
                    .custom-orange-badge {
                        background-color: var(--theme-color) !important;
                        color: #fff !important;
                    }
                </style>

                <li class="dropdown notification-list topbar-dropdown">
                    <a class="nav-link dropdown-toggle nav-user me-0 position-relative d-flex flex-column align-items-center p-0"
                        data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false"
                        aria-label="Open profile menu">
                        <img src="{{ $profileData->photo_url }}" alt="user avatar" class="rounded-circle">
                        <span class="badge {{$roleBadgeClass}} text-uppercase mt-1"
                            style="font-size:.55rem;line-height:1;">{{ $roleLabel }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end profile-dropdown " style="min-width: 20rem;">
                        <!-- item-->
                        <div class="dropdown-header noti-title p-2">
                            <div class="d-flex align-items-center">
                                <img src="{{ $profileData->photo_url }}" alt="user-image" class="rounded me-3"
                                    style="width:48px;height:48px;object-fit:cover;">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="m-0 text-dark">{{ $profileData->name }}</h5>
                                        <span class="badge {{$roleBadgeClass}} text-uppercase">{{ $roleLabel }}</span>
                                    </div>
                                    <div class="text-muted text-sm">{{ $profileData->email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- item-->
                        <a href="{{ route('admin.profile') }}" class="dropdown-item notify-item">
                            <i class="mdi mdi-account-circle-outline fs-16 align-middle"></i>
                            <span>My Account</span>
                        </a>

                        <!-- item-->
                        <a href="{{ route('lock.show') }}" class="dropdown-item notify-item">
                            <i class="mdi mdi-lock-outline fs-16 align-middle"></i>
                            <span>Lock Screen</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- item-->
                        <a href="{{ route('admin.logout') }}" class="dropdown-item notify-item js-logout-link">
                            <i class="mdi mdi-location-exit text-danger fs-16 align-middle"></i>
                            <span class="text-danger">Logout</span>
                        </a>
                    </div>
                </li>

            </ul>
        </div>

    </div>

</div>

{{-- Standalone fullscreen handler - guaranteed to work regardless of app.js --}}
<script>
    (function () {
        function initFullscreen() {
            var btn = document.querySelector('[data-action="fullscreen"]');
            if (!btn) return;

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var body = document.body;
                var docEl = document.documentElement;

                // Check if currently in fullscreen
                var isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);

                if (isFullscreen) {
                    // Exit fullscreen
                    if (document.exitFullscreen) { document.exitFullscreen(); }
                    else if (document.webkitExitFullscreen) { document.webkitExitFullscreen(); }
                    else if (document.mozCancelFullScreen) { document.mozCancelFullScreen(); }
                    else if (document.msExitFullscreen) { document.msExitFullscreen(); }
                } else {
                    // Enter fullscreen
                    if (docEl.requestFullscreen) { docEl.requestFullscreen(); }
                    else if (docEl.webkitRequestFullscreen) { docEl.webkitRequestFullscreen(); }
                    else if (docEl.mozRequestFullScreen) { docEl.mozRequestFullScreen(); }
                    else if (docEl.msRequestFullscreen) { docEl.msRequestFullscreen(); }
                }
            });

            // Sync body class with fullscreen state
            function onFullscreenChange() {
                var isFS = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
                if (isFS) {
                    document.body.classList.add('fullscreen-enable');
                } else {
                    document.body.classList.remove('fullscreen-enable');
                }
            }
            document.addEventListener('fullscreenchange', onFullscreenChange);
            document.addEventListener('webkitfullscreenchange', onFullscreenChange);
            document.addEventListener('mozfullscreenchange', onFullscreenChange);
            document.addEventListener('MSFullscreenChange', onFullscreenChange);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFullscreen);
        } else {
            initFullscreen();
        }
    })();
</script>