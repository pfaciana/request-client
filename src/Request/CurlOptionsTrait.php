<?php


namespace RequestClient\Request;


trait CurlOptionsTrait
{
	protected $defaultCurlOptions = [
		CURLOPT_FILETIME       => 1, // get modification time (0)
		CURLOPT_AUTOREFERER    => 1, // automatically update the referer header (0)
		CURLOPT_CONNECTTIMEOUT => 300, // timeout for the connect phase (300)
		CURLOPT_TIMEOUT        => 300, // maximum time the request is allowed to take (0)
		CURLOPT_FAILONERROR    => 0, // request failure on HTTP response >= 400 (0)
		CURLOPT_CRLF           => 0, // enable CRLF conversion (0)

		CURLOPT_FOLLOWLOCATION => 1, // follow HTTP 3xx redirects (0)
		CURLOPT_MAXREDIRS      => 5, // maximum number of redirects allowed (-1)
		CURLOPT_HEADER         => 0, // pass headers to the data stream (0)
		/*
		CURLOPT_HTTPGET        => 1, // GET (1/0)
		CURLOPT_POST           => 0, // POST (0/1)
		CURLOPT_PUT            => 0, // PUT (0/1)
		CURLOPT_NOBODY         => 0, // HEAD (0/1)
		CURLOPT_CUSTOMREQUEST  => NULL, // DELETE (NULL/"DELETE")
		*/

		CURLOPT_HTTPHEADER => [
			"Connection: keep-alive",
			"Keep-Alive: 300",
			"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
			"Accept-Language: en-us,en;q=0.5",
			"Cache-Control: no-cache",
			"Pragma: no-cache",
		], // custom HTTP headers (NULL)

		CURLOPT_ENCODING       => '', // all available: ''; none: 'gzip;q=0,deflate;q=0' (NULL)
		CURLOPT_SSL_VERIFYHOST => 0, // verify the certificate's name against host (2)
		CURLOPT_SSL_VERIFYPEER => 0, // verify the peer's SSL certificate (1)

		CURLOPT_RETURNTRANSFER => 1, // return the transfer as a string, instead of outputting it directly (0)

		CURLINFO_HEADER_OUT => 1, // track the handle's request string
	];

	protected $curlOptions = [];

	public function setCurlOptions ($curlOptions = [], $merge = TRUE)
	{
		if (!$curlOptions) {
			return FALSE;
		}

		if (!is_array($curlOptions)) {
			return trigger_error("'options' must be an array.", E_USER_WARNING) && FALSE;
		}

		return $this->curlOptions = $merge ? $curlOptions + $this->curlOptions : $curlOptions;
	}

	public function setCurlOption ($curlOption, $curlValue)
	{
		$curlOptions = [];

		$curlOptions[$curlOption] = $curlValue;

		return $this->setCurlOptions($curlOptions);
	}

	public function getCurlOptions ($curlOptions = [])
	{
		if (!is_array($curlOptions)) {
			return trigger_error("'options' must be an array.", E_USER_WARNING) && FALSE;
		}

		return $curlOptions + $this->curlOptions + $this->defaultCurlOptions;
	}

	public function getCurlOption ($curlOption)
	{
		$curlOptions = $this->curlOptions + $this->defaultCurlOptions;

		return array_key_exists($curlOption, $curlOptions) ? $curlOptions[$curlOption] : NULL;
	}

	public function getCurlConstant ($code, $prefix = NULL)
	{
		foreach (get_defined_constants(TRUE)['curl'] as $name => $value) {
			if (!!$prefix && strpos($name, $prefix) !== 0) {
				continue;
			}
			if ($code === $value) {
				return $name;
			}
		}

		return 'UNKNOWN_CONSTANT';
	}
}

/*

Response
    [url] => http://cdn.photos.sparkplatform.com/cr/20190926145029219742000000-o.jpg // Last effective URL
    [http_code] => 200 // The last response code
    [content_type] => image/jpeg // Content-Type: of the requested document. NULL indicates server did not send valid Content-Type: header
    [filetime] => 1586482703 // Remote time of the retrieved document, with the CURLOPT_FILETIME enabled; if -1 is returned the time of the document is unknown

# Size
	[request_size] => 384 // Total size of issued requests, currently only for HTTP requests
	[size_upload] => 0 // Total number of bytes uploaded

	[header_size] => 506 // Total size of all headers received
	[size_download] => 1822871 // Total number of bytes downloaded

# Time
	[total_time] => 0.822906 // Total transaction time in seconds for last transfer

*/