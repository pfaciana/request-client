<?php

namespace RequestClient;

use RequestClient\Request\CurlOptionsTrait;

abstract class CurlBrowser extends Browser
{
	use CurlOptionsTrait;

	protected $getInfo;

	public function getInfo ($key = NULL)
	{
		return $key ? $this->getInfo[$key] : $this->getInfo;
	}

	public function getContentType ()
	{
		return $this->getInfo['content_type'];
	}

	public function getFiletime ()
	{
		return $this->getInfo['filetime'] ?? NULL;
	}

	public function getUploadSize ($includeHeader = TRUE)
	{
		return $includeHeader ? $this->getInfo['request_size'] + $this->getInfo['size_upload'] : $this->getInfo['size_upload'];
	}

	public function getDownloadSize ($includeHeader = TRUE)
	{
		return $includeHeader ? $this->getInfo['header_size'] + $this->getInfo['size_download'] : $this->getInfo['size_download'];
	}

	public function getSize ($includeHeader = TRUE)
	{
		if (!$includeHeader) {
			return $this->getInfo['size_upload'] + $this->getInfo['size_download'];
		}

		return $this->getInfo['request_size'] + $this->getInfo['size_upload'] + $this->getInfo['header_size'] + $this->getInfo['size_download'];
	}

	public function getTime ($microtime = FALSE)
	{
		if ($microtime) {
			return !empty($this->getInfo['total_time_us']) ? $this->getInfo['total_time_us'] : $this->getInfo['total_time'] * 1000000;
		}

		return !empty($this->getInfo['total_time']) ? $this->getInfo['total_time'] : $this->getInfo['total_time_us'] / 1000000;
	}
}