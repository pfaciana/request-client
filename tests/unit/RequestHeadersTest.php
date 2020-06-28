<?php

use RequestClient\Request\RequestHeaders;

class RequestHeadersTest extends \Codeception\Test\Unit
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
		$request = new RequestHeaders();

		$origHeaders = "GET /some/path HTTP/2\r
Host: some-domain.com\r
user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_1) AppleWebKit/537.35 (KHTML, like Gecko) Chrome/27.0.1253.110 Safari/537.35\r
accept: */*\r
accept-encoding: deflate, gzip\r
connection: keep-alive\r
cookie: a=1; b=2;\r
keep-alive: 300\r
accept-charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r
accept-language: en-us,en;q=0.5\r
cache-control: no-cache\r
pragma: no-cache\r
\r
\r\n";

		$request->setHeaders($origHeaders);

		$this->assertEquals('GET', $request->getHeader('action'), 'protocol');
		$this->assertEquals('/some/path', $request->getHeader('location'), 'location');
		$this->assertEquals('HTTP/2', $request->getHeader('protocol'), 'status');
		$this->assertEquals('some-domain.com', $request->getHeader('host'), 'host');
	}
}