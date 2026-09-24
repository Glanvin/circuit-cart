<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Checks whether the current user has a valid session.
function check_session()
{
    $CI =& get_instance();

    if (empty($CI->session->userdata('username'))) {
        throw new AppException('S01');
    }
}

//Returns a Bootstrap validation class for a form field.
function is_invalid($field, $error_class = 'is-invalid')
{
    $CI =& get_instance();

    if (!isset($CI->form_validation)) {
        return '';
    }

    return form_error($field) ? $error_class : '';
}

//Masks the local part of an email address, keeping the domain visible.
function mask_email($email)
{
    $email = (string) $email;

    if ($email === '' || strpos($email, '@') === false) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);

    if (strlen($local) <= 1) {
        return $local . '@' . $domain;
    }

    return $local[0] . str_repeat('*', strlen($local) - 1) . '@' . $domain;
}

/*
 * Renders an email template and dispatches it through the CI Email library,
 * returning whether the delivery pipeline accepted the message.
 */
function deliver_email(
	string $view,
	array $data,
	string $to,
	string $subject,
	string $from = '',
	string $fromName = 'Circuit Cart',
	string $replyTo = '',
	array $cc = [],
	array $bcc = []
): bool
{
    $CI =& get_instance();

    if ($to === '') {
        return false;
    }

    if (!isset($CI->email)) {
        $CI->load->library('email');
    }

    $message = $CI->load->view($view, $data, true);

	$from = $from !== '' ? $from : app_env('SMTP_USER', '');

    $CI->email->from($from, $fromName);
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);

    if ($replyTo !== '') {
        $CI->email->reply_to($replyTo);
    }
    if ($cc !== []) {
        $CI->email->cc($cc);
    }
    if ($bcc !== []) {
        $CI->email->bcc($bcc);
    }

    return $CI->email->send();
}

//Formats a booking row into human-readable label/value pairs for email bodies.
function booking_details(array $booking): array
{
    return [
        'Device' => $booking['device'] ?? '',
        'Issue Description' => $booking['issue'] ?? '',
        'Preferred Date' => date('F j, Y', strtotime((string) ($booking['preferred_date'] ?? ''))),
        'Preferred Time' => date('h:i A', strtotime((string) ($booking['preferred_time'] ?? ''))),
        'Status' => ucfirst((string) ($booking['status'] ?? '')),
    ];
}

//Renders the booking status email template and dispatches it to the customer.
function send_booking_email(string $to, string $subject, string $greeting, string $message, array $details): bool
{
    return deliver_email(
        'emails/booking_email',
        [
            'subject' => $subject,
            'greeting' => $greeting,
            'message' => $message,
            'details' => $details,
        ],
        $to,
        $subject
    );
}

//Sends an appointment status notification to the booking's owner, swallowing
//email failures so the booking workflow never breaks.
function send_booking_notification(string $username, string $subject, string $message, array $details): void
{
    $CI =& get_instance();

    try {
        $user = $CI->UserModel->findUserByUsername($username);

        if (!empty($user['email'])) {
            send_booking_email(
                $user['email'],
                $subject,
                'Hello ' . ucfirst((string) $username) . ',',
                $message,
                $details
            );
        }
    } catch (Exception $e) {
        // Ignore: the booking status change itself should still succeed.
    }
}

/*
 * Generates a 6-digit verification code, emails the plaintext version, then
 * stores only its hash plus expiry in the session. Returns false on failure.
 */
function store_otp(string $email): bool
{
    $CI =& get_instance();

    $code = (string) random_int(100000, 999999);

    $sent = deliver_email(
        'emails/opt_email',
        [
            'subject' => 'Verify Your Email - Circuit Cart',
            'greeting' => 'Hello!',
            'message' => "We're thrilled to have you as part of the Circuit Cart community. To finish securing your account, please verify your email address using the code we sent you.",
            'otp_code' => $code,
        ],
        $email,
        'Verify Your Email - Circuit Cart'
    );

    if (!$sent) {
        return false;
    }

    $CI->session->set_userdata('otp', [
        'hash' => password_hash($code, PASSWORD_DEFAULT),
        'expires_at' => time() + 120,
    ]);

    return true;
}

//Compares a submitted code against the stored hash, clearing the OTP once it
//matches or has expired.
function verify_otp(string $code): bool
{
    $CI =& get_instance();

    $otp = $CI->session->userdata('otp');

    if (!is_array($otp) || empty($otp['hash']) || empty($otp['expires_at'])) {
        return false;
    }

    if ($otp['expires_at'] < time()) {
        $CI->session->unset_userdata('otp');
        return false;
    }

    $valid = password_verify($code, $otp['hash']);

    if ($valid) {
        $CI->session->unset_userdata('otp');
    }

    return $valid;
}

//Returns the cart lines held in the session, or an empty array when the cart
//has no items.
function cart_items(): array
{
    $CI =& get_instance();

    $cart = $CI->session->userdata('cart_items');

    return is_array($cart) ? $cart : [];
}
