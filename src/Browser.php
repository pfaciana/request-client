<?php


namespace RequestClient;

use RequestClient\Request\UserAgentTrait;

abstract class Browser
{
	use UserAgentTrait;

	protected $requestHeader;
	protected $responseHeader;
	protected $request;
	protected $response;
	protected $cookies;
	protected $url;
	protected $statusCode;

	public function getRequest ()
	{
		return $this->request;
	}

	public function getRequestHeaders ($join = '; ')
	{
		return $this->requestHeader->getHeaders(...func_get_args());
	}

	public function getRequestHeader ($key, $join = '; ')
	{
		return $this->requestHeader->getHeader(...func_get_args());
	}

	public function getResponseHeaders ($join = '; ')
	{
		return $this->responseHeader->getHeaders(...func_get_args());
	}

	public function getResponseHeader ($key, $join = '; ')
	{
		return $this->responseHeader->getHeader(...func_get_args());
	}

	public function getResponse ()
	{
		return $this->response;
	}

	public function getStatusCode ()
	{
		return $this->statusCode;
	}

	public function getUrl ()
	{
		return $this->url;
	}

	public function getCookies ()
	{
		return $this->cookies;
	}

	public function getCookie ($key, $path = NULL, $domain = NULL)
	{
		return $this->cookies->get(...func_get_args());
	}
}