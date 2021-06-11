<?php

use \AspectMock\Test as test;
use RequestClient\Request\CurlBrowserOptions;

class OptionsTest extends \Codeception\Test\Unit
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
	public function testSetAuth ()
	{
		$browserOptions = ['auth_basic' => 'some_password'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertTrue(in_array('Authorization: Basic ' . base64_encode('some_password'), $curl[CURLOPT_HTTPHEADER]), 'Authorization Basic');


		$options->set('auth_bearer', 'some_token');
		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertTrue(in_array('Authorization: Bearer some_token', $curl[CURLOPT_HTTPHEADER]), 'Authorization Bearer');
		$this->assertFalse(in_array('Authorization: Basic ' . base64_encode('some_password'), $curl[CURLOPT_HTTPHEADER]), 'Removed Authorization Basic');
	}

	public function testSetMethod ()
	{
		$browserOptions = ['action' => 'Delete'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals('DELETE', $curl[CURLOPT_CUSTOMREQUEST], 'Method: DELETE');


		$options->set('method', 'pUT');
		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertFalse(array_key_exists(CURLOPT_CUSTOMREQUEST, $curl), 'Method: DELETE has been removed');
		$this->assertEquals(1, $curl[CURLOPT_PUT], 'Method: PUT');
	}

	public function testSetQuery ()
	{
		$browserOptions = ['url' => 'https://example-url.com/?a=1&b=two#hash', 'query' => 'b=2&c=3'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['url', 'curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$url = $normalizedBrowserOptions['url'];
		$this->assertEquals('https://example-url.com/?a=1&b=2&c=3#hash', $url, 'url matches');


		$options->set('query', ['c' => 'three', 'd' => 4]);
		$normalizedBrowserOptions = $options->getAll();

		$url = $normalizedBrowserOptions['url'];
		$this->assertEquals('https://example-url.com/?a=1&b=2&c=three&d=4#hash', $url, 'url matches after set');
	}

	public function testSetBody ()
	{
		$browserOptions = ['body' => ['a' => 1, 'b' => '2']];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_POST], 'Method: POST');
		$this->assertEquals(['a' => 1, 'b' => '2'], $curl[CURLOPT_POSTFIELDS], 'Post Fields');
		$this->assertFalse(in_array('Content-Type: application/x-www-form-urlencoded', $curl[CURLOPT_HTTPHEADER]), 'application/x-www-form-urlencoded');
		$this->assertFalse(in_array('Content-Type: application/json', $curl[CURLOPT_HTTPHEADER]), 'application/json');


		$options->set('form', ['c' => 3, 'd' => '4']);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as form');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_POST], 'Method: still POST ');
		$this->assertEquals('c=3&d=4', $curl[CURLOPT_POSTFIELDS], 'Post Fields');
		$this->assertTrue(in_array('Content-Type: application/x-www-form-urlencoded', $curl[CURLOPT_HTTPHEADER]), 'application/x-www-form-urlencoded');
		$this->assertFalse(in_array('Content-Type: application/json', $curl[CURLOPT_HTTPHEADER]), 'application/json');


		$options->set('json', ['e' => 5, 'f' => '6']);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as json');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_POST], 'Method: still POST ');
		$this->assertEquals('{"e":5,"f":"6"}', $curl[CURLOPT_POSTFIELDS], 'Post Fields');
		$this->assertFalse(in_array('Content-Type: application/x-www-form-urlencoded', $curl[CURLOPT_HTTPHEADER]), 'application/x-www-form-urlencoded');
		$this->assertTrue(in_array('Content-Type: application/json', $curl[CURLOPT_HTTPHEADER]), 'application/json');


		$browserOptions = ['form' => 'cc=33&dd=44', 'json' => '{"ee":55,"ff":"66"}'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as multiple');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_POST], 'Method: still POST ');
		$this->assertEquals('cc=33&dd=44', $curl[CURLOPT_POSTFIELDS], 'Post Fields');
		$this->assertTrue(in_array('Content-Type: application/x-www-form-urlencoded', $curl[CURLOPT_HTTPHEADER]), 'application/x-www-form-urlencoded');
		$this->assertFalse(in_array('Content-Type: application/json', $curl[CURLOPT_HTTPHEADER]), 'application/json');


		$browserOptions = ['json' => '{"ee":55,"ff":"66"}'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as multiple');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_POST], 'Method: still POST ');
		$this->assertEquals('{"ee":55,"ff":"66"}', $curl[CURLOPT_POSTFIELDS], 'Post Fields');
		$this->assertFalse(in_array('Content-Type: application/x-www-form-urlencoded', $curl[CURLOPT_HTTPHEADER]), 'application/x-www-form-urlencoded');
		$this->assertTrue(in_array('Content-Type: application/json', $curl[CURLOPT_HTTPHEADER]), 'application/json');


		$browserOptions = ['action' => 'Delete', 'body' => ['a' => 1, 'b' => '2']];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals('DELETE', $curl[CURLOPT_CUSTOMREQUEST], 'Method: DELETE');
	}

	public function testSetHeaders ()
	{
		$browserOptions = ['headers' => ['a' => 1, 'b' => '2', 'some-key: Some Value String']];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertTrue(in_array('a: 1', $curl[CURLOPT_HTTPHEADER]), 'array header');
		$this->assertTrue(in_array('some-key: Some Value String', $curl[CURLOPT_HTTPHEADER]), 'string header');


		$browserOptions = ['headers' => 'some-key-2: Some Other String'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertTrue(in_array('some-key-2: Some Other String', $curl[CURLOPT_HTTPHEADER]), 'single string header input');
	}

	public function testSetProxy ()
	{
		test::double('RequestClient\Request\CurlBrowserOptions', ['isTorEnabled' => FALSE]);

		$browserOptions = ['proxy' => 'tor'];

		$options = new CurlBrowserOptions($browserOptions);

		$normalizedBrowserOptions = $options->getAll();
		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertFalse(array_key_exists(CURLOPT_PROXY, $curl), 'CURLOPT_PROXY should not exist');


		test::double('RequestClient\Request\CurlBrowserOptions', ['isTorEnabled' => TRUE]);


		$options->set('proxy', 'tor');

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as form');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_HTTPPROXYTUNNEL], '$curlOptions: CURLOPT_HTTPPROXYTUNNEL');
		$this->assertEquals('127.0.0.1:9050', $curl[CURLOPT_PROXY], '$curlOptions: CURLOPT_PROXY');
		$this->assertEquals(CURLPROXY_SOCKS5_HOSTNAME, $curl[CURLOPT_PROXYTYPE], '$curlOptions: CURLOPT_PROXYTYPE');


		$options->set('proxy', ['type' => 'tor', 'host' => 'https://localhost', 'port' => '9000']);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as form');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_HTTPPROXYTUNNEL], '$curlOptions: CURLOPT_HTTPPROXYTUNNEL');
		$this->assertEquals('https://localhost:9000', $curl[CURLOPT_PROXY], '$curlOptions: CURLOPT_PROXY');
		$this->assertEquals(CURLPROXY_SOCKS5_HOSTNAME, $curl[CURLOPT_PROXYTYPE], '$curlOptions: CURLOPT_PROXYTYPE');


		$options->set('proxy', ['type' => 'apify', 'password' => 'password123']);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as form');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_HTTPPROXYTUNNEL], '$curlOptions: CURLOPT_HTTPPROXYTUNNEL');
		$this->assertEquals('http://proxy.apify.com:8000', $curl[CURLOPT_PROXY], '$curlOptions: CURLOPT_PROXY');
		$this->assertEquals('auto:password123', $curl[CURLOPT_PROXYUSERPWD], '$curlOptions: CURLOPT_PROXYTYPE');
		$this->assertFalse(array_key_exists(CURLOPT_PROXYTYPE, $curl), 'CURLOPT_PROXYUSERPWD should not exist');


		$options->set('proxy', ['type' => 'apify', 'host' => 'http://localhost', 'port' => '9000', 'username' => 'user', 'password' => 'password345']);

		$normalizedBrowserOptions = $options->getAll();

		$this->assertEquals(['curl'], array_keys($normalizedBrowserOptions), 'match keys after set as form');
		$curl = $normalizedBrowserOptions['curl'];
		$this->assertEquals(1, $curl[CURLOPT_HTTPPROXYTUNNEL], '$curlOptions: CURLOPT_HTTPPROXYTUNNEL');
		$this->assertEquals('http://localhost:9000', $curl[CURLOPT_PROXY], '$curlOptions: CURLOPT_PROXY');
		$this->assertEquals('user:password345', $curl[CURLOPT_PROXYUSERPWD], '$curlOptions: CURLOPT_PROXYTYPE');
		$this->assertFalse(array_key_exists(CURLOPT_PROXYTYPE, $curl), 'CURLOPT_PROXYUSERPWD should not exist');
	}
}