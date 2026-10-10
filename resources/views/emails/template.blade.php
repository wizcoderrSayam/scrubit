@php
    $settings = app(\App\Services\SettingsService::class);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->get('business.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f5fa;font-family:Inter,Segoe UI,Helvetica,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5fa;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;border-bottom:1px solid #dce8f6;">
                            <img src="{{ asset('images/brand/logo.webp') }}" width="140" alt="{{ $settings->get('business.name') }}" style="display:block;height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;font-size:15px;line-height:1.6;">
                            {!! $body !!}

                            @if ($actionUrl)
                                <p style="margin:28px 0 8px;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#046bd2;color:#ffffff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px;">{{ $actionText }}</a>
                                </p>
                                <p style="font-size:12px;color:#64748b;word-break:break-all;">If the button does not work, copy this link into your browser: {{ $actionUrl }}</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;background:#1e293b;color:#cbd5e1;font-size:12px;line-height:1.6;">
                            {{ $settings->get('business.name') }} &middot; {{ $settings->get('business.phone') }} &middot; {{ $settings->get('business.email') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
