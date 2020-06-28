<?php


namespace RequestClient\Request;


class RequestHeaders extends Headers
{
	public function setHeaderWithoutKey ($header)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($header), false)) !== __AM_CONTINUE__) return $__am_res; 
		list($method, $location, $protocol) = explode(' ', trim($header));
		$this->headers['action'][]   = $method;
		$this->headers['protocol'][] = $protocol;
		$this->headers['location'][] = $location;
	}
}