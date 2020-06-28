<?php

namespace RequestClient;

use RequestClient\Request\CurlOptionsTrait;
use RequestClient\Request\CurlErrorsTrait;

class MultiCurl
{
	use CurlOptionsTrait, CurlErrorsTrait;

	protected $mch;
	protected $pos = 0;
	protected $counter = 0;
	protected $urls = array();
	protected $total = 0;
	protected $active = array();
	protected $start;
	protected $end;
	protected $threads = 6;

	public function __construct ($urls = array(), $callback = NULL, $final_callback = NULL)
	{
		$this->run($urls, $callback, $final_callback);
	}

	public function __destruct ()
	{
		$this->mch = NULL;
	}

	protected function curl_init_the_queue ()
	{
		while (count($this->active) < $this->threads && $this->pos < count($this->urls)) {
			if (!$this->urls[$this->pos]) {
				$this->addErrors('URL Missing at Position: ' . $this->pos++);
				$this->counter++;
				continue;
			}
			if (!is_resource($ch = curl_init($this->urls[$this->pos]))) {
				$this->addErrors('Connection Failed: ' . $this->urls[$this->pos++]);
				$this->counter++;
				continue;
			}
			curl_setopt_array($ch, $this->getCurlOptions());
			curl_multi_add_handle($this->mch, $ch);
			$this->active[(string) $ch] = $this->pos++;
		}
	}

	public function run ($requests, $callback = NULL, $completed = NULL)
	{
		if (!$requests) {
			$this->addErrors('Requests URLs are missing.');
		}

		if (!$callback) {
			$this->addErrors('Missing callback to handle URL response.');
		}

		if (!$requests || !$callback) {
			if (is_callable($completed)) {
				$request_info = array(
					'urls'       => $requests,
					'started'    => FALSE,
					'completed'  => 0,
					'active'     => 0,
					'duration'   => 0,
					'start_time' => 0,
					'end_time'   => 0,
					'threads'    => $this->threads,
				);
				call_user_func($completed, $request_info, $this->errors);
			}

			return;
		}

		$this->start   = microtime(TRUE);
		$this->urls    = array_values((array) $requests);
		$this->total   = count($this->urls);
		$this->threads = min($this->threads, count($this->urls));

		$this->mch = curl_multi_init();
		curl_multi_setopt($this->mch, CURLMOPT_MAXCONNECTS, $this->threads);

		$this->curl_init_the_queue();

		do {
			while (($exec_code = curl_multi_exec($this->mch, $still_running)) == CURLM_CALL_MULTI_PERFORM)
				;

			if ($exec_code != CURLM_OK && $exec_code != CURLM_CALL_MULTI_PERFORM) {
				$this->addErrors($this->get_constant($exec_code, 'CURLM_'));
				$this->counter++;
				continue;
			}

			while ($handle_info = curl_multi_info_read($this->mch)) {

				$request_pos = $this->active[(string) $handle_info['handle']];
				$request     = $this->urls[$request_pos];

				if ($handle_info['result'] !== CURLE_OK) {
					$this->addErrors($this->get_constant($handle_info['result'], 'CURLE_') . ' for "' . $request . '"');
					$this->counter++;
					continue;
				}

				$response_info = curl_getinfo($handle_info['handle']);
				$response      = curl_multi_getcontent($handle_info['handle']);
				unset($this->active[(string) $handle_info['handle']]);

				if (is_callable($callback)) {
					$request_info = array(
						'url'        => $request,
						'url_pos'    => $request_pos,
						'urls'       => $this->urls,
						'total'      => $this->total,
						'started'    => $this->pos,
						'completed'  => ++$this->counter,
						'active'     => $this->active,
						'start_time' => $this->start,
						'threads'    => $this->threads,
						'headers'    => [],
						'content'    => '',
					);
					call_user_func($callback, $response, $response_info, $request_info, $handle_info, $this->errors);
				}

				// start a new request (it's important to do this before removing the old one)
				$this->curl_init_the_queue();

				curl_multi_remove_handle($this->mch, $handle_info['handle']);

			}

			// Block for data in / output; error handling is done by curl_multi_exec
			if ($still_running && curl_multi_select($this->mch, $this->getCurlOption(CURLOPT_TIMEOUT)) === -1) {
				// Perform a usleep if a select returns -1. See: https://bugs.php.net/bug.php?id=61141
				usleep(250);
			}

		} while ($still_running);

		curl_multi_close($this->mch);
		$this->end = microtime(TRUE);

		if (is_callable($completed)) {
			$request_info = array(
				'urls'       => $this->urls,
				'total'      => $this->total,
				'started'    => $this->pos,
				'completed'  => $this->counter,
				'active'     => $this->active,
				'duration'   => $this->end - $this->start,
				'start_time' => $this->start,
				'end_time'   => $this->end,
				'threads'    => $this->threads,
			);
			call_user_func($completed, $request_info, $this->errors);
		}
	}
}