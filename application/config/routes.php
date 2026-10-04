<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'HomeController';
$route['home'] = 'HomeController';
$route['browse'] = 'BrowseController';
$route['cart'] = 'CartController';
$route['cart/add/(:num)'] = 'CartController/add/$1';
$route['cart/buy/(:num)'] = 'CartController/buyNow/$1';
$route['cart/update/(:num)'] = 'CartController/update/$1';
$route['cart/remove/(:num)'] = 'CartController/remove/$1';
$route['checkout'] = 'CheckoutController';
$route['checkout/place'] = 'CheckoutController/placeOrder';
$route['checkout/success'] = 'CheckoutController/success';

$route['booking'] = 'BookingController';
$route['booking/appointments'] = 'BookingController/appointments';

$route['bugs'] = 'BugController';

$route['storage/booking'] = 'EmployeeBookingController';
$route['storage/booking/approve/(:num)'] = 'EmployeeBookingController/approve/$1';
$route['storage/booking/reschedule/(:num)'] = 'EmployeeBookingController/reschedule/$1';
$route['storage/booking/decline/(:num)'] = 'EmployeeBookingController/decline/$1';
$route['storage/booking/finish/(:num)'] = 'EmployeeBookingController/finish/$1';

$route['storage/bugs'] = 'EmployeeBugController';
$route['storage/bugs/status/(:num)/(:any)'] = 'EmployeeBugController/status/$1/$2';

$route['employee/dashboard'] = 'DashboardController';
$route['employee/analytics'] = 'AnalyticsController';
$route['employee/analytics/data'] = 'AnalyticsController/data';
$route['employee/orders'] = 'AnalyticsController/orders';
$route['employee/orders/status/(:num)/(:any)'] = 'AnalyticsController/orderStatus/$1/$2';
$route['employee/attendance'] = 'AttendanceController';
$route['employee/attendance/view/(:num)'] = 'AttendanceController/index/$1';
$route['employee/attendance/in'] = 'AttendanceController/timeIn';
$route['employee/attendance/out'] = 'AttendanceController/timeOut';
$route['employee/manage/users'] = 'ManagementController/users';
$route['employee/manage/users/import'] = 'ManagementController/importUsers';
$route['employee/manage/users/sample'] = 'ManagementController/userTemplate';
$route['employee/manage/employees'] = 'ManagementController/employees';
$route['employee/manage/employees/new'] = 'ManagementController/newEmployee';

$route['employee/settings'] = 'EmployeeSettingsController';
$route['employee/settings/updateProfile'] = 'EmployeeSettingsController/updateProfile';
$route['employee/settings/updatePassword'] = 'EmployeeSettingsController/updatePassword';
$route['employee/settings/verify'] = 'EmployeeSettingsController/verify';

$route['employee'] = 'EmployeeAuthController';
$route['employee/signin'] = 'EmployeeAuthController/signin';
$route['employee/signout'] = 'EmployeeAuthController/signout';

$route['pos'] = 'PosController';
$route['pos/complete'] = 'PosController/complete';

$route['inventory/stocks'] = 'StockController';
$route['inventory/stocks/new'] = 'StockController/newSKU';
$route['inventory/stocks/edit/(:num)'] = 'StockController/edit/$1';
$route['inventory/stocks/add/(:num)'] = 'StockController/addStock/$1';
$route['inventory/stocks/remove/(:num)'] = 'StockController/remove/$1';

$route['auth'] = 'AuthController';
$route['auth/signin'] = 'AuthController/signin';
$route['auth/signup'] = 'AuthController/signup';
$route['auth/signout'] = 'AuthController/signout';
$route['auth/verify'] = 'AuthController/verify';

$route['settings'] = 'SettingsController';
$route['settings/theme'] = 'SettingsController/theme';
$route['settings/updateProfile'] = 'SettingsController/updateProfile';
$route['settings/updatePassword'] = 'SettingsController/updatePassword';

$route['404_override'] = 'ErrorController';
$route['translate_uri_dashes'] = FALSE;
