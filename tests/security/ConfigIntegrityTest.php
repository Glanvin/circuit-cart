<?php

use PHPUnit\Framework\TestCase;

/**
 * Structural integrity checks for config files the app depends on.
 *
 * Catches latent configuration bugs such as duplicate array keys that PHP
 * silently collapses (e.g. a duplicated min_length error key) and regressions
 * in route-to-controller mapping.
 */
class ConfigIntegrityTest extends TestCase {

	private $config;

	protected function setUp(): void {
		$config = [];
		require APPPATH . 'config/form_rules.php';
		$this->config = $config;
	}

	public function testErrorCodesAreUnique(): void {
		$codes = error_codes();

		// Every code is a distinct, non-empty string key.
		$this->assertNotEmpty($codes);
		$this->assertSame(count($codes), count(array_unique(array_keys($codes))));

		foreach ($codes as $code => $message) {
			$this->assertMatchesRegularExpression('/^[A-Z]\d{2}$/', $code, "Invalid error code '{$code}'");
			$this->assertNotEmpty(trim($message), "Empty message for error code '{$code}'");
			// Any placeholders must use the well-formed {key} form.
			if (preg_match_all('/\{[^}]*\}/', $message, $m)) {
				foreach ($m[0] as $ph) {
					$this->assertMatchesRegularExpression('/^\{[a-zA-Z_]+\}$/', $ph, "Malformed placeholder '{$ph}' in '{$code}'");
				}
			}
		}
	}

	// ----------------------------------------------------------------
	// form_rules.php
	// ----------------------------------------------------------------

	public function testUsernameErrorsHasBothMinAndMaxLengthKeys() {
		// Regression: the errors array once defined 'min_length' twice, which
		// overwrote the real min_length and dropped max_length entirely.
		$errors = $this->config['user_signup'][0]['errors'];

		$this->assertArrayHasKey('min_length', $errors);
		$this->assertArrayHasKey('max_length', $errors);
		$this->assertSame('%s must be 3 characters long', $errors['min_length']);
		$this->assertStringContainsString('20', $errors['max_length']);
	}

	public function testEveryFieldHasAFieldAndRulesKey() {
		foreach (['user_signup', 'user_signin'] as $set) {
			foreach ($this->config[$set] as $rule) {
				$this->assertArrayHasKey('field', $rule, "{$set}: missing field");
				$this->assertArrayHasKey('rules', $rule, "{$set}: missing rules");
				$this->assertNotEmpty($rule['rules'], "{$set}: empty rules for {$rule['field']}");
			}
		}
	}

	public function testGuestCheckoutValidatesDeliveryAddressNotGuestAddress() {
		// Regression: the rule once targeted 'guest_address', which the form
		// never posts, so guest checkout validation silently passed.
		$fields = array_column($this->config['checkout_guest'], 'field');
		$this->assertContains('delivery_address', $fields);
		$this->assertNotContains('guest_address', $fields);

		foreach ($this->config['checkout_guest'] as $rule) {
			if ($rule['field'] === 'delivery_address') {
				$this->assertStringContainsString('required', $rule['rules']);
				$this->assertStringContainsString('max_length', $rule['rules']);
			}
		}
	}

	public function testSignedInCheckoutLengthChecksAddress() {
		$this->assertArrayHasKey('checkout_user', $this->config);
		$fields = array_column($this->config['checkout_user'], 'field');
		$this->assertContains('delivery_address', $fields);

		foreach ($this->config['checkout_user'] as $rule) {
			if ($rule['field'] === 'delivery_address') {
				$this->assertStringContainsString('max_length', $rule['rules']);
			}
		}
	}

	public function testAllSigninRuleSetsExist() {
		$this->assertArrayHasKey('user_signup', $this->config);
		$this->assertArrayHasKey('user_signin', $this->config);
	}

	// ----------------------------------------------------------------
	// routes.php
	// ----------------------------------------------------------------

	public function testRoutesMapToExpectedControllers() {
		$route = [];
		require APPPATH . 'config/routes.php';

		$expectations = [
			'home'                       => 'HomeController',
			'browse'                     => 'BrowseController',
			'wishlist'                   => 'WishlistController',
			'wishlist/toggle/(:num)'     => 'WishlistController/toggle/$1',
			'wishlist/remove/(:num)'     => 'WishlistController/remove/$1',
			'review/create/(:num)'       => 'ReviewController/create/$1',
			'employee/reviews'           => 'ReviewAdminController',
			'employee/reviews/status/(:num)/(:any)' => 'ReviewAdminController/status/$1/$2',
			'employee/reviews/delete/(:num)' => 'ReviewAdminController/delete/$1',
			'employee/dashboard'         => 'DashboardController',
			'employee/manage/users'      => 'ManagementController/users',
			'inventory/stocks'           => 'StockController',
			'auth/signin'                => 'AuthController/signin',
			'auth/signup'                => 'AuthController/signup',
			'auth/signout'               => 'AuthController/signout',
			'orders/cancel/(:num)'       => 'OrdersController/cancel/$1',
			'pos/refund'                 => 'PosController/refund',
			'pos/lookup'                 => 'PosController/lookup',
			'employee/settings/updateRefundPin' => 'EmployeeSettingsController/updateRefundPin',
			'repair/jobs/assign/(:num)'  => 'RepairJobController/assign/$1',
			'repair/jobs/payment/(:num)' => 'RepairJobController/recordPayment/$1',
			'booking/cancel/(:num)'      => 'BookingController/cancel/$1',
			'booking/reschedule/(:num)'  => 'BookingController/reschedule/$1',
			'employee/services'          => 'RepairServiceAdminController',
			'repair/status/(:any)'       => 'RepairStatusController/index/$1',
			'repair/estimate/(:any)'     => 'RepairStatusController/estimate/$1',
			'repair/estimate/(:any)/approve' => 'RepairStatusController/approve/$1',
			'repair/estimate/(:any)/decline' => 'RepairStatusController/decline/$1',
			'storage/booking/pick/(:num)' => 'EmployeeBookingController/pick/$1',
			'storage/booking/unfinish/(:num)' => 'EmployeeBookingController/unfinish/$1',
		];

		foreach ($expectations as $uri => $target) {
			$this->assertArrayHasKey($uri, $route, "Missing route for {$uri}");
			$this->assertSame($target, $route[$uri], "Wrong target for {$uri}");
		}
	}
}
