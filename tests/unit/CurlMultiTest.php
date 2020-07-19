<?php

use \AspectMock\Test as test;
use \RequestClient\CurlMulti;

class CurlMultiTest extends \Codeception\Test\Unit
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
		test::clean();
	}

	// tests
	public function testInvalid ()
	{
		$curl    = new CurlMulti(['threads' => 2]);
		$manager = $curl->run([]);

		$this->assertFalse($manager->isValid(), 'no urls are invalid');
		$this->assertTrue($manager->hasErrors(), 'there are errors');
		$this->assertTrue($manager->hasErrors(-1), 'there are errors at index -1');
		$this->assertEquals(['Requests URLs are missing'], $manager->getErrors(-1), 'missing urls error at index -1');
	}

	public function testCurlErrors ()
	{
		test::func('RequestClient', 'curl_multi_exec', CURLM_INTERNAL_ERROR);

		$urls = [];
		for ($i = 0; $i < 3; $i++) {
			$urls[] = uniqid('https://some-site-') . '-url.com';
		}

		$curl    = new CurlMulti(['threads' => 2]);
		$manager = $curl->run($urls);

		$errors = [-1 => ['CURLM_INTERNAL_ERROR']];

		$this->assertTrue($manager->hasErrors(), 'there are errors');
		$this->assertEquals($errors, $manager->getErrors(), 'no request was made due to CURLM_INTERNAL_ERROR');

		$this->assertEquals(2, $manager->get('started'), 'get started count');
		$this->assertEquals(0, $manager->get('success'), 'get success count');
		$this->assertEquals(0, $manager->get('failed'), 'get failed count');
		$this->assertEquals(0, $manager->get('completed'), 'get completed count');
	}

	public function testBasicMulti ()
	{
		test::func('RequestClient', 'curl_multi_getcontent', '');
		test::func('RequestClient', 'curl_getinfo', ['http_code' => 0, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);

		$urls = [];
		for ($i = 0; $i < 3; $i++) {
			$urls[] = uniqid('https://some-site-') . '-url.com';
		}

		$curl    = new CurlMulti(['threads' => 2]);
		$manager = $curl->run($urls);

		$errors = [['CURLE_COULDNT_RESOLVE_HOST'], ['CURLE_COULDNT_RESOLVE_HOST'], ['CURLE_COULDNT_RESOLVE_HOST'],];

		$this->assertTrue($manager->hasErrors(), 'there are errors');
		$this->assertEquals($errors, $manager->getErrors(), 'all requests are CURLE_COULDNT_RESOLVE_HOST error');

		$this->assertEquals(3, $manager->get('started'), 'get started count');
		$this->assertEquals(0, $manager->get('success'), 'get success count');
		$this->assertEquals(3, $manager->get('failed'), 'get failed count');
		$this->assertEquals(3, $manager->get('completed'), 'get completed count');
	}
}