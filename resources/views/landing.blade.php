@extends('layouts.auth.app')

@section('title')
    {{ __('Welcome') }}
@endsection

@section('main_content')
    {{-- Top Navigation Bar --}}
    <nav class="landing-nav">
        <div class="landing-nav__inner">
            <a href="{{ route('home') }}" class="landing-nav__brand">
                <div class="landing-nav__logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 17H22M19 17V11H22M19 17L13 21L7 17M7 17H4M7 17V11H4M7 11L13 7L19 11M13 7V3M13 7L7 11"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <span class="landing-nav__brand-text">{{ config('app.name', 'Sequence') }}</span>
            </a>
            <div class="landing-nav__actions">
                <a href="{{ route('login') }}" class="landing-btn landing-btn--outline">Sign In</a>
                <a href="{{ route('register') }}" class="landing-btn landing-btn--primary">Sign Up</a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="landing-hero">
        <div class="landing-hero__bg">
            <div class="landing-hero__gradient"></div>
            <div class="landing-hero__grid-pattern"></div>
        </div>
        <div class="landing-hero__content">
            <div class="landing-hero__badge" data-aos="fade-down">
                <span class="landing-hero__badge-dot"></span>
                Vehicle Logistics Platform
            </div>
            <h1 class="landing-hero__title" data-aos="fade-up" data-aos-delay="100">
                Smart Vehicle <span class="landing-hero__highlight">Sequencing</span>
                <br>for Modern Logistics
            </h1>
            <p class="landing-hero__subtitle" data-aos="fade-up" data-aos-delay="200">
                Streamline your vehicle dispatch, optimize route sequences, and manage
                fleet operations with an intelligent logistics platform built for efficiency.
            </p>
            <div class="landing-hero__cta" data-aos="fade-up" data-aos-delay="300">
                <a href="{{ route('register') }}" class="landing-btn landing-btn--primary landing-btn--lg">
                    Get Started
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.167 10H15.833M15.833 10L10 4.167M15.833 10L10 15.833" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
                <a href="{{ route('login') }}" class="landing-btn landing-btn--ghost landing-btn--lg">Sign In</a>
            </div>
        </div>

        {{-- Floating stat cards --}}
        <div class="landing-hero__stats" data-aos="fade-up" data-aos-delay="400">
            <div class="landing-stat-card">
                <div class="landing-stat-card__icon landing-stat-card__icon--blue">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M9 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM19 17a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"
                            stroke="currentColor" stroke-width="2" />
                        <path d="M13 16V6h5l3 5v5h-1M13 16H9M5 11H1M3 9v4" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="landing-stat-card__text">
                    <span class="landing-stat-card__number">500+</span>
                    <span class="landing-stat-card__label">Vehicles Managed</span>
                </div>
            </div>
            <div class="landing-stat-card">
                <div class="landing-stat-card__icon landing-stat-card__icon--green">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="landing-stat-card__text">
                    <span class="landing-stat-card__number">98%</span>
                    <span class="landing-stat-card__label">On-time Dispatch</span>
                </div>
            </div>
            <div class="landing-stat-card">
                <div class="landing-stat-card__icon landing-stat-card__icon--orange">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8Z"
                            stroke="currentColor" stroke-width="2" />
                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                    </svg>
                </div>
                <div class="landing-stat-card__text">
                    <span class="landing-stat-card__number">50+</span>
                    <span class="landing-stat-card__label">Route Networks</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="landing-features">
        <div class="landing-features__inner">
            <h2 class="landing-section-title" data-aos="fade-up">Why Choose <span
                    class="landing-hero__highlight">Sequence</span>?</h2>
            <p class="landing-section-desc" data-aos="fade-up" data-aos-delay="100">
                Everything you need to manage vehicle logistics in one platform.
            </p>
            <div class="landing-features__grid">
                <div class="landing-feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="landing-feature-card__icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                            <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                            <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                            <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </div>
                    <h3>Fleet Dashboard</h3>
                    <p>Real-time overview of all vehicles, drivers, and active routes with live status tracking.</p>
                </div>
                <div class="landing-feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="landing-feature-card__icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7l10 5 10-5-10-5ZM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>Smart Sequencing</h3>
                    <p>Automatically optimize vehicle dispatch order based on priority, distance, and load capacity.</p>
                </div>
                <div class="landing-feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="landing-feature-card__icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" />
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>
                    <h3>Multi-tenant</h3>
                    <p>Manage multiple businesses, branches and user roles from a single centralized platform.</p>
                </div>
                <div class="landing-feature-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="landing-feature-card__icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10Z"
                                stroke="currentColor" stroke-width="2" />
                            <path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>Compliance Ready</h3>
                    <p>Built-in audit trails, role-based permissions, and business compliance tools out of the box.</p>
                </div>
            </div>
        </div>
    </section>
    {{-- Footer removed to use the layout's footer --}}
@endsection

@push('css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    <style>
        /* ─── Light Theme Reset ─── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body.auth-shell {
            background: #f8fafc;
            /* Light bg */
            color: #1e293b;
            /* Dark text */
            font-family: 'Inter', -apple-system, sans-serif;
        }

        .auth-shell__main {
            min-height: 100vh;
        }

        /* ─── Navigation ─── */
        .landing-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 16px 24px;
            background: rgba(255, 255, 255, 0.85);
            /* Light glass */
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .landing-nav__inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .landing-nav__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .landing-nav__logo-icon {
            width: 32px;
            height: 32px;
            color:
                {{ config('services.theme.color') }}
            ;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .landing-nav__logo-icon svg {
            width: 100%;
            height: 100%;
        }

        .landing-nav__brand-text {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .landing-nav__actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        /* ─── Buttons ─── */
        .landing-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s ease;
            border: none;
        }

        .landing-btn--primary {
            background: linear-gradient(135deg,
                    {{ config('services.theme.color') }}
                    ,
                    {{ config('services.theme.color') }}
                );
            color: #fff;
            box-shadow: 0 4px 15px
                {{ config('services.theme.color') }}
                40;
        }

        .landing-btn--primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px
                {{ config('services.theme.color') }}
                59;
            color: #fff;
        }

        .landing-btn--outline {
            background: transparent;
            color: #475569;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .landing-btn--outline:hover {
            background: rgba(0, 0, 0, 0.03);
            color: #1e293b;
            border-color: rgba(0, 0, 0, 0.2);
        }

        .landing-btn--ghost {
            background: rgba(0, 0, 0, 0.04);
            color: #475569;
            border: 1px solid transparent;
        }

        .landing-btn--ghost:hover {
            background: rgba(0, 0, 0, 0.08);
            color: #1e293b;
        }

        .landing-btn--lg {
            padding: 14px 32px;
            font-size: 1rem;
            border-radius: 12px;
        }

        /* ─── Hero ─── */
        .landing-hero {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 120px 24px 60px;
            text-align: center;
        }

        .landing-hero__bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: #f8fafc;
        }

        .landing-hero__gradient {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 50% 0%,
                    {{ config('services.theme.color') }}
                    14 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 80% 30%, rgba(59, 130, 246, 0.05) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 20% 60%, rgba(168, 85, 247, 0.05) 0%, transparent 60%);
        }

        .landing-hero__grid-pattern {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, black 40%, transparent 80%);
        }

        .landing-hero__content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }

        .landing-hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px 6px 10px;
            background:
                {{ config('services.theme.color') }}
                14;
            border: 1px solid
                {{ config('services.theme.color') }}
                26;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            color:
                {{ config('services.theme.color') }}
            ;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .landing-hero__badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background:
                {{ config('services.theme.color') }}
            ;
            box-shadow: 0 0 8px
                {{ config('services.theme.color') }}
                66;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(1.3);
            }
        }

        .landing-hero__title {
            font-size: clamp(2.2rem, 5.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.15;
            color: #0f172a;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .landing-hero__highlight {
            background: linear-gradient(135deg,
                    {{ config('services.theme.color') }}
                    ,
                    {{ config('services.theme.color') }}
                );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .landing-hero__subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: #64748b;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto 36px;
        }

        .landing-hero__cta {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ─── Stats ─── */
        .landing-hero__stats {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 72px;
        }

        .landing-stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 16px;
            padding: 18px 24px;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .landing-stat-card:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color:
                {{ config('services.theme.color') }}
                1a;
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        }

        .landing-stat-card__icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .landing-stat-card__icon svg {
            width: 22px;
            height: 22px;
        }

        .landing-stat-card__icon--blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .landing-stat-card__icon--green {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .landing-stat-card__icon--orange {
            background:
                {{ config('services.theme.color') }}
                1a;
            color:
                {{ config('services.theme.color') }}
            ;
        }

        .landing-stat-card__text {
            display: flex;
            flex-direction: column;
        }

        .landing-stat-card__number {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
        }

        .landing-stat-card__label {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }

        /* ─── Features ─── */
        .landing-features {
            padding: 100px 24px;
            background: #fff;
        }

        .landing-features__inner {
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
        }

        .landing-section-title {
            font-size: clamp(1.6rem, 3.5vw, 2.4rem);
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .landing-section-desc {
            font-size: 1.05rem;
            color: #64748b;
            margin-bottom: 56px;
        }

        .landing-features__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            text-align: left;
        }

        .landing-feature-card {
            padding: 32px 28px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            transition: all 0.35s ease;
        }

        .landing-feature-card:hover {
            background: #fff;
            border-color:
                {{ config('services.theme.color') }}
                33;
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
        }

        .landing-feature-card__icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg,
                    {{ config('services.theme.color') }}
                    1a,
                    {{ config('services.theme.color') }}
                    0d);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .landing-feature-card__icon svg {
            width: 24px;
            height: 24px;
            color:
                {{ config('services.theme.color') }}
            ;
        }

        .landing-feature-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .landing-feature-card p {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.7;
        }

        /* ─── Responsive ─── */
        @media (max-width: 640px) {
            .landing-nav {
                padding: 12px 16px;
            }

            .landing-btn--lg {
                padding: 12px 24px;
                font-size: 0.9rem;
            }

            .landing-hero {
                padding: 100px 16px 40px;
            }

            .landing-hero__stats {
                flex-direction: column;
                align-items: center;
            }

            .landing-stat-card {
                width: 100%;
                max-width: 300px;
            }

            .landing-features {
                padding: 60px 16px;
            }
        }
    </style>
@endpush

@push('js')
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            AOS.init({ once: true, duration: 700, offset: 50 });
        });
    </script>
@endpush