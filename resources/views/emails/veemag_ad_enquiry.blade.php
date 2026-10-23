<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Advertising enquiry</title>
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
                            VeeMags &middot; Advertising Enquiry
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:24px;font-weight:bold;line-height:1.25;padding-bottom:24px;color:#ffffff;">
                            {{ $lead->business_name ?: $lead->name }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-bottom:6px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;">Contact name</td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:18px;font-size:16px;color:#ffffff;">{{ $lead->name }}</td>
                    </tr>

                    @if($lead->business_name)
                        <tr>
                            <td style="padding-bottom:6px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;">Business</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom:18px;font-size:16px;color:#ffffff;">{{ $lead->business_name }}</td>
                        </tr>
                    @endif

                    @if($lead->phone)
                        <tr>
                            <td style="padding-bottom:6px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;">Phone</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom:18px;font-size:16px;color:#ffffff;">
                                <a href="tel:{{ $lead->phone }}" style="color:#ffffff;text-decoration:none;">{{ $lead->phone }}</a>
                            </td>
                        </tr>
                    @endif

                    @if($lead->address)
                        <tr>
                            <td style="padding-bottom:6px;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;">Address</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom:18px;font-size:16px;color:#ffffff;">{{ $lead->address }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td style="border-top:1px solid #2a2733;padding-top:18px;font-size:13px;color:#9a95a3;line-height:1.6;">
                            Raised from the GoKoncentrate app.
                            @if($issueTitle)
                                <br>Issue: <span style="color:#e9e7ee;">{{ $issueTitle }}</span>
                            @endif
                            @if($sectionTitle)
                                <br>Section: <span style="color:#e9e7ee;">{{ $sectionTitle }}</span>
                            @endif
                            <br>Received: {{ $lead->created_at?->format('j M Y, g:ia') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
