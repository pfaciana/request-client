<?php


namespace RequestClient\Request;


class CookieJar
{
	protected $cookieJar = [];

	public function __construct ($cookies = [], $url = NULL)
	{
		$this->set($cookies, $url);
	}

	protected function setSingle ($cookie, $url = NULL)
	{
		if (!is_object($cookie) || get_class($cookie) !== 'RequestClient\Request\Cookie') {
			$cookie = new Cookie($cookie, $url);
		}

		$this->cookieJar[$cookie->getParam('domain') ?? $url ?? '*'][$cookie->getParam('path')][$cookie->getKey()] = $cookie;

	}

	protected function setAll ($cookies = [], $url = NULL)
	{
		foreach ($cookies as $cookie) {
			$this->setSingle($cookie, $url);
		}
	}

	public function set ($cookies, $url = NULL)
	{
		is_array($cookies) ? $this->setAll($cookies, $url) : $this->setSingle($cookies, $url);
	}

	public function get ($key, $path = NULL, $domain = NULL)
	{
		$path = $path ?? '/';

		$this->flushExpiredCookies();

		foreach ($this->cookieJar as $cookieDomain => $pathCookies) {
			if ($cookieDomain && $domain) {
				$cookieDomain = '.' . ltrim($cookieDomain, '.');
				if ($cookieDomain !== substr('.' . $domain, -\strlen($cookieDomain))) {
					continue;
				}
			}

			foreach ($pathCookies as $cookiePath => $namedCookies) {
				if (0 !== strpos($path, $cookiePath)) {
					continue;
				}
				if (isset($namedCookies[$key])) {
					return $namedCookies[$key];
				}
			}
		}

		return NULL;
	}

	public function expire ($name, $path = NULL, $domain = NULL)
	{
		$path    = $path ?? '/';
		$domains = !$domain ? array_keys($this->cookieJar) : [$domain];

		foreach ($domains as $domain) {
			unset($this->cookieJar[$domain][$path][$name]);

			if (empty($this->cookieJar[$domain][$path])) {
				unset($this->cookieJar[$domain][$path]);

				if (empty($this->cookieJar[$domain])) {
					unset($this->cookieJar[$domain]);
				}
			}
		}
	}

	public function clear ()
	{
		$this->cookieJar = [];
	}

	public function getAll ($uri = NULL, $join = FALSE)
	{
		$this->flushExpiredCookies();

		$parts = $uri ? array_replace(['path' => '/'], parse_url($uri)) : [];

		$join = is_string($join) ? $join : FALSE;

		$cookies = [];
		foreach ($this->cookieJar as $domain => $pathCookies) {
			if ($domain && !empty($parts['host'])) {
				$domain = '.' . ltrim($domain, '.');
				if ($domain != substr('.' . $parts['host'], -\strlen($domain))) {
					continue;
				}
			}

			foreach ($pathCookies as $path => $namedCookies) {
				if (!empty($parts['path']) && $path != substr($parts['path'], 0, \strlen($path))) {
					continue;
				}

				foreach ($namedCookies as $cookie) {
					if (!empty($parts['scheme']) && $cookie->getParam('secure') && 'https' != $parts['scheme']) {
						continue;
					}

					if ($join) {
						$cookies[$cookie->getKey()] = $cookie->getValue();
					}
					else {
						$cookies[] = $cookie;
					}
				}
			}
		}

		return $join ? http_build_query($cookies, NULL, $join) : $cookies;
	}

	public function getHeaderCookie ($join = '; ', $uri = NULL, $includePrefix = FALSE)
	{
		return ($includePrefix ? 'Cookie: ' : '') . $this->getAll($uri, $join);
	}

	public function flushExpiredCookies ()
	{
		foreach ($this->cookieJar as $domain => $pathCookies) {
			foreach ($pathCookies as $path => $namedCookies) {
				foreach ($namedCookies as $name => $cookie) {
					if ($cookie->isExpired()) {
						unset($this->cookieJar[$domain][$path][$name]);
					}
				}
			}
		}
	}
}
