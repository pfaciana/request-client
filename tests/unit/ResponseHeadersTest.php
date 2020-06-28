<?php

use RequestClient\Request\ResponseHeaders;

class ResponseHeadersTest extends \Codeception\Test\Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before ()
	{
	}

	protected function _after ()
	{
	}

	// tests
	public function testSetHeaderWithoutKey ()
	{
		$response = new ResponseHeaders();

		$origHeaders = "HTTP/2 200\r
date: Wed, 1 Jan 2020 01:23:45 GMT\r
server: Apache\r
last-modified: Tue, 1 Jan 2019 12:54:32 GMT\r
accept-ranges: bytes\r
cache-control: max-age=31536000\r
set-cookie: cookie_one=value1; expires=Sat, 4-Jan-2020 20:34:33 GMT; Max-Age=31536000; path=/; domain=.some-domain.com\r
set-cookie: cookie_two=value2; expires=Sat, 4-Jan-2020 20:34:33 GMT; Max-Age=31536000; path=/; domain=.some-domain.com\r
expires: Wed, 1 Jan 2021 01:23:45 GMT\r
vary: Accept-Encoding\r
content-encoding: gzip\r
content-length: 31368\r
content-type: text/css; charset=utf-8\r
\r
\r\n";

		$response->setHeaders($origHeaders);

		$this->assertEquals('HTTP/2', $response->getHeader('protocol'), 'protocol');
		$this->assertEquals('200', $response->getHeader('status'), 'status');
		$this->assertEquals('Apache', $response->getHeader('server'), 'server');
		$this->assertCount(2, $response->getHeader('set-cookie', FALSE), 'host');
	}
}