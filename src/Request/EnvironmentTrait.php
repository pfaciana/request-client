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
		$command = $this->isWindows() ? 'netstat -aon | findstr ":9050" | findstr "LISTENING"' : 'ps aux | grep -w [t]or';

		return !empty(exec($command));
	}

	public function restartTor ()
	{
		$output = shell_exec(($this->isWindows() ? 'wsl ' : '') . 'sudo service tor restart');
		sleep(3);

		return $output;
	}
}