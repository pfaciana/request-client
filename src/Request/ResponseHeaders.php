<?php


namespace RequestClient\Request;


class ResponseHeaders extends Headers
{
	public function setHeaderWithoutKey ($header)
	{
		list($protocol, $status) = explode(' ', trim($header));
		$this->headers['protocol'][] = $protocol;
		$this->headers['status'][]   = $status;
	}
}