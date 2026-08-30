<?php
defined('BASEPATH') OR exit('No direct script access allowed');

//Checks whether the current user has a valid session.
function check_session()
{
    $CI =& get_instance();

    if (empty($CI->session->userdata('username'))) {
        throw new Exception("The Stored Session is invalid. Please login again", 4);
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
