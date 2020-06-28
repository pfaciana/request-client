<?php


namespace RequestClient\Request;


class Headers
{
	protected $headers = [];

	protected $lineDelimiter = "\r\n";

	public function __construct ($headers = '')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($headers), false)) !== __AM_CONTINUE__) return $__am_res; 
		$this->setHeaders($headers);
	}

	public function setHeaders ($headers)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($headers), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (empty($headers)) {
			return FALSE;
		}

		if (is_string($headers)) {
			$headers = explode($this->lineDelimiter, trim($headers));
		}

		if (!is_array($headers)) {
			return FALSE;
		}

		foreach ($headers as $header) {
			$this->setHeaderFromStream(NULL, $header);
		}

		return $this->headers;
	}

	public function setHeaderWithoutKey ($header) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($header), false)) !== __AM_CONTINUE__) return $__am_res;  }

	public function setHeaderWithKey ($header)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($header), false)) !== __AM_CONTINUE__) return $__am_res; 
		list($key, $value) = explode(':', $header, 2);
		$this->headers[strtolower(trim($key))][] = trim($value);
	}

	public function setHeaderFromStream ($resource, $data)
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($resource, $data), false)) !== __AM_CONTINUE__) return $__am_res; 
		if ($header = trim($data)) {
			strpos($header, ':') ? $this->setHeaderWithKey($header) : $this->setHeaderWithoutKey($header);
		}

		return strlen($data);
	}

	protected function formatHeaders ($join = ';')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($join), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!is_string($join)) {
			return $this->headers;
		}

		$formattedHeaders = [];
		foreach ($this->headers as $key => $values) {
			$formattedHeaders[$key] = implode($join, $values);
		}

		return $formattedHeaders;
	}

	public function getRawHeaders ()
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
		$rawHeaders = '';

		foreach ($this->headers as $key => $values) {
			foreach ($values as $value) {
				$rawHeaders .= "{$key}: {$value}{$this->lineDelimiter}";
			}
		}

		return trim($rawHeaders);
	}

	public function getHeaders ($join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($join), false)) !== __AM_CONTINUE__) return $__am_res; 
		return $this->formatHeaders($join);
	}

	public function getHeader ($key, $join = '; ')
	{ if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($key, $join), false)) !== __AM_CONTINUE__) return $__am_res; 
		if (!array_key_exists($key, $this->headers)) {
			return NULL;
		}

		if (is_string($join)) {
			return implode($join, $this->headers[$key]);
		}

		return $this->headers[$key];
	}
}