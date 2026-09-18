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

    if ($email === '' || strpos($email, '@') === FALSE) {
        return $email;
    }

    [$local, $domain] = explode('@', $email, 2);

    if (strlen($local) <= 1) {
        return $local . '@' . $domain;
    }

    return $local[0] . str_repeat('*', strlen($local) - 1) . '@' . $domain;
}

//Renders an email template and dispatches it through the CI Email library.
//Returns TRUE when the delivery pipeline accepted the message, FALSE when SMTP failed.
function deliver_email(string $view, array $data, string $to, string $subject, string $from = ''): bool
{
    $CI =& get_instance();

    $message = $CI->load->view($view, $data, TRUE);

	$from = $from !== '' ? $from : app_env('SMTP_USER', '');

    $CI->email->from($from, 'Circuit Cart');
    $CI->email->to($to);
    $CI->email->subject($subject);
    $CI->email->message($message);

    return $CI->email->send();
}
