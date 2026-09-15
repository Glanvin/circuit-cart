<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['protocol']    = 'smtp';
$config['smtp_host']   = app_env('SMTP_HOST', 'smtp.gmail.com');
$config['smtp_port']   = app_env('SMTP_PORT', '465');
$config['smtp_user']   = app_env('SMTP_USER');
$config['smtp_pass']   = app_env('SMTP_PASS');
$config['smtp_crypto'] = app_env('SMTP_CRYPTO', 'ssl');
$config['mailtype']       = 'html';
$config['charset']        = 'utf-8';
$config['newline']        = "\r\n";
$config['crlf']           = "\r\n";
$config['smtp_keepalive'] = TRUE;
$config['smtp_timeout']   = 30;