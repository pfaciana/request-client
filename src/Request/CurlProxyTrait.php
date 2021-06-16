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

	public function getNewExitNode ()
	{
		if (empty($this->torClient)) {
			return FALSE;
		}

		$countries = $this->torClient->getConf('ExitNodes')['ExitNodes'];
		$this->torClient->setConf(['ExitNodes' => $countries . ',' . $countries]);
		$this->torClient->setConf(['ExitNodes' => $countries]);
	}

	public function connectToTorClient ()
	{
		$this->torClient = new \Dapphp\TorUtils\ControlClient();
		$this->torClient->connect($this->torClientSettings['host'], $this->torClientSettings['port']);
		if (isset($this->torClientSettings['password'])) {
			$this->torClient->authenticate($this->torClientSettings['password']);
		}
	}

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

	public function resetProxy ()
	{
		if (!empty($this->torClient) && !empty($this->origTorClientConfig)) {
			$this->torClient->setConf($this->origTorClientConfig);
		}
	}
}