<?php


namespace RequestClient\Request;


trait CurlProxyTrait
{
	use EnvironmentTrait;

	protected $curlProxyKeys = [
		CURLOPT_HTTPPROXYTUNNEL,
		CURLOPT_PROXY,
		CURLOPT_PROXYUSERPWD,
		CURLOPT_PROXYTYPE,
	];
	protected $origTorClientConfig = [];
	protected $torClientSettings = [
		'host'     => '127.0.0.1',
		'port'     => 9051,
		'password' => NULL,
	];
	protected $torClient;

	/**
	 * Configure the Proxy
	 *
	 * <code>
	 * $proxy->setCurlProxy([
	 *   'proxy' => [
	 *     'type'    => 'tor',
	 *     'control' => [
	 *       'password'   => 'some-password',
	 *       'country'    => ['US', 'CA', 'CH'],
	 *       'dynamicIP'  => TRUE,
	 *       'validateIP' => TRUE,
	 *     ],
	 *   ],
	 * ]);
	 * </code>
	 *
	 * @param string|array $settings    {
	 *                                  If settings is a string it will save it's value to the $type key
	 * @type string        $type        Type of proxy to connect to. Available options: `tor`|`apify`|`nordvpn`
	 * @type string        $host        Proxy IP/url address to connect to
	 * @type string        $port        Proxy port to connect through
	 * @type string        $username    Username to connect with. Required for `apify`|`nordvpn`
	 * @type string        $password    Password to connect with. Required for `apify`|`nordvpn`
	 * @type string        $restart     Whether to first restart the local proxy. Used with `tor`
	 * @type string        $control     {
	 * @type string        $host        IP/url address to connect to local client. Used with `tor`
	 * @type string        $port        Port the local client is on. Used with `tor`
	 * @type string        $password    Password to connect to the local client. Used with `tor`
	 * @type string        $country     Country to run the ExitNode out of. Short for ['config']['ExitNodes']. Used with `tor`
	 * @type string        $dynamicIP   Tell the client proxy to get a new IP address to exit from. Used with `tor`
	 * @type string        $validateIP  Check to make sure the current IP address is coming from one of the available countries defined in the client proxy config. Used with `tor`
	 * @type string        $config      {
	 * @type string        $ExitNodes   `ExitNodes` setting defined in the client proxy config. Used with `tor`
	 * @type string        $StrictNodes `StrictNodes` setting defined in the client proxy config. Used with `tor`
	 *                                  }
	 *                                  }
	 *                                  }
	 * @return bool|null Whether the settings were applied successfully or null on no settings were passed
	 */
	public function setCurlProxy ($settings)
	{
		if (empty($settings)) {
			return NULL;
		}

		foreach ($this->curlProxyKeys as $proxyKey) {
			unset($this->options['curl'][$proxyKey]);
		}

		$settings = is_string($settings) ? ['type' => $settings] : $settings;

		if ($settings['type'] === 'tor' && $this->isTorEnabled()) {
			$settings += ['host' => '127.0.0.1', 'port' => '9050'];

			$this->options['curl'] = [
					CURLOPT_HTTPPROXYTUNNEL => 1,
					CURLOPT_PROXY           => $settings['host'] . ':' . $settings['port'],
					CURLOPT_PROXYTYPE       => CURLPROXY_SOCKS5_HOSTNAME,
				] + $this->options['curl'];

			if (!empty($settings['restart'])) {
				$this->restartTor();
			}
			if (!empty($settings['restart']) || $this->hasTorBeenRestarted()) {
				$this->torClient = NULL;
			}

			if (!empty($settings['control'])) {
				if (!$this->setTorClient($settings['control'])) {
					return FALSE;
				}
			}
		}
		elseif ($settings['type'] === 'apify') {
			$settings += ['host' => 'http://proxy.apify.com', 'port' => '8000', 'username' => 'auto'];

			$this->options['curl'] = [
					CURLOPT_HTTPPROXYTUNNEL => 1,
					CURLOPT_PROXY           => $settings['host'] . ':' . $settings['port'],
					CURLOPT_PROXYUSERPWD    => $settings['username'] . ':' . $settings['password'],
				] + $this->options['curl'];
		}
		elseif ($settings['type'] === 'nordvpn') {
			foreach ($this->curlProxyKeys as $proxyKey) {
				unset($this->options['curl'][$proxyKey]);
			}

			/* ie.socks.nordhold.net, dublin.ie.socks.nordhold.net
			 * nl.socks.nordhold.net, amsterdam.nl.socks.nordhold.net
			 * se.socks.nordhold.net, stockholm.se.socks.nordhold.net
			 * us.socks.nordhold.net, atlanta.us.socks.nordhold.net, dallas.us.socks.nordhold.net, los-angeles.us.socks.nordhold.net
			 */
			$settings += ['host' => 'us.socks.nordhold.net', 'port' => '1080'];

			$this->options['curl'] += [
				CURLOPT_HTTPPROXYTUNNEL => 1,
				CURLOPT_PROXY           => $settings['host'],
				CURLOPT_PROXYPORT       => $settings['port'],
				CURLOPT_PROXYUSERPWD    => $settings['username'] . ':' . $settings['password'],
				CURLOPT_PROXYTYPE       => CURLPROXY_SOCKS5,
				CURLOPT_PROXYAUTH       => CURLAUTH_BASIC,
			];
		}

		return TRUE;
	}

