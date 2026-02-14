<header class="header-section home-header">
    <nav class="navbar navbar-expand-lg p-0">
        <div class="container">
            <button class="navbar-toggler p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#staticBackdrop"
                aria-controls="staticBackdrop">
                <i class="fa fa-bars" aria-hidden="true"></i>
            </button>
            <a href="{{ route('home') }}" class="header-logo">
                <img src="{{ asset($page_data['web_logo_one'] ?? 'assets/images/icons/upload-icon.svg') }}" alt="header-logo" />
            </a>

            @php($headerBtnRoute = data_get($page_data, 'headings.header_btn_link'))
            <a href="{{ ($headerBtnRoute && Route::has($headerBtnRoute)) ? route($headerBtnRoute) : route('login') }}"  class="get-app-btn login-btn ">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M5.48131 12.9012C4.30234 13.6032 1.21114 15.0366 3.09389 16.8304C4.01359 17.7065 5.03791 18.3332 6.32573 18.3332H13.6743C14.9621 18.3332 15.9864 17.7065 16.9061 16.8304C18.7888 15.0366 15.6977 13.6032 14.5187 12.9012C11.754 11.2549 8.24599 11.2549 5.48131 12.9012Z"
                        fill="#1C1C1C" />
                    <path
                        d="M13.75 5.4165C13.75 7.48757 12.0711 9.1665 10 9.1665C7.92893 9.1665 6.25 7.48757 6.25 5.4165C6.25 3.34544 7.92893 1.6665 10 1.6665C12.0711 1.6665 13.75 3.34544 13.75 5.4165Z"
                        fill="#1C1C1C" />
                </svg>
                {{ $page_data['headings']['header_btn_text'] ?? 'Login' }}
            </a>

            <!-- Mobile Menu -->
            <div class="offcanvas offcanvas-start mobile-menu" data-bs-backdrop="static" tabindex="-1"
                id="staticBackdrop" aria-labelledby="staticBackdropLabel">
                <div class="offcanvas-header home-offcanvas-header">
                    <a href="{{ route('home') }}" class="header-logo"><img
                            src="{{ asset($page_data['web_logo_one'] ?? 'assets/images/icons/upload-icon.svg') }}"
                            alt="header-logo" /></a>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="offcanvas-body">
                    <div class="accordion accordion-flush mb-30" id="sidebarMenuAccordion">
                        <div class="accordion-item">
                            <a href="{{ route('home') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Home') }}</a>
                        </div>
                        <div class="accordion-item">
                            <a href="{{ route('about.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('About Us') }}</a>
                        </div>
                        <div class="accordion-item">
                            <a href="{{ route('plan.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Pricing') }}</a>
                        </div>
                        <div class="accordion-item">
                            <a href="javascript:void(0);" class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#support-menu" aria-expanded="false"
                                aria-controls="support-menu">{{ __('Pages') }}</a>
                            <div id="support-menu" class="accordion-collapse collapse"
                                data-bs-parent="#sidebarMenuAccordion">
                                <ul class="accordion-body p-0">
                                    <li>
                                        <a href="{{ route('blogs.index') }}">{{ __('Blog') }}</a>
                                        <p class="mb-0 arrow">
                                            <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                            </svg>
                                        </p>
                                    </li>
                                    <li>
                                        <a href="{{ route('term.index') }}"> {{ __('Terms & Conditions') }} </a>
                                        <p class="mb-0 arrow">
                                            <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                            </svg>
                                        </p>
                                    </li>
                                    <li>
                                        <a href="{{ route('policy.index') }}"> {{ __('Privacy Policy') }} </a>
                                        <p class="mb-0 arrow">
                                            <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                            </svg>
                                        </p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <a href="{{ route('contact.index') }}" class="accordion-button without-sub-menu"
                                type="button">{{ __('Contact Us') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link"
                            aria-current="page">{{ __('Home') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" aria-current="page"
                            href="{{ route('about.index') }}">{{ __('About Us') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" aria-current="page"
                            href="{{ route('plan.index') }}">{{ __('Pricing') }}</a>
                    </li>

                    <li class="nav-item menu-dropdown">
                            <a class="nav-link" aria-current="page" href="javascript:void(0);">{{ __('Pages') }} <span class="arrow">
                                <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                    <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                </svg>
                            </span></a>
                        <ul class="dropdown-content">
                                <li>
                                <a class="dropdown-item"
                                    href="{{ route('blogs.index') }}">{{ __('Blog') }}<span>
                                        <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                            <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                        </svg>
                                    </span></a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('term.index') }}">{{ __('Terms & Conditions') }}
                                    <span>
                                        <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                            <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                        </svg>
                                    </span></a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('policy.index') }}">{{ __('Privacy Policy') }}<span>
                                        <svg class="nav-arrow-icon" width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                            <path d="M1.02302 15.477L1.02302 15.477C0.936464 15.3904 0.867802 15.2877 0.820956 15.1746L1.05193 15.0789L0.820956 15.1746C0.774111 15.0615 0.75 14.9403 0.75 14.8178C0.75 14.6954 0.774111 14.5742 0.820956 14.4611C0.867802 14.348 0.936464 14.2453 1.02302 14.1587L1.02304 14.1587L7.18255 8L1.02304 1.84131L1.02302 1.8413C0.848209 1.66649 0.75 1.42939 0.75 1.18216C0.75 0.934938 0.848209 0.697839 1.02302 0.523024C1.19784 0.34821 1.43494 0.25 1.68216 0.25C1.92939 0.25 2.16649 0.34821 2.3413 0.523024L9.15904 7.34076L1.02302 15.477ZM1.02302 15.477C1.10958 15.5635 1.21235 15.6322 1.32544 15.679C1.43853 15.7259 1.55975 15.75 1.68216 15.75C1.80458 15.75 1.92579 15.7259 2.03889 15.679C2.15198 15.6322 2.25474 15.5635 2.3413 15.477L9.15904 8.65924L1.02302 15.477Z" fill="currentColor" stroke="currentColor" stroke-width="0.5" />
                                        </svg>
                                    </span></a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page"
                            href="{{ route('contact.index') }}">{{ __('Contact Us') }}</a>
                    </li>
                </ul>

                <a href="{{ ($headerBtnRoute && Route::has($headerBtnRoute)) ? route($headerBtnRoute) : route('login') }}"
                    class="get-app-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M5.48131 12.9012C4.30234 13.6032 1.21114 15.0366 3.09389 16.8304C4.01359 17.7065 5.03791 18.3332 6.32573 18.3332H13.6743C14.9621 18.3332 15.9864 17.7065 16.9061 16.8304C18.7888 15.0366 15.6977 13.6032 14.5187 12.9012C11.754 11.2549 8.24599 11.2549 5.48131 12.9012Z"
                            fill="#1C1C1C" />
                        <path
                            d="M13.75 5.4165C13.75 7.48757 12.0711 9.1665 10 9.1665C7.92893 9.1665 6.25 7.48757 6.25 5.4165C6.25 3.34544 7.92893 1.6665 10 1.6665C12.0711 1.6665 13.75 3.34544 13.75 5.4165Z"
                            fill="#1C1C1C" />
                    </svg>

                    {{ $page_data['headings']['header_btn_text'] ?? 'Login' }}

                </a>
            </div>
        </div>
    </nav>
</header>
