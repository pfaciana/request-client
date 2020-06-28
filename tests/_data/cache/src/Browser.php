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
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->request;
	}

	public function getRequestHeaders ($join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($join), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->requestHeader->getHeaders(...func_get_args());
	}

	public function getRequestHeader ($key, $join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $join), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->requestHeader->getHeader(...func_get_args());
	}

	public function getResponseHeaders ($join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($join), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->responseHeader->getHeaders(...func_get_args());
	}

	public function getResponseHeader ($key, $join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $join), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->responseHeader->getHeader(...func_get_args());
	}

	public function getResponse ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->response;
	}

	public function getStatusCode ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->statusCode;
	}

	public function getUrl ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->url;
	}

	public function getCookies ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->cookies;
	}

	public function getCookie ($key, $path = NULL, $domain = NULL)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $path, $domain), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->cookies->get(...func_get_args());
	}
}