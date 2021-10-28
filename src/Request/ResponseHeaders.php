<?php


namespace RequestClient\Request;


class ResponseHeaders extends Headers
{
	// Comment to fix AspectMock bug

	public function setHeaderWithoutKey ($header)
	{
		[$protocol, $status] = explode(' ', trim($header));
		$this->headers['protocol'][] = $protocol;
		$this->headers['status'][]   = $status;
	}
}