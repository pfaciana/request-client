<?php


namespace RequestClient\Request;


trait EnvironmentTrait
{
	protected $torRestartedAt = NULL;

	/**
	 * Checks if the current system is a Windows box or not
	 *
	 * @return bool `true` for Windows, `false` for other
	 */
	public function isWindows ()
	{
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	/**
	 * Checks to see if tor is running on the current system
	 *
	 * @return bool `true` for running, `false` for disabled or does not exist
	 */
	public function isTorEnabled ()
	{
		$command = $this->isWindows() ? 'netstat -aon | findstr ":9050" | findstr "LISTENING"' : 'ps aux | grep -w [t]or';

		return !empty(exec($command));
	}

	/**
	 * Attempts to restart the local tor service, if allowed
	 *
	 * @param int $sleep Seconds to pause after restart
	 * @return string The command line output of the restart command
	 */
	public function restartTor ($sleep = 3)
	{
		global $torRestartedAt;

		$output = shell_exec(($this->isWindows() ? 'wsl ' : '') . 'sudo service tor restart');
		sleep($sleep);

		$torRestartedAt = $this->torRestartedAt = time();

		return $output;
	}

	/**
	 * Checks to see if tor has been externally restarted since the last request
	 *
	 * Used when knowing if the user needs to establish a new connection to the tor control client
	 *
	 * @param bool $sync Whether to persist the out of sync flag or not. `true` syncs it, `false` keeps it out of date.
	 * @return bool `true` on out of date, `false` on up to date
	 */
	public function hasTorBeenRestarted ($sync = TRUE)
	{
		global $torRestartedAt;

		if ($torRestartedAt === $this->torRestartedAt) {
			return FALSE;
		}

		if (!empty($sync)) {
			$this->torRestartedAt = $torRestartedAt;
		}

		return TRUE;
	}

	/**
	 * Get the current IP address being used
	 *
	 * @param bool $skipTor Whether to avoid using tor for this request. If you're not using tor, this needs to be `true`.
	 * @return string The current IP address being used to exit the curl request from.
	 */
	public function getIP ($skipTor = FALSE)
	{
		$torify = !$skipTor && $this->isTorEnabled() ? 'torify' : '';
		$wsl    = $this->isWindows() ? 'wsl' : '';

		return trim(exec(trim("{$wsl} {$torify} curl ifconfig.me 2>&1")));
	}

	/**
	 * Get the Location of an IP address
	 *
	 * @param string      $key     The name of the key you want returned from the response object that ipinfo.io sends back. Defaults to `country`.
	 * @param null|string $ip      The IP address to check, use `null` for the current IP being used. Defaults to `null`.
	 * @param bool        $skipTor Whether to avoid using tor for this request. If you're not using tor, this needs to be `true`.
	 * @return bool|string|object The value for the $key requested, or `false` if the $key does not exist in the response, and the entire response object if $key is empty.
	 */
	public function getLocation ($key = 'country', $ip = NULL, $skipTor = FALSE)
	{
		$torify  = !$skipTor && $this->isTorEnabled() ? 'torify' : '';
		$wsl     = $this->isWindows() ? 'wsl' : '';
		$command = "{$wsl} {$torify} curl -s ipinfo.io";
		!empty($ip) && ($command .= "/{$ip}");

		$response = json_decode(shell_exec(trim("{$command} 2>&1")), TRUE);

		return is_string($key) && !empty($key) ? (array_key_exists($key, $response) ? $response[$key] : FALSE) : $response;

	}
}