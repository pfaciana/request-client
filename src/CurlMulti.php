<?php

namespace RequestClient;

use RequestClient\Request\CurlOptionsTrait;
use RequestClient\Request\UserAgentTrait;

class CurlMulti
{
	use CurlOptionsTrait, UserAgentTrait;

	protected $mch;
	protected $statusConfig = [];

	public function __construct ($statusConfig = [], $curlOptions = [])
	{
		$curlOptions += [CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]];
		$this->setCurlOptions($curlOptions);
		$this->statusConfig = $statusConfig;
	}

	public function run ($urls, $callback = NULL, $callfront = NULL)
	{
		$this->statusConfig += ['curl' => $this->getCurlOptions()];

		$manager = new CurlSessionManager($urls, $this->statusConfig);

		if (!$manager->isValid()) {
			return $manager->addError('Requests URLs are missing', -1);
		}

		$this->mch = curl_multi_init();
		curl_multi_setopt($this->mch, CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$manager->addSessionsToGroup($this->mch, $callfront);

		do {
			while (($exec_code = curl_multi_exec($this->mch, $still_running)) == CURLM_CALL_MULTI_PERFORM)
				;

			if ($exec_code != CURLM_OK && $exec_code != CURLM_CALL_MULTI_PERFORM) {
				$manager->groupFailed($this->getCurlConstant($exec_code, 'CURLM_'));
				continue;
			}

			while ($handle_info = curl_multi_info_read($this->mch)) {
				$ch = $handle_info['handle'];

				$errorCode = $handle_info['result'] == CURLE_OK ? NULL : $this->getCurlConstant($handle_info['result'], 'CURLE_');

				$manager->processSession($ch, $callback, $errorCode);
				$manager->addSessionsToGroup($this->mch, $callfront);
				curl_multi_remove_handle($this->mch, $ch);
			}

			// Block for data in / output; error handling is done by curl_multi_exec
			if ($still_running && curl_multi_select($this->mch, $this->getCurlOption(CURLOPT_TIMEOUT)) === -1) {
				// Perform a usleep if a select returns -1. See: https://bugs.php.net/bug.php?id=61141
				usleep(250);
			}

		} while ($still_running);

		curl_multi_close($this->mch);

		$manager->close();

		return $manager;
	}

	public function __destruct ()
	{
		$this->mch = NULL;
	}
}