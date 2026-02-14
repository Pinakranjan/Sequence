<section class="footer-container d-print-none">
    <style>
        /* Auth footer: keep business name inline normally, break to its own line on small mobile portrait */
        .auth-business-name {
            display: inline;
            font-weight: 400;
        }

        @media (max-width: 576px) and (orientation: portrait) {
            .auth-business-name {
                display: block;
                margin-top: 6px;
            }
        }

        /* Footer styling: transparent and above content; allow full business name without truncation */
        .auth-footer-pill {
            display: inline-block;
            background: transparent;
            /* no backdrop blur to keep fully transparent */
            color: #6c757d;
            padding: 6px 14px;
            border-radius: 999px;
            border: none;
            box-shadow: none;
            position: relative;
            z-index: 9999;
            /* ensure it's above other auth content */
            width: auto;
            white-space: normal;
            /* allow wrapping so full business name shows */
        }

        @media (max-width: 576px) and (orientation: portrait) {
            .auth-footer-pill {
                display: block;
            }
        }
    </style>

    <footer
        class="footer-content container-fluid d-flex align-items-center justify-content-center flex-wrap py-3 mt-4 ms-0"
        style="background: #f0e8c5;">
        <div class="fs-13 text-center">
            <div class="auth-footer-pill" role="contentinfo">
                &copy; {{ date('Y') }} - Made with
                <span aria-hidden="true"
                    style="display:inline-block; width:18px; vertical-align:middle; color: #e02424;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18"
                        fill="currentColor">
                        <path
                            d="M12 21s-7.333-4.868-9.333-7.333C-0.333 9.667 3.2 4 8 4c1.833 0 3.333.917 4 2.167C12.667 4.917 14.167 4 16 4c4.8 0 8.333 5.667 5.333 9.667C19.333 16.132 12 21 12 21z" />
                    </svg>
                </span>
                by <a href="#" class="auth-business-name"
                    style="color: #6c757d; font-weight: 400; text-decoration: none;">Hepta Infotech Services LLP</a>
            </div>
        </div>
    </footer>
</section>