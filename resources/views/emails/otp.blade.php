<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your ShopEase verification code</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="background:#16a34a;padding:24px 32px;">
                <span style="color:#ffffff;font-size:20px;font-weight:bold;">ShopEase</span>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">Use the code below to sign in to your ShopEase account.</p>
                <div style="margin:24px 0;text-align:center;">
                    <span style="display:inline-block;padding:14px 28px;background:#f0fdf4;border:1px dashed #16a34a;border-radius:8px;font-size:32px;font-weight:bold;letter-spacing:8px;color:#15803d;">{{ $otp }}</span>
                </div>
                <p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#52525b;">This code expires in {{ $expiryMinutes }} minute{{ $expiryMinutes === 1 ? '' : 's' }}.</p>
                <p style="margin:0;font-size:14px;line-height:1.5;color:#52525b;">If you didn't request this code, you can safely ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
