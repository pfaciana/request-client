<?php

namespace RequestClient;

class Curl extends CurlSession
{
	public function request ($url, $options = [])
	{
		return $this->init($url, $options)->exec();
	}
}