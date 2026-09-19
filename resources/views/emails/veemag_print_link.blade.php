@php
    $symbol = $currency === 'USD' ? '$' : $currency . ' ';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your print copy</title>
</head>
<body style="margin:0;padding:0;background:#0c0b11;font-family:Arial,Helvetica,sans-serif;color:#e9e7ee;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background:#0c0b11;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px;background:#16141d;border-radius:12px;padding:32px;">
                    <tr>
                        <td style="font-size:12px;letter-spacing:.22em;text-transform:uppercase;color:#F26522;padding-bottom:16px;">
                            VeeMag &middot; Print Edition
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:26px;font-weight:bold;line-height:1.2;padding-bottom:8px;color:#ffffff;">
                            {{ $issue->title }}
                        </td>
                    </tr>
                    @if($issue->issue_label)
                        <tr>
                            <td style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;padding-bottom:20px;">
                                {{ $issue->publication->title ?? '' }} &middot; {{ $issue->issue_label }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="font-size:15px;line-height:1.6;color:#c9c6cf;padding-bottom:24px;">
                            You asked for a printed copy of this issue. Your checkout is ready &mdash;
                            it is held for you at the link below, so you can finish on any device.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:15px;line-height:1.6;color:#c9c6cf;padding-bottom:24px;">
                            <strong style="color:#ffffff;">{{ $symbol }}{{ number_format($price, 2) }}</strong>
                            per copy, plus shipping calculated at checkout.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:24px;">
                            <a href="{{ $checkoutUrl }}"
                                style="display:inline-block;background:#F26522;color:#ffffff;text-decoration:none;
                                       font-weight:bold;font-size:15px;padding:14px 28px;border-radius:999px;">
                                Complete your order
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px;line-height:1.6;color:#79747f;border-top:1px solid rgba(255,255,255,.08);padding-top:20px;">
                            If the button does not work, paste this link into your browser:<br>
                            <span style="color:#9a95a3;word-break:break-all;">{{ $checkoutUrl }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
