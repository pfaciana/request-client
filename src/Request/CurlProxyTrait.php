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

	public function setCurlProxy ($settings)
	{
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
		}
		elseif ($settings['type'] === 'apify') {
			$settings += ['host' => 'http://proxy.apify.com', 'port' => '8000', 'username' => 'auto'];

			$this->options['curl'] = [
					CURLOPT_HTTPPROXYTUNNEL => 1,
					CURLOPT_PROXY           => $settings['host'] . ':' . $settings['port'],
					CURLOPT_PROXYUSERPWD    => $settings['username'] . ':' . $settings['password'],
				] + $this->options['curl'];
		}
	}

}