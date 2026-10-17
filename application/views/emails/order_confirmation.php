<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo isset($subject) ? $subject : 'Order Confirmation'; ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f7fa; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e3e8ef;">
                    <tr>
                        <td style="background-color:#0dcaf0; padding:24px 40px; text-align:center;">
                            <h1 style="margin:0; color:#055160; font-size:24px; font-weight:700;">Circuit Cart</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px 16px; text-align:center;">
                            <h2 style="margin:0 0 8px; color:#1a1f36; font-size:20px;"><?php echo isset($greeting) ? $greeting : 'Thank you for your order!'; ?></h2>
                            <p style="margin:0; color:#6b7280; font-size:15px;"><?php echo isset($message) ? $message : 'Your order has been placed.'; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 40px; color:#374151; font-size:14px; line-height:1.6;">
                            <?php if (!empty($sale)): ?>
                                <p style="margin:0 0 12px;">Receipt: <strong><?php echo htmlspecialchars($sale['receipt_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                Total: <strong>₱<?php echo number_format((float)($sale['total'] ?? 0), 2); ?></strong></p>
                            <?php endif; ?>
                            <p style="margin:16px 0;">You can track your order from <a href="<?php echo site_url('orders'); ?>" style="color:#0dcaf0;">My Orders</a>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 40px 32px; text-align:center;">
                            <p style="margin:0; color:#9ca3af; font-size:12px;">&copy; <?php echo date('Y'); ?> Circuit Cart</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
