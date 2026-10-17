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

$config['booking_form'] = array(
	array(
		'field' => 'device',
		'label' => 'Device Name & Model',
		'rules' => 'required|trim|max_length[128]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'max_length' => '%s is too long, maximum length is 128 characters',
		)
	),
	array(
		'field' => 'issue',
		'label' => 'Issue Description',
		'rules' => 'required|trim|min_length[10]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be at least 10 characters long',
		)
	),
	array(
		'field' => 'preferred_date',
		'label' => 'Visit Date',
		'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'regex_match' => '%s must be in YYYY-MM-DD format',
		)
	),
	array(
		'field' => 'preferred_time',
		'label' => 'Visit Time',
		'rules' => 'required|regex_match[/^\d{2}:\d{2}$/]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'regex_match' => '%s must be in HH:MM format',
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

$config['bug_report'] = array(
	array(
		'field' => 'email',
		'label' => 'Email',
		'rules' => 'trim|valid_email|max_length[255]',
		'errors' => array(
			'valid_email' => '%s must be a valid email address',
			'max_length' => '%s is too long, maximum length is 255 characters',
		)
	),
	array(
		'field' => 'title',
		'label' => 'Title',
		'rules' => 'required|trim|max_length[150]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'max_length' => '%s is too long, maximum length is 150 characters',
		)
	),
	array(
		'field' => 'description',
		'label' => 'Description',
		'rules' => 'required|trim|min_length[10]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be at least 10 characters long',
		)
	),
);

