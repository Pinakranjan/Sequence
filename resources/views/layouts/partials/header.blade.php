<div class="header-bg sticky-top">
    <header class="main-header-section ">
        <div class="header-wrapper">
            <div class="header-left">
                <div class="sidebar-opner"><i class="fal fa-bars" aria-hidden="true"></i></div>
                <a target="_blank" class=" view-website" href="{{ route('home') }}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 22C6.47715 22 2 17.5228 2 12C2 9.20746 3.14465 6.68227 4.99037 4.86802M12 22C11.037 21.2864 11.1907 20.4555 11.6738 19.6247C12.4166 18.3474 12.4166 18.3474 12.4166 16.6444C12.4166 14.9414 13.4286 14.1429 17 14.8571C18.6047 15.1781 19.7741 12.9609 21.8573 13.693M12 22C16.9458 22 21.053 18.4096 21.8573 13.693M4.99037 4.86802C5.83966 4.95765 6.31517 5.41264 7.10496 6.24716C8.6044 7.83152 10.1038 7.96372 11.1035 7.4356C12.6029 6.64343 11.3429 5.3603 13.1027 4.66298C14.1816 4.23551 14.3872 3.11599 13.8766 2.17579M4.99037 4.86802C6.79495 3.09421 9.26969 2 12 2C12.6414 2 13.2687 2.06039 13.8766 2.17579M21.8573 13.693C21.9511 13.1427 22 12.5771 22 12C22 7.11857 18.5024 3.05405 13.8766 2.17579"
                            stroke="black" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                    <p>{{ __('View Website') }}</p>
                </a>
            </div>

            <div class="header-middle"></div>
            <div class="header-right ">
                <div class="language-change">

                    <div class="dropdown">
                        <button class="btn language-dropdown dropdown-toggle language-btn border-0" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('flags/' . languages()[app()->getLocale()]['flag'] . '.svg') }}" alt=""
                                class="flag-icon ">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.00002 11.1667C7.87202 11.1667 7.744 11.118 7.64666 11.02L2.98 6.35337C2.78466 6.15804 2.78466 5.84135 2.98 5.64601C3.17533 5.45068 3.49202 5.45068 3.68735 5.64601L8.00067 9.95933L12.314 5.64601C12.5093 5.45068 12.826 5.45068 13.0213 5.64601C13.2167 5.84135 13.2167 6.15804 13.0213 6.35337L8.35467 11.02C8.256 11.118 8.12802 11.1667 8.00002 11.1667Z"
                                    fill="#404040" />
                            </svg>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-scroll">
                            @foreach (languages() as $key => $language)
                                <li class="language-li">
                                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['lang' => $key]) }}">
                                        <div class="language-img-container">
                                            <img src="{{ asset('flags/' . $language['flag'] . '.svg') }}" alt=""
                                                class="flag-icon me-2">
                                            {{ $language['name'] }}
                                        </div>
                                    </a>
                                    @if (app()->getLocale() == $key)
                                        <i class="fas fa-check language-check"></i>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                </div>
                <div class="profile-info dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="d-flex align-items-center gap-2">
                        <img src="{{ asset(Auth::user()->image ?? 'assets/images/icons/default-user.png') }}"
                            alt="Profile">
                        <div class="d-flex profile-text   ">
                            <p class="name">{{ Str::limit(Auth::user()->name, 15, '.') }}</p>
                            <p class="text">{{ ucfirst(auth()->user()->role ?? '')}}</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="{{ route('admin.profile') }}"> <i class="fal fa-user"></i>
                                {{ __('My Profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="logoutButton">
                                <i class="far fa-sign-out"></i> {{ __('Logout') }}
                                <form action="{{ route('logout') }}" method="post" id="logoutForm">
                                    @csrf
                                </form>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</div>