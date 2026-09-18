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

//Renders an email template and dispatches it through the CI Email library.
//Returns true when the delivery pipeline accepted the message, false when the
//recipient is missing or SMTP failed.
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

//Generates a 6-digit verification code valid for 2 minutes, emails the
//plaintext code to the given address first, then stores only its hash plus the
//expiry in the session. Returns false when the email could not be delivered.
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

//Compares a submitted code against the stored hashed OTP. Returns true when the
//code matches and has not expired, false otherwise. Clears the stored OTP once
//it has been consumed or is no longer valid.
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

//Returns the current cart lines held in the session. Seeds the cart from the
//first two active DB parts the first time it is requested so the navbar badge
//and cart page can share one source. Returns an empty array when the cart has
//been emptied (line items were removed) or the database is unavailable.
//Each line: part_id, name, category, price, quantity.
function cart_items(): array
{
    $CI =& get_instance();

    $cart = $CI->session->userdata('cart_items');

    if ($cart !== NULL) {
        return is_array($cart) ? $cart : [];
    }

    $cart = [];

    try {
        $CI->load->model('PartsModel');

        $parts = array_slice($CI->PartsModel->getAllParts(), 0, 2);

        foreach ($parts as $i => $part) {
            $cart[] = [
                'part_id' => $part['part_id'],
                'name' => $part['part_name'],
                'category' => $part['category'],
                'price' => $part['selling_price'],
                'quantity' => $i === 0 ? 1 : 2,
            ];
        }
    } catch (Exception $e) {
        $cart = [];
    }

    $CI->session->set_userdata('cart_items', $cart);

    return $cart;
}