$config['employee_signin'] = array(
	array(
		'field' => 'employee_id',
		'label' => 'Employee ID',
		'rules' => 'required|exact_length[6]|numeric',
		'errors' => array(
			'required' => '%s cannot be blank',
			'exact_length' => '%s must be a 6 digit number',
			'numeric' => '%s must contain numbers only',
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
);

$config['employee_create'] = array(
	array(
		'field' => 'email',
		'label' => 'Email',
		'rules' => 'required|valid_email|is_unique[tbl_employees.email]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'is_unique' => '%s is already in use'
		)
	),
	array(
		'field' => 'firstname',
		'label' => 'First Name',
		'rules' => 'required|regex_match[/^[a-zA-Z -]+$/]',
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
	),
	array(
		'field' => 'contactnumber',
		'label' => 'Phone Number',
		'rules' => 'required|exact_length[10]|numeric',
	),
	array(
		'field' => 'password',
		'label' => 'Password',
		'rules' => 'required|min_length[6]',
	),
	array(
		'field' => 'role',
		'label' => 'Role',
		'rules' => 'required|in_list[cashier,manager,technician,admin,developer,logistics]',
	),
);

$config['employee_profile'] = array(
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

$config['employee_password'] = array(
	array(
		'field' => 'current_password',
		'label' => 'Current Password',
		'rules' => 'required|callback_check_current_employee_password',
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

$config['checkout_guest'] = array(
	array(
		'field' => 'guest_email',
		'label' => 'Email',
		'rules' => 'required|valid_email|max_length[255]',
		'errors' => array(
			'required' => '%s is required for delivery confirmation',
		)
	),
	array(
		'field' => 'delivery_address',
		'label' => 'Delivery Address',
		'rules' => 'required|max_length[255]',
		'errors' => array(
			'required' => '%s is required for delivery',
		)
	),
);

// Signed-in shoppers may leave the address blank to fall back to their saved
// account address, so it is optional here but still length-checked.
$config['checkout_user'] = array(
	array(
		'field' => 'delivery_address',
		'label' => 'Delivery Address',
		'rules' => 'max_length[255]',
	),
);

$config['sku_create'] = array(
	array(
		'field' => 'sku',
		'label' => 'SKU',
		'rules' => 'required|max_length[64]|regex_match[/^[A-Za-z0-9\-_]+$/]|is_unique[tbl_sku.sku]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'is_unique' => '%s already exists'
		)
	),
	array(
		'field' => 'part_name',
		'label' => 'Part Name',
		'rules' => 'required|max_length[255]',
	),
	array(
		'field' => 'category',
		'label' => 'Category',
		'rules' => 'required|max_length[50]',
	),
	array(
		'field' => 'device_type',
		'label' => 'Device Type',
		'rules' => 'required|max_length[50]',
	),
	array(
		'field' => 'cost_price',
		'label' => 'Cost Price',
		'rules' => 'required|numeric',
	),
	array(
		'field' => 'selling_price',
		'label' => 'Selling Price',
		'rules' => 'required|numeric',
	),
	array(
		'field' => 'stock_quantity',
		'label' => 'Stock Quantity',
		'rules' => 'required|integer|greater_than_equal_to[0]',
	),
);

$config['sku_edit'] = array(
	array(
		'field' => 'part_name',
		'label' => 'Part Name',
		'rules' => 'required|max_length[255]',
	),
	array(
		'field' => 'category',
		'label' => 'Category',
		'rules' => 'required|max_length[50]',
	),
	array(
		'field' => 'device_type',
		'label' => 'Device Type',
		'rules' => 'required|max_length[50]',
	),
	array(
		'field' => 'cost_price',
		'label' => 'Cost Price',
		'rules' => 'required|numeric',
	),
	array(
		'field' => 'selling_price',
		'label' => 'Selling Price',
		'rules' => 'required|numeric',
	),
);

$config['add_stock'] = array(
	array(
		'field' => 'stock_quantity',
		'label' => 'Stock Quantity',
		'rules' => 'required|integer|greater_than_equal_to[0]',
	),
);

$config['promo_create'] = array(
	array(
		'field' => 'code',
		'label' => 'Code',
		'rules' => 'required|max_length[32]|regex_match[/^[A-Za-z0-9\-_]+$/]|is_unique[tbl_promo_codes.code]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'regex_match' => '%s may only contain letters, numbers, hyphens and underscores',
			'is_unique' => '%s already exists',
		)
	),
	array(
		'field' => 'discount_type',
		'label' => 'Discount Type',
		'rules' => 'required|in_list[percent,fixed]',
	),
	array(
		'field' => 'discount_value',
		'label' => 'Discount Value',
		'rules' => 'required|numeric|greater_than[0]',
	),
	array(
		'field' => 'min_subtotal',
		'label' => 'Minimum Subtotal',
		'rules' => 'numeric|greater_than_equal_to[0]',
	),
	array(
		'field' => 'max_total_uses',
		'label' => 'Maximum Total Uses',
		'rules' => 'integer|greater_than_equal_to[0]',
	),
	array(
		'field' => 'per_user_limit',
		'label' => 'Per-User Limit',
		'rules' => 'integer|greater_than_equal_to[0]',
	),
);

$config['promo_edit'] = array(
	array(
		'field' => 'discount_type',
		'label' => 'Discount Type',
		'rules' => 'required|in_list[percent,fixed]',
	),
	array(
		'field' => 'discount_value',
		'label' => 'Discount Value',
		'rules' => 'required|numeric|greater_than[0]',
	),
	array(
		'field' => 'min_subtotal',
		'label' => 'Minimum Subtotal',
		'rules' => 'numeric|greater_than_equal_to[0]',
	),
	array(
		'field' => 'max_total_uses',
		'label' => 'Maximum Total Uses',
		'rules' => 'integer|greater_than_equal_to[0]',
	),
	array(
		'field' => 'per_user_limit',
		'label' => 'Per-User Limit',
		'rules' => 'integer|greater_than_equal_to[0]',
	),
);

$config['service_create'] = array(
	array(
		'field' => 'name',
		'label' => 'Service Name',
		'rules' => 'required|trim|max_length[128]|is_unique[tbl_repair_services.name]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'max_length' => '%s is too long, maximum length is 128 characters',
			'is_unique' => '%s already exists',
		)
	),
	array(
		'field' => 'description',
		'label' => 'Description',
		'rules' => 'trim|max_length[2000]',
	),
	array(
		'field' => 'base_price',
		'label' => 'Base Price',
		'rules' => 'trim|numeric|greater_than_equal_to[0]',
		'errors' => array(
			'numeric' => '%s must be a number',
			'greater_than_equal_to' => '%s cannot be negative',
		)
	),
	array(
		'field' => 'sort_order',
		'label' => 'Sort Order',
		'rules' => 'trim|integer|greater_than_equal_to[0]',
		'errors' => array(
			'integer' => '%s must be a whole number',
			'greater_than_equal_to' => '%s cannot be negative',
		)
	),
);

$config['service_edit'] = array(
	array(
		'field' => 'name',
		'label' => 'Service Name',
		'rules' => 'required|trim|max_length[128]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'max_length' => '%s is too long, maximum length is 128 characters',
		)
	),
	array(
		'field' => 'description',
		'label' => 'Description',
		'rules' => 'trim|max_length[2000]',
	),
	array(
		'field' => 'base_price',
		'label' => 'Base Price',
		'rules' => 'trim|numeric|greater_than_equal_to[0]',
		'errors' => array(
			'numeric' => '%s must be a number',
			'greater_than_equal_to' => '%s cannot be negative',
		)
	),
	array(
		'field' => 'sort_order',
		'label' => 'Sort Order',
		'rules' => 'trim|integer|greater_than_equal_to[0]',
		'errors' => array(
			'integer' => '%s must be a whole number',
			'greater_than_equal_to' => '%s cannot be negative',
		)
	),
);

$config['review_form'] = array(
	array(
		'field' => 'rating',
		'label' => 'Rating',
		'rules' => 'required|in_list[1,2,3,4,5]',
		'errors' => array(
			'required' => 'Please choose a rating from 1 to 5 stars',
			'in_list' => 'Please choose a rating from 1 to 5 stars',
		)
	),
	array(
		'field' => 'title',
		'label' => 'Review Title',
		'rules' => 'required|trim|max_length[128]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'max_length' => '%s is too long, maximum length is 128 characters',
		)
	),
	array(
		'field' => 'body',
		'label' => 'Review',
		'rules' => 'required|trim|min_length[10]|max_length[2000]',
		'errors' => array(
			'required' => '%s cannot be blank',
			'min_length' => '%s must be at least 10 characters long',
			'max_length' => '%s is too long, maximum length is 2000 characters',
		)
	),
);
