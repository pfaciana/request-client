<?php


namespace RequestClient\Request;


trait StatusHelpersTrait
{
	// Comment to fix AspectMock bug

	public function getThrottle ()
	{
		return $this->throttle;
	}

	public function getThreads ()
	{
		return $this->threads;
	}

	public function getTotal ()
	{
		return $this->total;
	}

	public function getStarted ()
	{
		return $this->started;
	}

	public function getSuccess ()
	{
		return $this->success;
	}

	public function getFailed ()
	{
		return $this->failed;
	}

	public function getCompleted ()
	{
		return $this->completed;
	}

	public function getStartTime ($precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->getTime('startTime', $precision, $mode);
	}

	public function getEndTime ($precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->getTime('endTime', $precision, $mode);
	}

	public function getDuration ($precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->getTime('duration', $precision, $mode);
	}

	protected function getTime ($key, $precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->round($this->get($key), $precision, $mode);
	}

	protected function round ($number, $precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return is_null($number) || is_null($precision) ? $number : round($number, $precision, $mode);
	}

	public function getItemPerSecond ($precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->round($this->getCompleted() / ($this->getDuration() ?: 1), $precision, $mode);
	}

	public function getSecondsPerItem ($precision = NULL, $mode = PHP_ROUND_HALF_UP)
	{
		return $this->round($this->getDuration() / ($this->getCompleted() ?: 1), $precision, $mode);
	}

	protected function calculatePercent ($a, $b, $precision = 0, $raw = FALSE)
	{
		$precision = $raw ? $precision + 2 : $precision;

		$multiplier = pow(10, $precision);

		$percent = $a / $b * ($raw ? 1 : 100) * $multiplier;

		$percent = (is_null($precision) ? $percent : floor($percent)) / $multiplier;

		return $percent;
	}

	public function getPercent ($precision = NULL, $raw = FALSE)
	{
		return $this->calculatePercent($this->getCompleted(), $this->getTotal(), $precision, $raw);
	}
}