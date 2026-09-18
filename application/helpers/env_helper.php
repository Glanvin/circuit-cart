<?php
if ( ! function_exists('app_env'))
{
	function app_env($key, $default = NULL)
	{
		$value = getenv($key);
		if ($value !== false)
		{
			return $value;
		}
		if (isset($_ENV[$key]))
		{
			return $_ENV[$key];
		}
		if (isset($_SERVER[$key]))
		{
			return $_SERVER[$key];
		}
		return $default;
	}
}