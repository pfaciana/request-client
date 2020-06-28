<?php

namespace RequestClient;

use RequestClient\Request\CookieJar;
use RequestClient\Request\RequestHeaders;
use RequestClient\Request\ResponseHeaders;

class Curl extends CurlBrowser
{
	public function __construct ($curlOptions = [])
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($curlOptions), false)) !== __AM_CONTINUE__) return $__am_res; 
		$this->cookies = new CookieJar();
		$curlOptions   += [CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]];
		$this->setCurlOptions($curlOptions);
	}

	public function request ($url, $options = [])
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($url, $options), false)) !== __AM_CONTINUE__) return $__am_res; 
		$ch = curl_init($url);

		$this->responseHeader = new ResponseHeaders();

		$options += ['curl' => [CURLOPT_HEADERFUNCTION => [$this->responseHeader, 'setHeaderFromStream']]];
		$this->setCurlOptions($options['curl']);

		if (empty($options['curl'][CURLOPT_COOKIE]) && !empty($cookies = $this->cookies->getHeaderCookie('; ', $url))) {
			$options['curl'][CURLOPT_COOKIE] = $cookies;
		}

		curl_setopt_array($ch, $this->getCurlOptions($options['curl'] ?? []));

		$this->response = curl_exec($ch);

		$this->getInfo = curl_getinfo($ch);

		$this->url = $this->getInfo['url'] ?: $url;

		$this->statusCode = $this->getInfo['http_code'];

		$this->requestHeader = new RequestHeaders($this->getInfo['request_header']);

		if (!empty($setCookie = $this->responseHeader->getHeader('set-cookie', FALSE))) {
			$this->cookies->set($setCookie, $this->url);
		}

		curl_close($ch);

		return $this->response;
	}
}