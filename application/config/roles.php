<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Employee Roles
| -------------------------------------------------------------------
| Central definition of the employee role set and the landing page each
| role is redirected to after signing in. Keep this in sync with any
| changes to application/controllers role gates.
*/
$config['employee_roles'] = array(
    'cashier',
    'manager',
    'technician',
    'admin',
    'developer',
    'logistics',
);

$config['role_home'] = array(
    'cashier'    => 'pos',
    'manager'    => 'employee/analytics',
    'technician' => 'storage/booking',
    'developer'  => 'storage/bugs',
    'logistics'  => 'inventory/stocks',
    'admin'      => 'employee/dashboard',
);