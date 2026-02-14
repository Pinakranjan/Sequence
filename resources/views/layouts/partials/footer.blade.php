<section class="footer-container d-print-none">
    <style>
        /* Footer: keep business name inline normally, break to its own line on small portrait screens */
        .business-name {
            display: inline;
            font-weight: 400;
        }

        @media (max-width: 576px) and (orientation: portrait) {
            .business-name {
                display: block;
                margin-top: 6px;
            }
        }
    </style>
    <footer
        class="footer-content container-fluid d-flex align-items-center justify-content-center justify-content-sm-between flex-wrap py-3 mt-4 ms-0 ">
        <p class="mb-0 me-3"> {{ Str::words(get_option('general')['copy_right'] ?? '', 10, '...') }}</p>
        <p class="mb-0">{{ Str::words(get_option('general')['admin_footer_text'] ?? '', 5, '...')}}: <a
                class="text-ancor business-name" href="{{ get_option('general')['admin_footer_link'] ?? '' }}"
                target="_blank">{{ Str::words(get_option('general')['admin_footer_link_text'] ?? '', 5, '...') }}</a>
        </p>
    </footer>
</section>