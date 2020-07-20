<?php


namespace RequestClient;

use RequestClient\Request\CookieJar;
use RequestClient\Request\CurlBrowserOptions;
use RequestClient\Request\RequestHeaders;
use RequestClient\Request\ResponseHeaders;

class CurlSession extends CurlBrowser
{
	protected $ch;
	protected $pos;
	protected $options = [];

	public function __construct ($curlOptions = [])
	{
		$curlOptions += [CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]];
		$this->setCurlOptions($curlOptions);
		$this->cookies = new CookieJar();
	}

	public function init ($url, $options = [])
	{
		$this->url = $this->origUrl = $url;
		$this->ch  = curl_init($url);
		is_int($options) && ($this->pos = $options);
		is_array($options) && $this->setOptions($options);
		$this->options = [];

		return $this;
	}

	public function setOptions ($options = [])
	{
		$options['url'] = $this->url;

		$options['curl'] = $options['curl'] ?? [];

		$this->responseHeader = new ResponseHeaders();

		$options['curl'] += [CURLOPT_HEADERFUNCTION => [$this->responseHeader, 'setHeaderFromStream']];

		if (empty($options['curl'][CURLOPT_COOKIE]) && !empty($cookies = $this->cookies->getHeaderCookie('; ', $this->url))) {
			$options['curl'][CURLOPT_COOKIE] = $cookies;
		}

		$this->options = new CurlBrowserOptions($options + $this->options);

		$curlOptions = $this->getCurlOptions($this->options->get('curl', []));

		curl_setopt_array($this->ch, $curlOptions);

		$this->url = $curlOptions[CURLOPT_URL] ?? $options['url'];

		if (array_key_exists(CURLOPT_POSTFIELDS, $curlOptions)) {
			$this->request = $curlOptions[CURLOPT_POSTFIELDS];
		}

		return $this;
	}

	public function exec ($close = TRUE)
	{
		$this->response = !is_int($this->pos) ? curl_exec($this->ch) : curl_multi_getcontent($this->ch);

		$this->getInfo = curl_getinfo($this->ch);

		$this->url = !empty($this->getInfo['url']) ? $this->getInfo['url'] : $this->url;

		if (empty($this->statusCode = $this->getInfo['http_code']) && empty($this->response)) {
			$this->response = FALSE;
		}

		$this->requestHeader = new RequestHeaders($this->getInfo['request_header'] ?? '');

		if (!empty($setCookie = $this->responseHeader->getHeader('set-cookie', FALSE))) {
			$this->cookies->set($setCookie, $this->url);
		}

		$close && $this->close();

		return $this->response;
	}

	public function getPosition ()
	{
		return $this->pos;
	}

	public function getHandle ()
	{
		return $this->ch;
	}

	public function close ()
	{
		curl_close($this->ch);

		return $this;
	}
}