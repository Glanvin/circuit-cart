<?php

$config['user_signup'] = array(
	array(
		'field' => 'username',
		'label' => 'Username',
		'rules' => 'required|min_length[3]|max_length[20]|regex_match[/^[a-zA-Z0-9_@.\-]+$/]|is_unique[tbl_users.username]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be 3 characters long',
			'max_length' => '%s too long max length is 20 characters',
			'is_unique' => '%s has already been taken'
		)
	),
	array(
		'field' => 'email',
		'label' => 'Email',
		'rules' => 'required|valid_email|is_unique[tbl_users.email]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'is_unique' => '%s is already in use'
		)
	),
	array(
		'field' => 'firstname',
		'label' => 'First Name',
		'rules' => 'required|regex_match[/^[a-zA-Z -]+$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'middlename',
		'label' => 'Middle Name',
		'rules' => 'regex_match[/^[a-zA-Z -]+$/]',
	),
	array(
		'field' => 'lastname',
		'label' => 'Last Name',
		'rules' => 'required|regex_match[/^[a-zA-Z -]+$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'contactnumber',
		'label' => 'Phone Number',
		'rules' => 'required|exact_length[10]|numeric',
		'errors' => array(
			'exact_length' => '%s is must be 10 digits long'
		)
	),
	array(
		'field' => 'password',
		'label' => 'Password',
		'rules' => 'required|min_length[6]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'passwordconf',
		'label' => 'Password Confirmation',
		'rules' => 'required|min_length[6]|matches[password]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'matches' => '%s does not match to Password',
		)
	),
);

$config['user_signin'] = array(
	array(
		'field' => 'username',
		'label' => 'Username',
		'rules' => 'required|min_length[3]|max_length[20]|regex_match[/^[a-zA-Z0-9_@.\-]+$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be 3 characters long',
			'max_length' => '%s too long max length is 20 characters',
		)
	),
	array(
		'field' => 'password',
		'label' => 'Password',
		'rules' => 'required|min_length[6]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	)
);

$config['user_profile'] = array(
	array(
		'field' => 'firstname',
		'label' => 'First Name',
		'rules' => 'required|regex_match[/^[a-zA-Z -]+$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'middlename',
		'label' => 'Middle Name',
		'rules' => 'regex_match[/^[a-zA-Z -]+$/]',
	),
	array(
		'field' => 'lastname',
		'label' => 'Last Name',
		'rules' => 'required|regex_match[/^[a-zA-Z -]+$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'email',
		'label' => 'Email',
		'rules' => 'required|valid_email',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'contactnumber',
		'label' => 'Phone Number',
		'rules' => 'required|exact_length[10]|numeric',
		'errors' => array(
			'exact_length' => '%s is must be 10 digits long'
		)
	),
);

$config['user_address'] = array(
	array(
		'field' => 'address',
		'label' => 'Address',
		'rules' => 'trim|max_length[100]',
	),
);

$config['user_password'] = array(
	array(
		'field' => 'current_password',
		'label' => 'Current Password',
		'rules' => 'required|callback_check_current_password',
		'errors' => array(
			'required' => '%s cannot be blank',
		)
	),
	array(
		'field' => 'new_password',
		'label' => 'New Password',
		'rules' => 'required|min_length[6]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be at least 6 characters long',
		)
	),
	array(
		'field' => 'confirm_password',
		'label' => 'Confirm New Password',
		'rules' => 'required|matches[new_password]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'matches' => '%s does not match to New Password',
		)
	),
);