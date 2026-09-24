<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo isset($subject) ? htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') : 'Circuit Cart Appointment'; ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:Helvetica, Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f7fa; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e3e8ef;">

                    <tr>
                        <td style="background-color:#0dcaf0; padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; color:#055160; font-size:28px; font-weight:700; letter-spacing:0.5px;">Circuit Cart</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px 40px 20px; text-align:center;">
                            <h2 style="margin:0 0 8px; color:#1a1f36; font-size:22px;"><?php echo isset($greeting) ? htmlspecialchars($greeting, ENT_QUOTES, 'UTF-8') : 'Hello!'; ?></h2>
                            <p style="margin:0; color:#6b7280; font-size:15px;"><?php echo isset($message) ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : ''; ?></p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 40px; color:#374151; font-size:14px; line-height:1.7;">
                            <?php if (!empty($details) && is_array($details)): ?>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #e3e8ef; border-radius:8px;">
                                    <?php foreach ($details as $label => $value): ?>
                                        <tr>
                                            <td style="padding:12px 24px; font-weight:600; color:#1a1f36; border-bottom:1px solid #eef1f6; width:40%;">
                                                <?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td style="padding:12px 24px; color:#374151; border-bottom:1px solid #eef1f6;">
                                                <?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>

                            <p style="margin:24px 0 0;">If you have any questions about your appointment, feel free to contact us through the details below.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 40px 0;">
                            <hr style="border:0; border-top:1px solid #eef1f6; margin:0;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 40px 32px; text-align:center;">
                            <p style="margin:0 0 10px; color:#9ca3af; font-size:12px; line-height:1.6;">
                                Circuit Cart &middot; 019 Corrales Ext, Cagayan de Oro City, Philippines<br>
                                <a href="mailto:support@circuitcart.com" style="color:#4e7cff; text-decoration:none;">support@circuitcart.com</a> &middot; +63 900 321 3266
                            </p>
                        </td>
                    </tr>

                </table>
                <p style="margin:16px 0 0; color:#b8bfcc; font-size:11px;">&copy; <?php echo date('Y'); ?> Circuit Cart. All rights reserved.</p>
            </td>
        </tr>
    </table>

</body>
</html>