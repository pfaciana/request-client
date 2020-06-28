<?php


namespace RequestClient\Request;


trait CurlErrorsTrait
{
	protected $errors = [];

	protected function addErrors ($message)
	{
		$messages = (array) $message;

		foreach ($messages as $message) {
			$this->errors[] = $message;
		}
	}

	public function getErrors ()
	{
		return $this->errors;
	}
}