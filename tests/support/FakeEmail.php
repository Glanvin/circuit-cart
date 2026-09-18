<?php

/**
 * Records email-library interactions so controllers/helpers can be tested
 * without a real SMTP connection.
 */
class FakeEmail {

	public $from;
	public $to;
	public $subject;
	public $message;
	public $sendResult = TRUE;
	public $sent       = FALSE;
	public $sendCalls  = 0;

	public function from($from, $name = '') {
		$this->from = [$from, $name];
		return $this;
	}

	public function to($to) {
		$this->to = $to;
		return $this;
	}

	public function subject($subject) {
		$this->subject = $subject;
		return $this;
	}

	public function message($message) {
		$this->message = $message;
		return $this;
	}

	public function send() {
		$this->sendCalls++;
		if ($this->sendResult) {
			$this->sent = TRUE;
		}
		return $this->sendResult;
	}

	public function print_debugger() {
		return 'SMTP debug output';
	}
}
