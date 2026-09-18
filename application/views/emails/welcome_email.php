<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo isset($subject) ? $subject : 'Welcome to Circuit Cart'; ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7fa; font-family:Helvetica, Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f7fa; padding:24px 0;">
        <tr>
            <td align="center">
                <!-- Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e3e8ef;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0dcaf0; padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; color:#055160; font-size:28px; font-weight:700; letter-spacing:0.5px;">Circuit Cart</h1>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding:40px 40px 20px; text-align:center;">
                            <h2 style="margin:0 0 8px; color:#1a1f36; font-size:22px;"><?php echo isset($greeting) ? $greeting : 'Hello!'; ?></h2>
                            <p style="margin:0; color:#6b7280; font-size:15px;"><?php echo isset($message) ? $message : 'Welcome aboard!'; ?></p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:20px 40px; color:#374151; font-size:14px; line-height:1.7;">
                            <p style="margin:0 0 12px;">We're thrilled to have you as part of the Circuit Cart community. Shop smarter, track orders in real time, and get exclusive deals straight to your inbox.</p>
                            <p style="margin:0 0 24px;">To get started, explore our latest collections and add your favorites to the cart.</p>

                            <!-- Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td style="background-color:#0dcaf0; border-radius:8px;">
                                        <a href="#" target="_blank" style="display:inline-block; padding:14px 36px; color:#ffffff; text-decoration:none; font-weight:600; font-size:15px; border-radius:8px;">Start Browsing</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:24px 40px 0;">
                            <hr style="border:0; border-top:1px solid #eef1f6; margin:0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px 32px; text-align:center;">
                            <p style="margin:0 0 10px; color:#9ca3af; font-size:12px; line-height:1.6;">
                                Circuit Cart &middot; 019 Corrales Ext, Cagayan de Oro City, Philippines<br>
                                <a href="mailto:support@circuitcart.com" style="color:#4e7cff; text-decoration:none;">support@circuitcart.com</a> &middot; +63 900 321 3266
                            </p>
                            <p style="margin:0; color:#b8bfcc; font-size:11px;">You received this email because you signed up for Circuit Cart.<br>
                            <a href="#" style="color:#b8bfcc; text-decoration:underline;">Unsubscribe</a></p>
                        </td>
                    </tr>

                </table>
                <p style="margin:16px 0 0; color:#b8bfcc; font-size:11px;">&copy; <?php echo date('Y'); ?> Circuit Cart. All rights reserved.</p>
            </td>
        </tr>
    </table>

</body>
</html>
