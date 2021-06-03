<?php


namespace RequestClient\Request;


/**
 * Class Cookie
 *
 * @package RequestClient\Request
 */
class Cookie
{
	/**
	 * @var string current url
	 */
	protected $url;

	/**
	 * @var array parsed parts of the url
	 */
	protected $urlParts;

	/**
	 * @var int unix time of the request
	 */
	protected $timestamp;

	/**
	 * @var string cookie name
	 */
	protected $key;

	/**
	 * @var string cookie value
	 */
	protected $value;

	/**
	 * @var array associative array of cookie properties
	 */
	protected $params = [];

	/**
	 * Cookie constructor.
	 *
	 * @param string|array $cookie    can be either a url encode string or a parsed array
	 *                                @@ex 'a=b;c=1;e;f;'
	 *                                @@ex ['a' => 'b', 'c' => 1, 'e' => true, 'f' => true]
	 * @param string       $url       current page url, used for Secure and Domain properties
	 * @param int          $timestamp current time in Unix, used for Expires property
	 */
	public function __construct ($cookie = NULL, $url = NULL, $timestamp = NULL)
	{
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

	/**
	 * Parses the input cookie string
	 *
	 * @param string $cookie Url encode string of key value pairs joined by a semi-colon
	 * @return bool `TRUE` on success and `FALSE` on failure
	 */
	protected function parseCookie ($cookie)
	{
		if (empty($cookie) || !is_string($cookie)) {
			return FALSE;
		}

		$params = explode(';', $cookie);

		$keyValue = array_shift($params);

		[$this->key, $this->value] = explode('=', trim($keyValue));

		foreach ($params as $param) {
			if (strpos($param, '=')) {
				[$key, $value] = explode('=', trim($param));
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

	/**
	 * Parses the input cookie array
	 *
	 * @param array $cookie Associative array of cookie properties
	 * @return bool `TRUE` on success and `FALSE` on failure
	 */
	protected function parseCookieArray ($cookie)
	{
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

	/**
	 * Manually set all the cookie arguments
	 *
	 * The third parameter can also be an associative array of all remaining parameters
	 *
	 * @param string $name     Name
	 * @param string $value    Value
	 * @param mixed  $expires  Expires
	 *                         !!note: Can be all parameters in an associative array
	 * @param string $path     Path
	 * @param string $domain   Domain
	 * @param bool   $secure   Secure
	 * @param bool   $httponly HttpOnly
	 * @param string $samesite SameSite
	 * @param int    $maxage   Max-Age
	 * @return bool `TRUE` on success
	 */
	public function setCookie ($name = NULL, $value = NULL, $expires = 0, $path = '', $domain = '', $secure = FALSE, $httponly = FALSE, $samesite = NULL, $maxage = NULL)
	{
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

	/**
	 * Get a url encoded key/value pair for the cookie
	 *
	 * @return mixed The value of the key, or `NULL` if undefined
	 *               !!ex 'a=b'
	 *               !!ex NULL
	 */
	public function getCookie ()
	{
		if (!isset($this->key) || !isset($this->value)) {
			return NULL;
		}

		return $this->key . '=' . urlencode($this->value);
	}

	/**
	 * Get the full cookie with all it's parameters
	 *
	 * @param string $join The delimiter to join the params
	 * @param array  $keys Keys to filter in the response
	 *                     !!note: missing keys do not get returned
	 * @return string Url encoded string
	 */
	public function getFullCookie ($join = '; ', $keys = [])
	{
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

	/**
	 * Creates the key value pair fo the Full Cookie
	 *
	 * @param string $key   Name
	 * @param mixed  $value Value
	 * @return string|null Part of the Full Cookie
	 */
	protected function getFilteredParam ($key, $value)
	{
		$value = $this->filterGetValue($key, $value, FALSE, TRUE);

		if (is_bool($value)) {
			return $value ? $key : NULL;
		}

		return "{$key}={$value}";
	}

	/**
	 * Set the key for the cookie
	 *
	 * @param string $key Name of the key
	 * @return mixed The key name
	 */
	public function setKey ($key)
	{
		return $this->key = $key;
	}

	/**
	 * Get the key for the cookie
	 *
	 * @return mixed key name
	 */
	public function getKey ()
	{
		return $this->key;
	}

	/**
	 * Set the value for the cookie.
	 * No normalizing is done at this stage.
	 *
	 * @param mixed $value The value to be set
	 * @return mixed The value that was set
	 */
	public function setValue ($value)
	{
		return $this->value = $value;
	}

	/**
	 * Get value for this cookie
	 *
	 * @return mixed cookie value
	 */
	public function getValue ()
	{
		return $this->value;
	}

	/**
	 * Manually define a key
	 *
	 * @param string $key   The key to set
	 *                      !!note: Case-insensitive
	 *                      !!note: If `$key = NULL`, then the field is `removed`
	 * @param mixed  $value The value of the key
	 * @return mixed The set value (with `$raw = FALSE`)
	 */
	public function setParam ($key, $value = TRUE)
	{
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

	/**
	 * Normalize parameter values
	 *
	 * @param string $key   Name
	 * @param mixed  $value Value
	 * @return mixed Normalized value.
	 */
	protected function filterSetValue ($key, $value)
	{
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

	/**
	 * Get a cookie details by key
	 *
	 * Keys with empty values return TRUE
	 * !!ex: 'cookieKey=cookieValue;Secure' <-- Secure is TRUE
	 *
	 * Keys that do not exist return NULL
	 * !!ex: 'cookieKey=cookieValue' <-- Secure is NULL
	 *
	 * 'Secure' will return FALSE if the 'Secure' key is defined, but the url is not Secure
	 * !!ex: 'cookieKey=cookieValue;Secure', 'http://some-url.com' <-- Not Secure, Secure is FALSE
	 *
	 * The 'Domain' key will override the current url inputted
	 * !!ex: 'cookieKey=cookieValue;Domain=some-site.com', 'http://subdomain.some-url.com' <-- Domain is 'some-site.com'
	 *
	 * @param string $key Cookie key to retrieve
	 *                    !!note: case-insensitive
	 * @param bool   $raw Preserves input type, defaults to `FALSE`.
	 *                    Use `TRUE` when you want to return unix time for `Expires`
	 * @return mixed The value for `$key`
	 */
	public function getParam ($key, $raw = FALSE)
	{
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

	/**
	 * Get a normalized value for a property
	 * Used specifically for determining `Expires` when there is `Max-Age`
	 *
	 * @param string $key   Property Name
	 * @param mixed  $value Property Value
	 * @param bool   $raw   Preserve input original type in response
	 * @param bool   $orig  Ignore `Max-Age` when getting `Expires`
	 *                      !!note: `Max-Age` normally will override `Expires`
	 * @return mixed
	 */
	protected function filterGetValue ($key, $value, $raw = FALSE, $orig = FALSE)
	{
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

	/**
	 * Get the Unix Time.
	 * Used for `Expires`
	 *
	 * @param string $date String formatted date
	 * @return int Unix Time
	 */
	protected function getUnixTime ($date)
	{
		if (!is_string($date) || ctype_digit($date)) {
			return (int) $date;
		}

		return (int) strtotime(trim($date, " \t\n\r\0\x0B'"));
	}

	/**
	 * Get the string formatted date in GMT
	 *
	 * @param int $date Unix Time
	 * @return string string formatted date
	 *                  !!note: Format: `D, d M Y H:i:s T`
	 */
	protected function getStringDate ($date)
	{
		if (!is_string($date) || ctype_digit($date)) {
			$date = \DateTime::createFromFormat('U', $date, new \DateTimeZone('GMT'));
			$date = str_replace('+0000', '', $date->format('D, d M Y H:i:s T'));
		}

		return $date;
	}

	/**
	 * Determines if the cookie is expired or not
	 *
	 * A cookie is NOT expired if
	 * * there is no `Expires`, `Max-Age` data provided
	 * * `Expires` is in the past
	 * * `Max-Age` is <= 0
	 * !!note: `Max-Age` overrides `Expires`
	 *
	 * @return bool Is the cookie expired
	 */
	public function isExpired ()
	{
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