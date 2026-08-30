<?php

use PHPUnit\Framework\TestCase;

class ToastifyTest extends TestCase {

	private $ci;
	private $session;
	private $toastify;

	protected function setUp(): void {
		$this->session  = new FakeSession();
		$this->ci       = new CI_Controller();
		$this->ci->session = $this->session;
		$this->toastify = new Toastify();
	}

	public function testSuccessStoresFlashdataUnderToastSuccessKey() {
		$this->toastify->success('Saved!');

		$this->assertSame('Saved!', $this->session->flashdata('toast_success'));
	}

	public function testErrorStoresFlashdataUnderToastErrorKey() {
		$this->toastify->error('Boom');

		$this->assertSame('Boom', $this->session->flashdata('toast_error'));
	}

	public function testInfoStoresFlashdataUnderToastInfoKey() {
		$this->toastify->info('Heads up');

		$this->assertSame('Heads up', $this->session->flashdata('toast_info'));
	}

	public function testRenderReturnsEmptyStringWhenNoMessages() {
		$this->assertSame('', $this->toastify->render());
	}

	public function testRenderWrapsMessagesInToastContainer() {
		$this->toastify->success('ok');

		$html = $this->toastify->render();

		$this->assertStringContainsString('toast-container', $html);
		$this->assertStringContainsString('class="toast ', $html);
	}

	public function testRenderUsesCorrectClassAndTitlePerType() {
		$expectations = [
			'toast_success' => ['text-bg-success', 'Success'],
			'toast_error'   => ['text-bg-danger', 'Error'],
			'toast_info'    => ['text-bg-info', 'Notice'],
		];

		foreach ($expectations as $key => [$class, $title]) {
			$this->session = new FakeSession();
			$this->ci->session = $this->session;
			$this->toastify = new Toastify();

			$this->toastify->{$key === 'toast_success' ? 'success' : ($key === 'toast_error' ? 'error' : 'info')}('msg');
			$html = $this->toastify->render();

			$this->assertStringContainsString($class, $html);
			$this->assertStringContainsString($title, $html);
		}
	}

	public function testRenderEscapesHtmlInMessage() {
		$evil = '<script>alert(1)</script>';
		$this->toastify->error($evil);

		$html = $this->toastify->render();

		$this->assertStringNotContainsString('<script>', $html);
		$this->assertStringContainsString(htmlspecialchars($evil), $html);
	}

	public function testRenderOutputsMultipleToastsForMultipleTypes() {
		$this->toastify->success('a');
		$this->toastify->info('b');
		$this->toastify->error('c');

		$html = $this->toastify->render();

		$this->assertSame(3, substr_count($html, 'role="alert"'));
	}
}
