<?php


namespace RequestClient\Request;


trait EnvironmentTrait
{
	protected $torRestartedAt = NULL;

	public function isWindows ()
	{
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	public function isTorEnabled ()
	{
		$command = $this->isWindows() ? 'netstat -aon | findstr ":9050" | findstr "LISTENING"' : 'ps aux | grep -w [t]or';

		return !empty(exec($command));
	}

	public function restartTor ($sleep = 3)
	{
		global $torRestartedAt;

		$output = shell_exec(($this->isWindows() ? 'wsl ' : '') . 'sudo service tor restart');
		sleep($sleep);

		$torRestartedAt = $this->torRestartedAt = time();

		return $output;
	}

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

	public function getIP ($skipTor = FALSE)
	{
		$torify = !$skipTor && $this->isTorEnabled() ? 'torify' : '';
		$wsl    = $this->isWindows() ? 'wsl' : '';

		return trim(exec(trim("{$wsl} {$torify} curl ifconfig.me 2>&1")));
	}
}