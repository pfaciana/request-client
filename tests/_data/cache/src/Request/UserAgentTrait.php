<?php


namespace RequestClient\Request;


trait UserAgentTrait
{
	protected $userAgents = [
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_1) AppleWebKit/537.35 (KHTML, like Gecko) Chrome/27.0.1253.110 Safari/537.35',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1 Safari/605.1.15',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.113 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:75.0) Gecko/20100101 Firefox/75.0',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36',
	];

	public function setUserAgents ($userAgents = [], $merge = FALSE)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($userAgents, $merge), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!$userAgents) {
			return FALSE;
		}

		$userAgents = (array) $userAgents; // cast to array;

		return $this->userAgents = $merge ? array_merge($this->userAgents, $userAgents) : $userAgents;
	}

	public function getUserAgents ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->userAgents;
	}

	public function getUserAgent ($index = FALSE)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($index), false)) !== __AM_CONTINUE__) return $__am_res; 
		if ($index === FALSE || empty($this->userAgents[$index])) {
			$index = array_rand($this->userAgents);
		}

		return $this->userAgents[$index];
	}

}