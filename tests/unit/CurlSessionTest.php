<?php

use \AspectMock\Test as test;
use \RequestClient\CurlSession;

class CurlSessionTest extends \Codeception\Test\Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	public static function setUpBeforeClass (): void
	{
	}

	public static function tearDownAfterClass (): void
	{
	}

	protected function _before ()
	{
	}

	protected function _after ()
	{
		test::clean();
	}

	// tests
	public function testCurlSessionBasic ()
	{
		$domain  = 'some-domain.com';
		$path    = '/dir';
		$url     = 'https://' . $domain . $path;
		$destUrl = 'https://redirected-domain.com/page';

		$response = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquam at aut, culpa ea ex laborum nam nobis porro ullam unde. A, et expedita inventore minus nisi pariatur temporibus veritatis voluptates.';

		$getInfo = [
			'http_code'      => 200,
			'url'            => $destUrl,
			'content_type'   => 'text/html; charset=utf-8',
			'filetime'       => 1586482703,
			'request_size'   => 431,
			'size_upload'    => 0.0,
			'header_size'    => 318,
			'size_download'  => 31368.0,
			'total_time'     => 0.2584549,
			'local_ip'       => '192.168.1.192',
			'request_header' => "GET {$path} HTTP/2\r\nHost: {$domain}\r\n\r\n\r\n",
		];

		$uploadSize   = $getInfo['request_size'] + $getInfo['size_upload'];
		$downloadSize = $getInfo['header_size'] + $getInfo['size_download'];
		$size         = $uploadSize + $downloadSize;

		test::double('RequestClient\Request\ResponseHeaders', [
			'__construct' => function ($headers = '') use ($url, $domain, $path) {
				$headers = "HTTP/2 200\r\nset-cookie: cookie_one=value1; expires=Sat, 4-Jan-2020 20:34:33 GMT; Max-Age=31536000; path={$path}; domain=.{$domain}\r
set-cookie: cookie_two=value2; expires=Sat, 4-Jan-2020 20:34:33 GMT; path={$path}; domain=.{$domain}\r\n\r\n\r\n";

				$this->setHeaders($headers);
			},
		]);
		test::func('RequestClient', 'curl_exec', $response);
		test::func('RequestClient', 'curl_getinfo', $getInfo);

		$curl   = new CurlSession();
		$output = $curl->init($url)->exec(FALSE);

		$this->assertEquals($response, $output, 'request return the correct response');
		$this->assertEquals($getInfo['local_ip'], $curl->getInfo('local_ip'), 'get local_ip');
		$this->assertEquals($getInfo['content_type'], $curl->getContentType(), 'getContentType');
		$this->assertEquals($getInfo['filetime'], $curl->getFiletime(), 'getFiletime');
		$this->assertEquals($uploadSize, $curl->getUploadSize(), 'getUploadSize');
		$this->assertEquals($getInfo['size_upload'], $curl->getUploadSize(FALSE), 'getUploadSize no headers');
		$this->assertEquals($downloadSize, $curl->getDownloadSize(), 'getDownloadSize');
		$this->assertEquals($getInfo['size_download'], $curl->getDownloadSize(FALSE), 'getDownloadSize no headers');
		$this->assertEquals($size, $curl->getSize(), 'getSize');
		$this->assertEquals($getInfo['size_upload'] + $getInfo['size_download'], $curl->getSize(FALSE), 'getSize no headers');
		$this->assertEquals($getInfo['total_time'], $curl->getTime(), 'getTime');
		$this->assertEquals($getInfo['total_time'] * 1e6, $curl->getTime(TRUE), 'getTime in us');
		$this->assertNull($curl->getRequest(), 'getRequest');
		$this->assertCount(4, $curl->getRequestHeaders(), 'getRequestHeaders');
		$this->assertEquals('GET', $curl->getRequestHeader('action'), 'getRequestHeader');
		$this->assertCount(3, $curl->getResponseHeaders(), 'getResponseHeaders');
		$this->assertEquals('HTTP/2', $curl->getResponseHeader('protocol'), 'getResponseHeader');
		$this->assertEquals($response, $curl->getResponse(), 'getResponse');
		$this->assertEquals($getInfo['http_code'], $curl->getStatusCode(), 'getStatusCode');
		$this->assertEquals($getInfo['url'], $curl->getUrl(), 'getUrl');
		$this->assertEquals($url, $curl->getOrigUrl(), 'getOrigUrl');
		$this->assertCount(1, $curl->getCookies()->getAll(), 'count all non-expired cookies in jar');
		$this->assertCount(1, $curl->getCookies()->getAll($url), 'count cookies for the path - one should be expired');
		$this->assertCount(0, $curl->getCookies()->getAll('https://' . $domain), 'no cookies for the root domain');
		$this->assertEquals('value1', $curl->getCookie('cookie_one', $path, $domain)->getValue(), 'get cookie value');
		$this->assertEquals('value1', $curl->getCookie('cookie_one', $path)->getValue(), 'get cookie value for any domain');
		$this->assertTrue(is_null($curl->getPosition()), 'position is not defined for single curl request');
		$this->assertTrue(is_resource($curl->getHandle()), 'handle is a resource');
		$curl->close();
		$this->assertFalse(is_resource($curl->getHandle()), 'handle has been closed');
	}

	public function testCurlSessionCookies ()
	{
		$domain = 'some-domain.com';
		$path   = '/dir';
		$url    = 'https://' . $domain . $path;

		$getInfo = [
			'http_code'      => 200,
			'url'            => $url,
			'content_type'   => 'text/html; charset=utf-8',
			'filetime'       => 1586482703,
			'request_size'   => 431,
			'size_upload'    => 0.0,
			'header_size'    => 318,
			'size_download'  => 31368.0,
			'total_time'     => 0.2584549,
			'request_header' => "GET {$path} HTTP/2\r\nHost: {$domain}\r\n\r\n\r\n",
		];
		test::func('RequestClient', 'curl_getinfo', $getInfo);
		test::func('RequestClient', 'curl_exec', '');

		$cookieHeader = NULL;
		test::double('RequestClient\CurlSession', [
			'getCurlOptions' => function ($curlOptions = []) use (&$cookieHeader) {
				$cookieHeader = !empty($curlOptions[CURLOPT_COOKIE]) ? $curlOptions[CURLOPT_COOKIE] : NULL;

				return $curlOptions + $this->curlOptions + $this->defaultCurlOptions;
			},
		]);

		$curl = new CurlSession();

		// make the first call to set the cookies
		test::double('RequestClient\Request\ResponseHeaders', [
			'__construct' => function ($headers = '') use ($url, $domain, $path) {
				$headers = "HTTP/2 200\r\nset-cookie: cookie_one=value1; expires=Sat, 4-Jan-2020 20:34:33 GMT; Max-Age=31536000; path={$path}; domain=.{$domain}\r
set-cookie: cookie_two=value2; expires=Sat, 4-Jan-2020 20:34:33 GMT; path={$path}; domain=.{$domain}\r\n\r\n\r\n";

				$this->setHeaders($headers);
			},
		]);
		$curl->init($url)->exec();
		$this->assertNull($cookieHeader, 'make sure there are no cookies');

		// make the second call to send the cookies from the cookieJar
		test::double('RequestClient\Request\ResponseHeaders', [
			'__construct' => function ($headers = '') use ($url, $domain, $path) {
				$headers = "HTTP/2 200\r\nset-cookie: cookie_three=value3; expires=Sat, 4-Jan-2020 20:34:33 GMT; Max-Age=31536000; path={$path}; domain=.{$domain}\r\n\r\n\r\n";

				$this->setHeaders($headers);
			},
		]);
		$curl->init($url)->exec();
		$this->assertEquals('cookie_one=value1', $cookieHeader, 'make sure the initial cookie is set');

		// make the third call adding in the additional cookie from the second call
		$curl->init($url)->exec();
		$this->assertEquals('cookie_one=value1; cookie_three=value3', $cookieHeader, 'make sure the additional cookie is set');

		// clear the cookie jar
		$curl->getCookies()->clear();
		$curl->init($url)->exec();
		$this->assertNull($cookieHeader, 'make sure the cookies were cleared');

		// manually add an extra cookie
		$curl->getCookies()->set(new \RequestClient\Request\Cookie('cookie_four=value4', $url));
		$curl->init($url)->exec();
		$this->assertEquals('cookie_three=value3; cookie_four=value4', $cookieHeader, 'make sure the manual cookie is set');

		// override all the cookies request call
		$curl->init($url, ['curl' => [CURLOPT_COOKIE => 'cookie_five=value5']])->exec();
		$this->assertEquals('cookie_five=value5', $cookieHeader, 'make sure the cookie was overwritten');

		$curl->init($url)->exec();
		$this->assertEquals('cookie_three=value3; cookie_four=value4', $cookieHeader, 'make sure the previous cookies were preserved');
	}

	public function testCurlSessionPost ()
	{
		$domain      = 'some-domain.com';
		$path        = '/dir';
		$url         = 'https://' . $domain . $path . '?a=1&b=two#hash';
		$curlOptions = [];

		$getInfo = [
			'http_code'      => 200,
			'request_header' => "GET {$path} HTTP/2\r\nHost: {$domain}\r\n\r\n\r\n",
		];
		test::func('RequestClient', 'curl_getinfo', $getInfo);
		test::func('RequestClient', 'curl_exec', '');
		test::double('RequestClient\CurlSession', [
			'getCurlOptions' => function ($options = []) use (&$curlOptions) {
				return $curlOptions = $options;
			},
		]);
		test::double('RequestClient\Request\CurlBrowserOptions', ['isTorEnabled' => TRUE]);

		$options = [
			'json'        => ['key1' => 'value1', 'key2' => 'value2'],
			'query'       => ['b' => 2, 'c' => 3],
			'proxy'       => 'tor',
			'auth_bearer' => 'some_token',
			'curl'        => [],
		];

		$curl = new CurlSession();
		$curl->init($url, $options)->exec();

		$this->assertEquals(1, $curlOptions[CURLOPT_POST], '$curlOptions: CURLOPT_POST');
		$this->assertEquals(1, $curlOptions[CURLOPT_HTTPPROXYTUNNEL], '$curlOptions: CURLOPT_HTTPPROXYTUNNEL');
		$this->assertEquals('127.0.0.1:9050', $curlOptions[CURLOPT_PROXY], '$curlOptions: CURLOPT_PROXY');
		$this->assertEquals(CURLPROXY_SOCKS5_HOSTNAME, $curlOptions[CURLOPT_PROXYTYPE], '$curlOptions: CURLOPT_PROXYTYPE');
		$this->assertTrue(in_array('Authorization: Bearer some_token', $curlOptions[CURLOPT_HTTPHEADER]), '$curlOptions: Authorization');
		$this->assertTrue(in_array('Content-Type: application/json', $curlOptions[CURLOPT_HTTPHEADER]), '$curlOptions: Content-Type');

		$this->assertEquals('https://some-domain.com/dir?a=1&b=two#hash', $curl->getOrigUrl(), 'check original url');
		$this->assertEquals('https://some-domain.com/dir?a=1&b=2&c=3#hash', $curl->getUrl(), 'check url used for the request');
		$this->assertEquals('{"key1":"value1","key2":"value2"}', $curl->getRequest(), 'json data is serialized and sent as the request');
	}

	public function testSuccessFailedRequest ()
	{
		test::func('RequestClient', 'curl_getinfo', ['http_code' => 200, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);
		test::func('RequestClient', 'curl_exec', 'some response');

		$domain = 'some-domain.com';
		$path   = '/dir';
		$url    = 'https://' . $domain . $path;

		$curl = new CurlSession();
		$curl->init($url);

		$this->assertFalse($curl->requestSent(), 'request has not started');
		$this->assertFalse($curl->requestSucceeded(), 'request has not succeeded because it hasnt started');
		$this->assertFalse($curl->requestFailed(), 'request has not failed because it hasnt started');

		$curl->exec();

		$this->assertTrue($curl->requestSent(), 'request has started');
		$this->assertTrue($curl->requestSucceeded(), '200 request was successful');
		$this->assertFalse($curl->requestFailed(), '200 request did not fail');

		test::func('RequestClient', 'curl_getinfo', ['http_code' => 404, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);
		test::func('RequestClient', 'curl_exec', '');

		$curl = new CurlSession();
		$curl->init($url)->exec();

		$this->assertFalse($curl->requestSucceeded(), '404 request was not successful');
		$this->assertTrue($curl->requestFailed(), '404 request failed');

		test::func('RequestClient', 'curl_getinfo', ['http_code' => 0, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);
		test::func('RequestClient', 'curl_exec', '');

		$curl = new CurlSession();
		$curl->init($url)->exec();

		$this->assertFalse($curl->requestSucceeded(), '0 request was not successful');
		$this->assertTrue($curl->requestFailed(), '0 request failed');
	}

	public function testMinify ()
	{
		$input  = ' <body class="body">

<div class="main-wrap">
    <main>
        <textarea>
            Some text
            with newlines
            and some spaces
        </textarea>

        <div class="test">
            <p>This text</p>
            <p>should not</p>
            <p>wrap on multiple lines</p>
        </div>
    </main>
</div>
<script>
    console.log(\'Script tags are not minified\');
    console.log(\'This is inside a script tag\');
</script></body>';
		$output = '<body class="body"><div class="main-wrap"> <main> <textarea>
            Some text
            with newlines
            and some spaces
        </textarea> <div class="test"> <p>This text</p> <p>should not</p> <p>wrap on multiple lines</p> </div> </main> </div> <script>
    console.log(\'Script tags are not minified\');
    console.log(\'This is inside a script tag\');
</script></body>';

		test::func('RequestClient', 'curl_getinfo', ['http_code' => 200, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);
		test::func('RequestClient', 'curl_exec', $input);

		$domain = 'some-domain.com';
		$path   = '/dir';
		$url    = 'https://' . $domain . $path;

		$curl = new CurlSession();
		$curl->init($url);
		$curl->exec();

		$this->assertEquals($output, $curl->minify(), 'minified response');
		$this->assertEquals($input, $curl->getResponse(), 'response unchanged');
		$curl->minifyResponse();
		$this->assertEquals($output, $curl->getResponse(), 'response minified');
		$this->assertEquals(1, $curl->minify(1), 'return invalid argument');

		$curl->setQp('<b>change the html</b>');
		$this->assertCount(0, $curl->query(NULL, 'textarea'), 'there should be no textarea after changing $qp');
		$this->assertCount(1, $curl->query(NULL, 'textarea', ['reset' => TRUE]), 'reset back to the original response');


		$curl = new CurlSession();
		$curl->init($url, ['minify' => TRUE]);
		$curl->exec();
		$this->assertEquals($output, $curl->getResponse(), 'response minified via options');


		$input  = '       <div class="a">
            <p>b</p>
        </div>      ';
		$output = '<div class="a"> <p>b</p> </div> ';

		$this->assertEquals($output, $curl->minify($input), 'minified input');


		$json = [
			'a' => TRUE,
			'b' => 'two',
			'c' => [1, 2, 3],
		];

		$json_alt = [
			'x' => FALSE,
			'y' => 'five',
			'z' => [-1, -2, -3],
		];

		test::func('RequestClient', 'curl_exec', json_encode($json));

		$curl = new CurlSession();
		$curl->init($url);
		$curl->exec();

		$this->assertEquals('two', $curl->queryJson('b'), 'initial json query');
		$this->assertEquals(-1, $curl->queryJson($json_alt, 'z[0]'), 'query alt json, but dont save');
		$this->assertEquals(2, $curl->queryJson('c[1]'), 'check alt query was not saved');
		$this->assertEquals(-2, $curl->queryJson($json_alt, 'z[1]', ['reset' => TRUE]), 'query alt and save');
		$this->assertEquals(-3, $curl->queryJson($json_alt, 'z[2]'), 'check alt query was saved');

		$json = [
			'foo' => 'bar',
			'baz' => 'boom',
			'cow' => 'milk',
			'php' => 'hypertext processor',
		];

		test::func('RequestClient', 'curl_exec', http_build_query($json));

		$callback = function ($json, $options, $client) {
			parse_str($json, $output);

			return json_encode(['query' => $output]);
		};

		$curl = new CurlSession();
		$curl->init($url);
		$curl->exec();

		$curl->filterJson($callback);

		$this->assertEquals('bar', $curl->jPath('query.foo'), 'check that json ran through the callback');

	}
}