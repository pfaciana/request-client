<?php


namespace RequestClient\Request;


class Cookie
{
	protected $url;
	protected $urlParts;
	protected $key;
	protected $value;
	protected $params = [];
	protected $timestamp;

	public function __construct ($cookie = NULL, $url = NULL, $timestamp = NULL)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($cookie, $url, $timestamp), false)) !== __AM_CONTINUE__) return $__am_res; 
		$this->url       = $url;
		$this->urlParts  = $url ? parse_url($url) : [];
		$this->timestamp = is_int($timestamp) || ctype_digit($timestamp) ? (int) $timestamp : time();

		if (is_string($cookie)) {
			$this->parseCookie(urldecode($cookie));
		}

		if (is_array($cookie)) {
			$this->parseCookieArray($cookie);
		}
	}

	protected function parseCookie ($cookie)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($cookie), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (empty($cookie) || !is_string($cookie)) {
			return FALSE;
		}

		$params = explode(';', $cookie);

		$keyValue = array_shift($params);

		list($this->key, $this->value) = explode('=', trim($keyValue));

		foreach ($params as $param) {
			if (strpos($param, '=')) {
				list($key, $value) = explode('=', trim($param));
				$key = trim($key);
			}
			else {
				$key   = trim($param);
				$value = TRUE;
			}

			$this->params[$key] = $this->filterSetValue($key, $value);
		}

		return TRUE;
	}

	protected function parseCookieArray ($cookie)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($cookie), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (empty($cookie) || !is_array($cookie)) {
			return FALSE;
		}

		$isFirst = TRUE;
		foreach ($cookie as $key => $value) {
			if ($isFirst) {
				$this->key   = $key;
				$this->value = $value;
				$isFirst     = FALSE;
				continue;
			}

			$this->params[$key] = $this->filterSetValue($key, $value);
		}

		return TRUE;
	}

	public function setCookie ($name = NULL, $value = NULL, $expires = 0, $path = '', $domain = '', $secure = FALSE, $httponly = FALSE, $samesite = NULL, $maxage = NULL)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $maxage), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (is_scalar($name)) {
			$this->key = $name;
		}

		if (is_scalar($value)) {
			$this->value = $value;
		}

		$options = is_array($expires) ? $expires : [];

		if (empty($options)) {
			if (isset($maxage)) {
				$options['Max-Age'] = $maxage;
			}
			elseif ($expires) { // falsy means session cookie
				$options['Expires'] = $expires;
			}
			if (!empty($domain) && is_string($domain)) {
				$options['Domain'] = $domain;
			}
			if (!empty($path) && is_string($path)) {
				$options['Path'] = $path;
			}
			if (!empty($samesite)) {
				$options['SameSite'] = $samesite;
			}
			if ($secure) {
				$options['Secure'] = !!$secure;
			}
			if ($httponly) {
				$options['HttpOnly'] = !!$httponly;
			}
		}

		foreach ($options as $key => $value) {
			$this->params[$key] = $this->filterSetValue($key, $value);
		}

		return TRUE;
	}

	public function getCookie ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!isset($this->key) || !isset($this->value)) {
			return NULL;
		}

		return $this->key . '=' . urlencode($this->value);
	}

	public function getFullCookie ($join = '; ', $keys = [])
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($join, $keys), false)) !== __AM_CONTINUE__) return $__am_res; 
		$keys = array_map('strtolower', (array) $keys);

		$fullCookie = [$this->getCookie()];

		foreach ($this->params as $key => $value) {
			if (!$keys || in_array(strtolower($key), $keys)) {
				if ($param = $this->getFilteredParam($key, $value)) {
					$fullCookie[] = $param;
				}
			}
		}

		return implode($join, $fullCookie);
	}

	protected function getFilteredParam ($key, $value)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $value), false)) !== __AM_CONTINUE__) return $__am_res; 
		$value = $this->filterGetValue($key, $value, FALSE, TRUE);

		if (is_bool($value)) {
			return $value ? $key : NULL;
		}

		return "{$key}={$value}";
	}

	public function setKey ($key)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->key = $key;
	}

	public function getKey ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->key;
	}

	public function setValue ($value)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($value), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->value = $value;
	}

	public function getValue ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->value;
	}

	public function setParam ($key, $value = TRUE)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $value), false)) !== __AM_CONTINUE__) return $__am_res; 
		foreach ($this->params as $paramKey => $paramValue) {
			if (strtolower($key) === strtolower($paramKey)) {
				if (is_null($value)) {
					unset($this->params[$paramKey]);

					return NULL;
				}

				return $this->params[$paramKey] = $this->filterSetValue($key, $value);
			}
		}

		return $this->params[$key] = $this->filterSetValue($key, $value);
	}

	protected function filterSetValue ($key, $value)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $value), false)) !== __AM_CONTINUE__) return $__am_res; 
		switch (strtolower($key)) {
			case 'expires':
				return $this->getUnixTime($value);
			case 'secure':
				if (array_key_exists('scheme', $this->urlParts) && $this->urlParts['scheme'] !== 'https') {
					return FALSE;
				}
		}

		return $value;
	}

	public function getParam ($key, $raw = FALSE)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $raw), false)) !== __AM_CONTINUE__) return $__am_res; 
		foreach ($this->params as $paramKey => $paramValue) {
			if (strtolower($key) === strtolower($paramKey)) {
				return $this->filterGetValue($paramKey, $paramValue, $raw);
			}
		}

		switch (strtolower($key)) {
			case 'domain':
				return !empty($this->urlParts['host']) ? $this->urlParts['host'] : NULL;
			case 'path':
				return '/';
		}

		return NULL;
	}

	protected function filterGetValue ($key, $value, $raw = FALSE, $orig = FALSE)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $value, $raw, $orig), false)) !== __AM_CONTINUE__) return $__am_res; 
		if ($orig) {
			switch (strtolower($key)) {
				case 'expires':
					return $raw ? $value : $this->getStringDate($value);
			}
		}
		else {
			switch (strtolower($key)) {
				case 'expires':
					if (is_int($maxAge = $this->getParam('max-age'))) {
						if ($maxAge < 1) {
							return NULL;
						}

						$value = $this->timestamp + $maxAge;
					}

					return $raw ? $value : $this->getStringDate($value);
				case 'max-age':
					return $raw ? $value : (int) $value;
			}
		}

		return $value;
	}

	protected function getUnixTime ($date)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($date), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!is_string($date) || ctype_digit($date)) {
			return (int) $date;
		}

		return (int) strtotime(trim($date, " \t\n\r\0\x0B'"));
	}

	protected function getStringDate ($date)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($date), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!is_string($date) || ctype_digit($date)) {
			$date = \DateTime::createFromFormat('U', $date, new \DateTimeZone('GMT'));
			$date = str_replace('+0000', '', $date->format('D, d M Y H:i:s T'));
		}

		return $date;
	}

	public function isExpired ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (is_int($maxAge = $this->getParam('max-age'))) {
			if ($maxAge < 1) {
				return TRUE;
			}

			$expires = $this->timestamp + $maxAge;
		}
		elseif (empty($expires = $this->getParam('expires'))) {
			return FALSE;
		}

		return $this->getUnixTime($expires) < time();
	}
}