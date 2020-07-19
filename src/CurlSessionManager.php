<?php


namespace RequestClient;

use RequestClient\Request\CurlOptionsTrait;
use RequestClient\Request\StatusHelpersTrait;

class CurlSessionManager
{
	use CurlOptionsTrait, StatusHelpersTrait;

	protected $reservedProperties = ['total', 'urls', 'sessions', 'errors', 'started', 'success', 'failed', 'completed', 'startTime', 'endTime', 'duration'];

	protected $throttle = 0; // microseconds to delay each request
	protected $threads = 6; // number of threads
	protected $total = 0; // count of url requests

	protected $urls = []; // all urls requested
	protected $sessions = []; //array of active sessions
	protected $errors = []; // all errors that came up during the series

	protected $started = FALSE; // number of requests started
	protected $success = 0; // number of successful requests
	protected $failed = 0; // number of failed requests
	protected $completed = 0; // number of completed requests

	protected $startTime; // start timestamp
	protected $endTime; // end timestamp
	protected $duration; // time difference from start to end

	protected function reset ($init = FALSE)
	{
		if ($this->started !== FALSE) {
			return trigger_error("You cannot init a new series before the previous one has finished.", E_USER_WARNING) && FALSE;
		}

		$this->threads = min((int) $this->threads, $this->urls->count());
		$this->total   = $this->urls->count();

		$this->sessions = [];
		$this->errors   = [];

		$this->started   = $init ? 0 : FALSE;
		$this->success   = 0;
		$this->failed    = 0;
		$this->completed = 0;

		$this->startTime = $init ? microtime(TRUE) : NULL;
		$this->endTime   = $init ? 0 : NULL;
		$this->duration  = $init ? 0 : NULL;

		return TRUE;
	}

	protected function updateTimes ()
	{
		$this->endTime  = microtime(TRUE);
		$this->duration = $this->endTime - $this->startTime;
	}

	public function close ()
	{
		$this->updateTimes();

		return $this;
	}

	protected function throttle ()
	{
		static $lastThrottle;

		if (!empty($this->throttle)) {
			$sinceLastCall = !empty($lastThrottle) ? microtime(TRUE) - $lastThrottle : 0;
			if (($diffTime = $this->throttle - $sinceLastCall) > 0) {
				usleep($diffTime * 1e6);
			}
		}

		$lastThrottle = microtime(TRUE);
	}

	public function init ()
	{
		return $this->reset(TRUE);
	}

	public function __construct ($urls, $config = [], $init = TRUE)
	{
		$this->urls = new \ArrayIterator((array) $urls);
		!empty($config) && $this->set($config);
		$init && $this->init();
	}

	public function set ($property, $value = NULL)
	{
		$config = is_array($property) ? $property : [$property => $value];

		foreach ($config as $property => $value) {
			if (in_array($property, $this->reservedProperties)) {
				return trigger_error("The '{$property}' property name is reserved.", E_USER_WARNING) && FALSE;
			}

			$this->{$property} = $value;
		}

		return TRUE;
	}

	public function get ($property, $default = NULL)
	{
		return property_exists($this, $property) ? $this->{$property} : $default;
	}

	public function isValid ()
	{
		return $this->urls->valid();
	}

	protected function getPosition ($pos = NULL)
	{
		if (is_null($pos)) {
			return $this->urls->key();
		}

		if (is_resource($pos)) {
			return $this->sessions[(string) $pos]->getPosition();
		}

		return $pos;
	}

	public function hasErrors ($pos = NULL)
	{
		return !empty($this->getErrors($pos));
	}

	public function getErrors ($pos = NULL)
	{
		if (is_null($pos)) {
			return $this->errors;
		}

		return $this->errors[$this->getPosition($pos)] ?? FALSE;
	}

	public function addError ($message, $pos = NULL)
	{
		$pos = $this->getPosition($pos);

		$this->errors[$pos] = array_merge($this->errors[$pos] ?? [], (array) $message);

		return $this;
	}

	protected function addSession ($ch, $session)
	{
		$this->sessions[(string) $ch] = $session;
	}

	protected function getSession ($ch, $default = FALSE)
	{
		$id = (string) $ch;

		return array_key_exists($id, $this->sessions) ? $this->sessions[$id] : $default;
	}

	protected function removeSession ($ch)
	{
		unset($this->sessions[(string) $ch]);
	}

	protected function sessionSuccess ()
	{
		$this->success++;
		$this->sessionCompleted();
	}

	protected function sessionFailed ($message, $pos = NULL)
	{
		$this->failed++;
		$this->addError($message, $pos);
		$this->sessionCompleted();
	}

	protected function sessionCompleted ()
	{
		$this->completed++;
		$this->updateTimes();
	}

	public function groupFailed ($message)
	{
		$this->addError($message, -1);
	}

	protected function hasOpenThreads ()
	{
		return count($this->sessions) < $this->threads;
	}

	protected function isLast ()
	{
		$pos = $this->urls->key();

		return is_null($pos) ?: $pos >= $this->urls->count() - 1;
	}

	protected function isNextReady ()
	{
		return $this->hasOpenThreads() && !$this->isLast();
	}

	public function addSessionsToGroup ($mch, $callback = NULL)
	{
		while ($this->isNextReady()) {
			if ($this->started > 0) {
				$this->urls->next();
				$this->throttle();
			}

			$this->started++;

			if (empty($url = $this->urls->current())) {
				$this->sessionFailed('URL Missing');
				continue;
			}

			$session = new CurlSession();

			$session->init($url, $this->urls->key());

			if (!is_resource($ch = $session->getHandle())) {
				$this->sessionFailed($this->getCurlConstant($ch, 'CURLE_'));
				continue;
			}

			$options = ['curl' => $this->get('curl', []) + [CURLOPT_PRIVATE => $this->urls->key()]];

			if (is_callable($callback)) {
				$options = call_user_func($callback, $options, $url, $session, $this);
			}

			$session->setOptions($options);

			curl_multi_add_handle($mch, $ch);

			$this->addSession($ch, $session);
		}
	}

	public function processSession ($ch, $callback = NULL, $errorMessage = NULL)
	{
		if (empty($session = $this->getSession($ch))) {
			return trigger_error("Session id does not exist.", E_USER_WARNING) && FALSE;
		}

		$response = $session->exec(FALSE);

		is_null($errorMessage) ? $this->sessionSuccess() : $this->sessionFailed($errorMessage, $ch);

		if (is_callable($callback)) {
			call_user_func($callback, $response, $session, $this);
		}

		$this->removeSession($ch);
	}
}