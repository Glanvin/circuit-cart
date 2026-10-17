<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($subject) ? $subject : 'Reset Password'; ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f7fa;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;border:1px solid #e3e8ef;">
                    <tr><td style="background:#0dcaf0;padding:20px;text-align:center;color:#055160;font-weight:700;">Circuit Cart</td></tr>
                    <tr><td style="padding:32px 40px 8px;text-align:center;color:#1a1f36;font-size:20px;">Reset your password</td></tr>
                    <tr><td style="padding:0 40px 16px;color:#374151;font-size:14px;line-height:1.6;">
                        <p style="margin:0 0 12px;">Click the button below to reset your password. This link expires in <?php echo (int)($expires_minutes ?? 60); ?> minutes.</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:16px auto;">
                            <tr><td style="background:#0dcaf0;border-radius:8px;"><a href="<?php echo htmlspecialchars($reset_url ?? '#', ENT_QUOTES, 'UTF-8'); ?>" style="display:inline-block;padding:12px 32px;color:#fff;text-decoration:none;font-weight:600;">Reset Password</a></td></tr>
                        </table>
                        <p style="margin:12px 0 0;color:#6b7280;font-size:13px;">If you didn't request this, you can safely ignore this email.</p>
                    </td></tr>
                    <tr><td style="padding:16px 40px 24px;text-align:center;color:#9ca3af;font-size:12px;">&copy; <?php echo date('Y'); ?> Circuit Cart</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
