<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AppException extends Exception {

	//Standardized error code the letter-prefixed identifier
	protected string $errorCode;

	public function __construct(string $code, array $params = [], ?Throwable $previous = NULL)
	{
		$this->errorCode = $code;
		parent::__construct(self::resolveMessage($code, $params), 0, $previous);
	}

	//Returns the standardized error code example "P01"
	public function getErrorCode(): string
	{
		return $this->errorCode;
	}

	//Returns "Message [code]" for display in toasts
	public function toToast(): string
	{
		return $this->getMessage().' ['.$this->errorCode.']';
	}

	//Resolves the configured message for a code and injects placeholders
	protected static function resolveMessage(string $code, array $params): string
	{
		$ci =& get_instance();

		$messages = NULL;
		if (isset($ci->config) && is_object($ci->config) && method_exists($ci->config, 'item')) {
			$messages = $ci->config->item('error_codes');
		}

		$template = (is_array($messages) && isset($messages[$code]))
			? $messages[$code]
			: 'An unexpected error occurred';

		foreach ($params as $key => $value) {
			$template = str_replace('{'.$key.'}', (string) $value, $template);
		}

		return $template;
	}
}
