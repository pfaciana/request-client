<?php


namespace RequestClient\Request;


class CurlBrowserOptions
{
	use CurlProxyTrait;

	protected $options = [];
	protected $requestHeaders = [
		"Connection: keep-alive",
		"Keep-Alive: 300",
		"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
		"Accept-Language: en-us,en;q=0.5",
		"Cache-Control: no-cache",
		"Pragma: no-cache",
	];

	public function __construct ($options = [])
	{
		$this->setAll($options);
	}

	public function get ($key, $default = NULL)
	{
		return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
	}

	public function set ($key, $value)
	{
		$this->options[$key] = $value;
		$this->normalizeOptions();
	}

	public function getAll ()
	{
		return $this->options;

	}

	public function setAll ($options = [])
	{
		$this->options = $options;
		$this->normalizeOptions();
	}

	protected function normalizeOptions ()
	{
		$this->setAuth();
		$this->setQuery();
		$this->setBody();
		$this->setProxy();
		$this->setMethod();
		$this->setHeaders();
	}

	protected function setAuth ()
	{
		if (array_key_exists('auth_basic', $this->options)) {
			$this->removeHeader('Authorization');
			$this->requestHeaders[] = 'Authorization: Basic ' . base64_encode($this->options['auth_basic']);
			unset($this->options['auth_basic']);
		}

		if (array_key_exists('auth_bearer', $this->options)) {
			$this->removeHeader('Authorization');
			$this->requestHeaders[] = 'Authorization: Bearer ' . $this->options['auth_bearer'];
			unset($this->options['auth_bearer']);
		}
	}

	protected function setMethod ()
	{
		if (!array_key_exists('method', $this->options) && !array_key_exists('action', $this->options)) {
			return;
		}

		$method = $this->options['method'] ?? $this->options['action'];

		unset($this->options['method']);
		unset($this->options['action']);

		$methods = [
			'get'  => CURLOPT_HTTPGET,
			'post' => CURLOPT_POST,
			'put'  => CURLOPT_PUT,
			'head' => CURLOPT_NOBODY,
		];

		foreach (array_merge(array_values($methods), [CURLOPT_CUSTOMREQUEST]) as $methodKey) {
			unset($this->options['curl'][$methodKey]);
		}

		if (in_array(strtolower($method), array_keys($methods))) {
			$this->options['curl'][$methods[strtolower($method)]] = 1;
		}
		else {
			$this->options['curl'][CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		}
	}

	protected function setQuery ()
	{
		if (!array_key_exists('query', $this->options) || empty($query = $this->options['query'])) {
			return;
		}

		unset($this->options['query']);

		if (!is_string($query)) {
			$query = http_build_query($query);
		}

		$this->options['curl'][CURLOPT_URL] = $this->options['url'] = http_build_url($this->options['url'], ['query' => $query], HTTP_URL_JOIN_QUERY);
	}

	protected function setBody ()
	{
		$keys = ['body', 'form', 'json'];
		$type = NULL;

		foreach ($keys as $key) {
			if (array_key_exists($key, $this->options)) {
				!isset($type) && ($body = $this->options[($type = $key)]);
				unset($this->options[$key]);
			}
		}

		if (!isset($type)) {
			return;
		}

		$this->removeHeader('Content-Type');

		switch ($type) {
			case 'form':
				$this->requestHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
				!is_string($body) && ($body = http_build_query($body, '', '&', PHP_QUERY_RFC1738));
				break;
			case 'json':
				$this->requestHeaders[] = 'Content-Type: application/json';
				!is_string($body) && ($body = json_encode($body, \PHP_VERSION_ID >= 70300 ? JSON_THROW_ON_ERROR : 0));
				break;
		}

		$this->options['method']                   = 'post';
		$this->options['curl'][CURLOPT_POSTFIELDS] = $body;
	}

	protected function setProxy ()
	{
		if (!array_key_exists('proxy', $this->options)) {
			return;
		}

		$this->setCurlProxy($this->options['proxy']);
		unset($this->options['proxy']);
	}

	protected function setHeaders ()
	{
		$this->processUserHeaders();

		$this->options['curl'][CURLOPT_HTTPHEADER] = $this->requestHeaders;
	}

	protected function removeHeader ($key)
	{
		$remove = [];

		foreach ($this->requestHeaders as $index => $header) {
			list($name, $value) = explode(':', $header);
			if (strtolower($name) === strtolower($key)) {
				$remove[] = $header;
			}
		}

		$this->requestHeaders = array_diff($this->requestHeaders, $remove);
	}

	protected function processUserHeaders ()
	{
		if (!array_key_exists('headers', $this->options)) {
			return;
		}

		$headers = (array) $this->options['headers'];

		foreach ($headers as $key => $header) {
			$this->requestHeaders[] = (!is_int($key) ? "{$key}: " : '') . $header;
		}


		unset($this->options['headers']);
	}
}