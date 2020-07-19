<?php


namespace RequestClient\Request;


trait EnvironmentTrait
{
	public function isWindows ()
	{
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	public function isTorEnabled ()
	{
		$command = $this->isWindows() ? 'netstat -aon | findstr ":9050"' : 'ps aux | grep -w [t]or';

		return !empty(exec($command));
	}

	public function restartTor ()
	{
		return !$this->isWindows() && $this->isTorEnabled() && exec('service tor restart') && (sleep(3) === 0);
	}
}