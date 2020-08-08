<?php


namespace RequestClient;

use RequestClient\Request\UserAgentTrait;
use RequestClient\Request\QueryTrait;

abstract class Browser
{
	use UserAgentTrait, QueryTrait;

	protected $requestHeader;
	protected $responseHeader;
	protected $request;
	protected $response;
	protected $cookies;
	protected $url;
	protected $origUrl;
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

	public function requestSucceeded ()
	{
		return $this->statusCode > 0 && $this->statusCode < 400;
	}

	public function requestFailed ()
	{
		return !is_null($this->statusCode) && !$this->requestSucceeded();
	}

	public function getUrl ()
	{
		return $this->url;
	}

	public function getOrigUrl ()
	{
		return $this->origUrl;
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