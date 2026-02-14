<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'Verification Code' }}</title>
    <style>
        /* Simple, email-friendly styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f6f6f8;
            color: #111827;
            margin: 0;
            padding: 20px;
        }

        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
        }

        .email-card {
            background: #ffffff;
            padding: 26px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02);
        }

        h1 {
            font-size: 20px;
            margin: 0 0 8px 0;
        }

        p {
            margin: 12px 0;
            line-height: 1.4;
        }

        .code {
            display: inline-block;
            font-size: 22px;
            letter-spacing: 6px;
            padding: 12px 18px;
            border-radius: 8px;
            background: #f4f6f8;
            border: 1px solid #e6e9ee;
            font-weight: 600;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }

        .footer {
            margin-top: 18px;
            font-size: 13px;
            color: #9ca3af;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .code {
                font-size: 20px;
                letter-spacing: 4px;
                padding: 10px 14px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-card">
            <h1>{{ $title ?? 'Your verification code' }}</h1>

            <p>Hi {{ $name ?? 'there' }},</p>

            <p>Use the verification code below to verify your email for <strong>{{ config('app.name') }}</strong>:</p>

            <p style="text-align:center;"><span class="code">{{ $code ?? '—' }}</span></p>

            <p class="muted">This code will expire in {{ $minutes ?? 15 }} minutes. If you did not request this code,
                you can safely ignore this email.</p>

            <p>Thanks,<br>{{ config('app.name') }}</p>

            <div class="footer">
                <p>If you need help, contact us at <a
                        href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>
                <p class="muted">This is an automated message — please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>

</html>