<?php


use RequestClient\Request\Cookie;
use RequestClient\Request\CookieJar;


class CookieJarTest extends \Codeception\Test\Unit
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
	public function testGetSet ()
	{
		$headers = [
			'set-cookie' => [
				'a=1;Expires=Wed, 1 Jan 2020 07:23:00 GMT;Domain=site-a.com',
				'b=2;Max-Age=3600;Domain=site-a.com',
			],
		];

		$cookieJar = new CookieJar($headers['set-cookie']);

		$this->assertEquals(2, $cookieJar->get('b')->getValue(), 'test cookie exists');
		$this->assertNull($cookieJar->get('a'), 'test cookie has expired');

		$cookieJar->set('c=3;Max-Age=3600;Domain=site-a.com;path=/dir');
		$this->assertEquals(3, $cookieJar->get('c', '/dir')->getValue(), 'test set cookie exists');

		$this->assertEquals(3, $cookieJar->get('c', '/dir', 'site-a.com')->getValue(), 'test set cookie by domain');
		$this->assertNull($cookieJar->get('c', '/dir', 'site-b.com'), 'test cookie for different domain');
		$this->assertNull($cookieJar->get('c', NULL, 'site-a.com'), 'test cookie for different path');

		$cookie = new Cookie('d=4;Max-Age=3600;Domain=subdomain.site-b.com');
		$cookieJar->set($cookie);
		$this->assertEquals(4, $cookieJar->get('d')->getValue(), 'test cookie object value exists');
	}

	public function testExpire ()
	{
		$headers = [
			'set-cookie' => [
				'a=1;Expires=Wed, 1 Jan 2020 07:23:00 GMT;Domain=site-a.com',
				'b=2;Max-Age=3600;Domain=site-a.com',
				'c=3;Max-Age=3600;Domain=site-a.com;path=/dir',
			],
		];

		$cookieJar = new CookieJar($headers['set-cookie']);

		$cookieJar->expire('c', '/dir', 'site-a.com');
		$this->assertNull($cookieJar->get('c', '/dir', 'site-a.com'), 'test expire');

		$cookieJar->expire('b');
		$this->assertNull($cookieJar->get('b'), 'test expire no domain');
	}

	public function testClear ()
	{
		$headers = [
			'set-cookie' => [
				'a=1;Expires=Wed, 1 Jan 2020 07:23:00 GMT;Domain=site-a.com',
				'b=2;Max-Age=3600;Domain=site-a.com',
				'c=3;Max-Age=3600;Domain=site-a.com;path=/dir',
			],
		];

		$cookieJar = new CookieJar($headers['set-cookie']);
		$cookieJar->clear();

		$this->assertNull($cookieJar->get('c', '/dir', 'site-a.com'), 'test expire');
		$this->assertNull($cookieJar->get('b'), 'test expire no domain');
	}

	public function testAll ()
	{
		$headers = [
			'set-cookie' => [
				'a=1;Expires=Wed, 1 Jan 2020 07:23:00 GMT;Domain=site-a.com',
				'b=2;Max-Age=3600;Domain=site-a.com',
				'c=3;Max-Age=3600;Domain=site-a.com;path=/dir',
				'd=4;Max-Age=3600;Domain=subdomain.site-b.com',
				'e=5;Max-Age=3600;Domain=site-a.com;Secure',
			],
		];

		$cookieJar = new CookieJar($headers['set-cookie']);
		$this->assertCount(4, $cookieJar->getAll(), 'count all active cookies');
		$this->assertCount(3, $cookieJar->getAll('https://www.site-a.com/dir'), 'count site with path active cookies');
		$this->assertCount(2, $cookieJar->getAll('https://www.site-a.com'), 'count site active cookies');
		$this->assertCount(1, $cookieJar->getAll('http://www.site-a.com'), 'count site with insecure cookies');
		$this->assertCount(1, $cookieJar->getAll('https://subdomain.site-b.com'), 'count subdomain site active cookies');
		$this->assertCount(0, $cookieJar->getAll('https://site-b.com'), 'parent site cookies');
		$this->assertCount(0, $cookieJar->getAll('https://subdomain2.site-b.com'), 'different subdomain cookies');
		$this->assertCount(0, $cookieJar->getAll('https://www.site-c.com'), 'random site cookies');
	}

	public function testGetHeaderCookie ()
	{
		$headers = [
			'set-cookie' => [
				'a=1;Expires=Wed, 1 Jan 2020 07:23:00 GMT;Domain=site-a.com',
				'b=2;Max-Age=3600;Domain=site-a.com',
				'c=3;Max-Age=3600;Domain=site-a.com;path=/dir',
				'd=4;Max-Age=3600;Domain=subdomain.site-b.com',
				'e=5;Max-Age=3600;Domain=site-a.com;Secure',
			],
		];

		$cookieJar = new CookieJar($headers['set-cookie']);

		$this->assertEquals('b=2; e=5; c=3; d=4', $cookieJar->getHeaderCookie(), 'all cookie header string');
		$this->assertEquals('b=2;e=5;c=3', $cookieJar->getHeaderCookie(';', 'https://site-a.com/dir'), 'all subdomain cookies header string');
		$this->assertEquals('Cookie: b=2; e=5', $cookieJar->getHeaderCookie('; ', 'https://site-a.com', TRUE), 'all parent domain header string');
		$this->assertEquals('Cookie: b=2', $cookieJar->getHeaderCookie(';', 'http://site-a.com', TRUE), 'all insecure parent domain header string');
	}
}