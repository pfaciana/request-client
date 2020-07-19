<?php


namespace RequestClient\Request;


class Headers
{
	protected $headers = [];

	protected $lineDelimiter = "\r\n";

	public function __construct ($headers = '', $lineDelimiter = "\r\n")
	{
		$this->setHeaders($headers);
		!empty($lineDelimiter) && ($this->lineDelimiter = $lineDelimiter);
	}

	public function setHeaders ($headers)
	{
		if (empty($headers)) {
			return FALSE;
		}

		if (is_string($headers)) {
			$headers = explode($this->lineDelimiter, trim($headers));
		}

		if (!is_array($headers)) {
			return FALSE;
		}

		foreach ($headers as $header) {
			$this->setHeaderFromStream(NULL, $header);
		}

		return $this->headers;
	}

	public function setHeaderWithoutKey ($header) { }

	public function setHeaderWithKey ($header)
	{
		list($key, $value) = explode(':', $header, 2);
		$this->headers[strtolower(trim($key))][] = trim($value);
	}

	public function setHeaderFromStream ($resource, $data)
	{
		if ($header = trim($data)) {
			strpos($header, ':') ? $this->setHeaderWithKey($header) : $this->setHeaderWithoutKey($header);
		}

		return strlen($data);
	}

	protected function formatHeaders ($join = ';')
	{
		if (!is_string($join)) {
			return $this->headers;
		}

		$formattedHeaders = [];
		foreach ($this->headers as $key => $values) {
			$formattedHeaders[$key] = implode($join, $values);
		}

		return $formattedHeaders;
	}

	public function getRawHeaders ()
	{
		$rawHeaders = '';

		foreach ($this->headers as $key => $values) {
			foreach ($values as $value) {
				$rawHeaders .= "{$key}: {$value}{$this->lineDelimiter}";
			}
		}

		return trim($rawHeaders);
	}

	public function getHeaders ($join = '; ')
	{
		return $this->formatHeaders($join);
	}

	public function getHeader ($key, $join = '; ')
	{
		if (!array_key_exists($key, $this->headers)) {
			return NULL;
		}

		if (is_string($join)) {
			return implode($join, $this->headers[$key]);
		}

		return $this->headers[$key];
	}
}