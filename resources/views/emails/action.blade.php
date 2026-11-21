<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subjectLine }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-card { padding: 28px 22px !important; }
            .email-header { padding: 26px 22px !important; }
            .email-title { font-size: 25px !important; line-height: 32px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f2f7f6;color:#172033;font-family:Arial,'Helvetica Neue',sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $subjectLine }} — an update from Astha Engineering.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f2f7f6;">
        <tr>
            <td align="center" style="padding:36px 14px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:600px;max-width:600px;">
                    <tr>
                        <td class="email-header" style="padding:30px 36px;background:#103f39;background-image:linear-gradient(135deg,#0d4a42 0%,#2f9f96 100%);border-radius:20px 20px 0 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="58" valign="middle">
                                        <div style="width:52px;height:52px;line-height:52px;text-align:center;border-radius:14px;background:#ffffff;color:#168a80;font-size:22px;font-weight:800;letter-spacing:-1px;">AE</div>
                                    </td>
                                    <td valign="middle" style="padding-left:14px;">
                                        <div style="color:#ffffff;font-size:21px;font-weight:800;line-height:26px;">Astha Engineering</div>
                                        <div style="color:#c9f2ed;font-size:12px;font-weight:700;letter-spacing:1.6px;line-height:20px;text-transform:uppercase;">Inspection &amp; Safety</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-card" style="padding:38px 40px;background:#ffffff;border:1px solid #dceae8;border-top:0;">
                            <div style="display:inline-block;padding:7px 12px;border-radius:999px;background:#e7f7f4;color:#168a80;font-size:11px;font-weight:800;letter-spacing:1.1px;text-transform:uppercase;">Account notification</div>
                            <h1 class="email-title" style="margin:20px 0 18px;color:#13213a;font-size:30px;line-height:38px;font-weight:800;letter-spacing:-0.5px;">{{ $subjectLine }}</h1>
                            <div style="height:3px;width:54px;background:#2f9f96;border-radius:4px;margin-bottom:24px;"></div>
                            <div style="color:#536174;font-size:16px;line-height:27px;">
                                {!! nl2br(e($body)) !!}
                            </div>

                            @if($attachmentPath)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:28px;background:#f0f9f7;border:1px solid #cce8e3;border-radius:12px;">
                                    <tr>
                                        <td width="42" style="padding:16px 0 16px 18px;color:#168a80;font-size:24px;">&#128206;</td>
                                        <td style="padding:16px 18px 16px 10px;color:#28534f;font-size:14px;line-height:21px;">
                                            <strong style="display:block;color:#173f3b;">Document attached</strong>
                                            {{ $attachmentName ?: 'Please find your document attached to this email.' }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <div style="margin-top:32px;padding-top:24px;border-top:1px solid #e7eeed;color:#536174;font-size:15px;line-height:24px;">
                                Warm regards,<br>
                                <strong style="color:#173f3b;">The Astha Engineering Team</strong>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px;text-align:center;background:#e7f1ef;border:1px solid #d5e6e3;border-top:0;border-radius:0 0 20px 20px;color:#71817e;font-size:12px;line-height:19px;">
                            This is an automated service message from Astha Engineering.<br>
                            Please keep this email for your records.
                            <div style="margin-top:10px;color:#91a09d;">&copy; {{ now()->year }} Astha Engineering. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