	/**
	 * Normalize an array or string of countries to used used in tor's `ExitNodes` setting.
	 *
	 * <code>
	 * // As array
	 * $exitNodes = $proxy->normalizeTorExitNodes(['US', 'CA', 'CH']);
	 * $exitNodes = $proxy->normalizeTorExitNodes(['{us}', '{ca}', '{ch}']);
	 * </code>
	 *
	 * <code>
	 * // As string
	 * $exitNodes = $proxy->normalizeTorExitNodes('US,CA,CH']);
	 * $exitNodes = $proxy->normalizeTorExitNodes('{us},{ca},{ch}']);
	 * </code>
	 *
	 * @param string|array $nodes An array or string representing the countries to use in tor's `ExitNodes` setting
	 * @return string The value to save to tor's `ExitNodes`
	 */
	public function normalizeTorExitNodes ($nodes)
	{
		if (empty($nodes)) {
			return '';
		}

		if (is_string($nodes)) {
			$nodes = explode(',', $nodes);
		}

		foreach ($nodes as &$node) {
			$node = '{' . trim($node, " {}\t\n\r\0\x0B") . '}';
		}

		return implode(',', $nodes);
	}

	/**
	 * Configuration to set in tor's client settings
	 *
	 * @param array $config      {
	 * @type string $host        IP/url address to connect to local client. Used with `tor`
	 * @type string $port        Port the local client is on. Used with `tor`
	 * @type string $password    Password to connect to the local client. Used with `tor`
	 * @type string $country     Country to run the ExitNode out of. Short for ['config']['ExitNodes']. Used with `tor`
	 * @type string $dynamicIP   Tell the client proxy to get a new IP address to exit from. Used with `tor`
	 * @type string $validateIP  Check to make sure the current IP address is coming from one of the available countries defined in the client proxy config. Used with `tor`
	 * @type string $config      {
	 * @type string $ExitNodes   `ExitNodes` setting defined in the client proxy config. Used with `tor`
	 * @type string $StrictNodes `StrictNodes` setting defined in the client proxy config. Used with `tor`
	 *                           }
	 *                           }
	 * @return bool|\Dapphp\TorUtils\ControlClient The tor client on success, and `false` on failure
	 */
	public function setTorClient ($config = [])
	{
		// This will persist the `host`, `port` and `password` for future calls
		// So the user does not have to keep passing them
		$this->torClientSettings = $config + $this->torClientSettings;

		if (empty($this->torClient)) {
			$this->connectToTorClient();
		}
		if (!empty($config['country'])) {
			$config['config']['ExitNodes'] = $config['country'];
			unset($config['country']);
		}
		// This is a little trick to get a new IP, but still keep the exit nodes the same
		// Otherwise if you reset the ExitNodes to the exact same value, it won't trigger a node (or IP) change
		if (!empty($config['dynamicIP'])) {
			$this->getNewExitNode();
		}
		if (!empty($config['config'])) {
			$this->origTorClientConfig += $this->torClient->getConf(implode(' ', array_keys($config['config'])));
			if (array_key_exists('ExitNodes', $config['config']) && !empty($config['config']['ExitNodes'])) {
				$config['config']['ExitNodes'] = $this->normalizeTorExitNodes($config['config']['ExitNodes']);
			}
			$this->torClient->setConf($config['config']);
		}
		if (!empty($config['validateIP'])) {
			if (!$this->validateExitNode($config['validateIP'])) {
				return FALSE;
			}
		}

		return $this->torClient;
	}

	/**
	 * Change IP of the tor ExitNode currently being used
	 *
	 * @return bool Returns `true` on success, `false` on failure/tor client does not exist
	 */
	public function getNewExitNode ()
	{
		if (empty($this->torClient)) {
			return FALSE;
		}

		$countries = $this->torClient->getConf('ExitNodes')['ExitNodes'];
		$this->torClient->setConf(['ExitNodes' => $countries . ',' . $countries]);
		$this->torClient->setConf(['ExitNodes' => $countries]);

		return TRUE;
	}

	/**
	 * Connect to the tor control client
	 */
	public function connectToTorClient ()
	{
		$this->torClient = new \Dapphp\TorUtils\ControlClient();
		$this->torClient->connect($this->torClientSettings['host'], $this->torClientSettings['port']);
		if (isset($this->torClientSettings['password'])) {
			$this->torClient->authenticate($this->torClientSettings['password']);
		}
	}

	/**
	 * Verify the ExitNode currently being used is, in fact, existing from the correct country
	 *
	 * @param int $attempts Number of attempts to reconnect to exit ouf of the correct country. Defaults to 30
	 * @return bool Returns `true` if valid, and `false` if not valid
	 */
	public function validateExitNode ($attempts = 30)
	{
		if (empty($this->torClient)) {
			return FALSE;
		}

		if (!is_int($attempts) || $attempts < 1) {
			$attempts = 30;
		}

		$countries = explode(',', strtolower($this->torClient->getConf('ExitNodes')['ExitNodes']));

		if (!empty($country = $this->getLocation())) {
			$country = '{' . strtolower($country) . '}';
		}

		while ($attempts-- && (empty($country) || !in_array($country, $countries))) {
			$this->getNewExitNode();
			if (!empty($country = $this->getLocation())) {
				$country = '{' . strtolower($country) . '}';
			}
		}

		if (!in_array($country, $countries)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get the current connection to the tor control client
	 *
	 * @return null|\Dapphp\TorUtils\ControlClient The tor client, or `null` if does not exist.
	 */
	public function getTorClient ()
	{
		if (!empty(func_get_args())) {
			return $this->setTorClient(...func_get_args());
		}
		elseif (empty($this->torClient) || $this->hasTorBeenRestarted()) {
			$this->connectToTorClient();
		}

		return $this->torClient ?: NULL;
	}

	/**
	 * Reset the Proxy back to the original settings once the connection is closed
	 */
	public function resetProxy ()
	{
		if (!empty($this->torClient) && !empty($this->origTorClientConfig)) {
			$this->torClient->setConf($this->origTorClientConfig);
		}
	}
}