<?php


namespace RequestClient\Request;


class RequestHeaders extends Headers
{
	public function setHeaderWithoutKey ($header)
	{
		list($method, $location, $protocol) = explode(' ', trim($header));
		$this->headers['action'][]   = $method;
		$this->headers['protocol'][] = $protocol;
		$this->headers['location'][] = $location;
	}
}