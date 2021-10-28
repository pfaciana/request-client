<?php


namespace RequestClient\Request;


class RequestHeaders extends Headers
{
	// Comment to fix AspectMock bug

	public function setHeaderWithoutKey ($header)
	{
		[$method, $location, $protocol] = explode(' ', trim($header));
		$this->headers['action'][]   = $method;
		$this->headers['protocol'][] = $protocol;
		$this->headers['location'][] = $location;
	}
}