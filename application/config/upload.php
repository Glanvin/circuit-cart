<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Per-subsystem upload settings, namespaced under $config['upload'][<subsystem>]
| so each consumer (booking, bug reports, user import) has an independent
| upload path and limits without key collisions in the shared config.
*/
$config['upload'] = array(
	'booking' => array(
		'upload_path'   => FCPATH . 'storage/booking/',
		'allowed_types' => 'jpg|jpeg|png|webp|gif',
		'max_size'      => 5120, // 5 MB per image
		'max_width'     => 0,
		'max_height'    => 0,
		'is_image'      => TRUE,
		'encrypt_name'  => TRUE,
		'overwrite'     => FALSE,
	),
	'bugs' => array(
		'upload_path'   => FCPATH . 'storage/bugs/',
		'allowed_types' => 'jpg|jpeg|png|gif',
		'max_size'      => 3072, // 3 MB per image
		'max_width'     => 0,
		'max_height'    => 0,
		'is_image'      => TRUE,
		'encrypt_name'  => TRUE,
		'overwrite'     => FALSE,
	),
	//Spreadsheets for the admin user import. xlsm is deliberately excluded
	//because macro-enabled workbooks are never a valid source of user rows.
	'user_import' => array(
		'upload_path'   => FCPATH . 'storage/tmp/',
		'allowed_types' => 'xlsx|xls|csv',
		'max_size'      => 5120, // 5 MB
		'max_width'     => 0,
		'max_height'    => 0,
		'is_image'      => FALSE,
		'encrypt_name'  => TRUE,
		'overwrite'     => FALSE,
	),
);