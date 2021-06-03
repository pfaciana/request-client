<?php


use RequestClient\Request\Cookie;


class CookieTest extends \Codeception\Test\Unit
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
	public function testGetCookie ()
	{
		$cookieStr = 'cookieKey=cookieValue;Expires=Mon, 4 Jan 2021 07:23:00 GMT;Max-Age=31536000;Domain=some-site.com;Path=/docs;SameSite=Strict;Secure;HttpOnly';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals('cookieKey=cookieValue', $cookie->getCookie(), 'new cookie from string');

		$cookie = new Cookie('');
		$this->assertNull($cookie->getCookie(), 'new cookie from empty string');

		$cookieArr = [
			'cookieKey' => 'cookieValue',
			'Expires'   => 'Mon, 4 Jan 2021 07:23:00 GMT',
			'Max-Age'   => 31536000,
			'Domain'    => 'some-site.com',
			'Path'      => '/docs',
			'SameSite'  => 'Strict',
			'Secure'    => TRUE,
			'HttpOnly'  => TRUE,
		];
		$cookie    = new Cookie($cookieArr);
		$this->assertEquals('cookieKey=cookieValue', $cookie->getCookie(), 'new cookie from array');

		$cookie = new Cookie([]);
		$this->assertNull($cookie->getCookie(), 'new cookie from empty array');
	}

	public function testGetParam ()
	{
		$cookieStr = 'cookieKey=cookieValue;Expires=Mon, 04 Jan 2021 07:23:00 GMT;Domain=some-site.com;Path=/docs;SameSite=Strict;Secure;HttpOnly';
		$cookie    = new Cookie($cookieStr);

		$this->assertEquals('Mon, 04 Jan 2021 07:23:00 GMT', $cookie->getParam('expires'), 'get expires');
		$this->assertEquals(1609744980, $cookie->getParam('expires', TRUE), 'get expires');
		$this->assertEquals('some-site.com', $cookie->getParam('Domain'), 'get Domain');
		$this->assertTrue($cookie->getParam('Secure'), 'get Secure');
		$this->assertTrue($cookie->getParam('HttpOnly'), 'get HttpOnly');

		$this->assertNull($cookie->getParam('DoesNotExist'), 'get undefined key');

		$cookie = new Cookie('cookieKey=cookieValue;Secure', 'https://some-url.com');
		$this->assertTrue($cookie->getParam('Secure'), 'get Secure with secure url');

		$cookie = new Cookie('cookieKey=cookieValue;Secure', 'http://some-url.com');
		$this->assertFalse($cookie->getParam('Secure'), 'get Secure with insecure url');

		$cookie = new Cookie('cookieKey=cookieValue;Domain=some-site.com', 'http://subdomain.some-url.com');
		$this->assertEquals('some-site.com', $cookie->getParam('domain'), 'get set domain');

		$cookie = new Cookie('cookieKey=cookieValue', 'http://subdomain.some-url.com');
		$this->assertEquals('subdomain.some-url.com', $cookie->getParam('domain'), 'get domain from url');
		$this->assertEquals('/', $cookie->getParam('path'), 'get default path');

		$cookie = new Cookie('cookieKey=cookieValue');
		$this->assertNull($cookie->getParam('domain'), 'get empty domain');

		$cookieArr = [
			'cookieKey' => 'cookieValue',
			'HttpOnly'  => NULL,
		];
		$cookie    = new Cookie($cookieArr);
		$this->assertNull($cookie->getParam('HttpOnly'), 'get null set HttpOnly');
	}

	public function testSetParam ()
	{
		$cookie = new Cookie();

		$this->assertEquals(1609744980, $cookie->setParam('expires', 'Mon, 4 Jan 2021 07:23:00 GMT'), 'set expires');
		$this->assertEquals(1609744980, $cookie->getParam('Expires', TRUE), 'check expires');

		$this->assertEquals(60, $cookie->setParam('Max-Age', '60'), 'set max-age');
		$this->assertEquals(60, $cookie->getParam('max-age'), 'check max-age');
		$this->assertNotEquals(1609744980, $cookie->getParam('Expires', TRUE), 'check that expires has changed');
		$this->assertEquals(1609744980, $cookie->setParam('expires', 'Mon, 4 Jan 2021 07:23:00 GMT'), 'try saving expires without overriding');
		$this->assertNotEquals(1609744980, $cookie->getParam('Expires', TRUE), 'check that expires has not changed');
		$this->assertEquals(0, $cookie->setParam('Max-Age', '0'), 'set max-age');
		$this->assertNull($cookie->getParam('Expires', TRUE), 'check that expires has changed');

		$this->assertTrue($cookie->setParam('Secure'), 'set secure');
		$this->assertTrue($cookie->getParam('Secure'), 'check secure');
		$this->assertFalse($cookie->setParam('Secure', FALSE), 'set secure false');
		$this->assertFalse($cookie->getParam('Secure'), 'check secure false');

		$this->assertEquals('Strict', $cookie->setParam('SameSite', 'Strict'), 'set SameSite');
		$this->assertEquals('Strict', $cookie->getParam('SameSite'), 'check SameSite');
		$this->assertNull($cookie->setParam('SameSite', NULL), 'remove SameSite');
		$this->assertNull($cookie->getParam('SameSite'), 'check SameSite is removed');
	}

	public function testGetSetKeyValue ()
	{
		$cookieStr = 'cookieKey=cookie+value+with+special+char%21';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals('cookieKey=cookie+value+with+special+char%21', $cookie->getCookie(), 'cookie with special chars');

		$this->assertEquals('cookieKey', $cookie->getKey(), 'check key');
		$this->assertEquals('cookie value with special char!', $cookie->getValue(), 'check value');

		$cookie->setKey('someOtherKey');
		$this->assertEquals('someOtherKey', $cookie->getKey(), 'changed key');

		$cookie->setValue('some other value?');
		$this->assertEquals('some other value?', $cookie->getValue(), 'changed value');

		$this->assertEquals('someOtherKey=some+other+value%3F', $cookie->getCookie(), 'changed cookie with special chars');
	}

	public function testGetFullCookie ()
	{
		$cookieStr = 'cookieKey=cookieValue; Expires=Mon, 04 Jan 2021 07:23:00 GMT; Max-Age=31536000; Domain=some-site.com; Path=/docs; SameSite=Strict; Secure; HttpOnly';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'full cookie should be the same');

		$cookieStr = 'cookieKey=cookieValue; Max-Age=0';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check session cookie');

		$cookieStr = 'cookieKey=cookieValue; Max-Age=-1';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check max-age -1');

		$cookieStr = 'cookieKey=cookieValue; Max-Age=1';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check max-age 1');

		$cookieStr = 'cookieKey=cookieValue; Expires=Mon, 04 Jan 2021 07:23:00 GMT; Max-Age=0';
		$cookie    = new Cookie($cookieStr);
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check session cookie with expires set');
		$this->assertTrue($cookie->setParam('SameSite', TRUE), 'set SameSite');
		$this->assertEquals("{$cookieStr}; SameSite", $cookie->getFullCookie(), 'check full cookie has SameSite');
		$this->assertFalse($cookie->setParam('SameSite', FALSE), 'set SameSite false');
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check full cookie doesnt has SameSite');
		$this->assertNull($cookie->setParam('SameSite', NULL), 'remove SameSite');
		$this->assertEquals($cookieStr, $cookie->getFullCookie(), 'check full cookie SameSite was removed');

		$cookieArr = [
			'cookieKey' => 'cookieValue',
			'Expires'   => 'Mon, 4 Jan 2021 07:23:00 GMT',
			'Max-Age'   => 31536000,
			'Domain'    => 'some-site.com',
			'Path'      => '/docs',
			'SameSite'  => 'Strict',
			'Secure'    => TRUE,
		];
		$cookie    = new Cookie($cookieArr);
		$this->assertEquals('cookieKey=cookieValue;Expires=Mon, 04 Jan 2021 07:23:00 GMT', $cookie->getFullCookie(';', ['expires']), 'check cookie parts');
		$this->assertEquals('cookieKey=cookieValue', $cookie->getFullCookie(';', ['HttpOnly']), 'check HttpOnly does not get returned');
	}

	public function testOverrideExpires ()
	{
		$cookieArr = [
			'cookieKey' => 'cookieValue',
			'Expires'   => 'Mon, 4 Jan 2021 07:23:00 GMT',
			'Max-Age'   => 60,
			'Domain'    => 'some-site.com',
			'Path'      => '/docs',
			'SameSite'  => 'Strict',
			'Secure'    => TRUE,
			'HttpOnly'  => TRUE,
		];
		$cookie    = new Cookie($cookieArr);

		$this->assertEquals('cookieKey=cookieValue;Expires=Mon, 04 Jan 2021 07:23:00 GMT', $cookie->getFullCookie(';', ['expires']), 'check cookie parts');

		$cookie->setParam('expires', 'Mon, 6 Jan 2121 07:23:00 GMT');
		$cookie->setParam('Secure', FALSE);

		$this->assertEquals('cookieKey=cookieValue;Expires=Mon, 06 Jan 2121 07:23:00 GMT', $cookie->getFullCookie(';', ['expires']), 'check cookie parts');
	}

	public function testIsExpired ()
	{
		$cookie = new Cookie('cookieKey=cookieValue');
		$this->assertFalse($cookie->isExpired(), 'cookie without expire date');

		$cookie = new Cookie('cookieKey=cookieValue;Expires=Mon, 04 Jan 2121 07:23:00 GMT;');
		$this->assertFalse($cookie->isExpired(), 'not expired cookie');

		$cookie = new Cookie('cookieKey=cookieValue;Expires=Wed, 1 Jan 2020 07:23:00 GMT');
		$this->assertTrue($cookie->isExpired(), 'expired cookie');

		$cookie = new Cookie('cookieKey=cookieValue; Max-Age=3600');
		$this->assertFalse($cookie->isExpired(), 'not expired max-age positive (future)');

		$cookie = new Cookie('cookieKey=cookieValue; Max-Age=0');
		$this->assertTrue($cookie->isExpired(), 'expired max-age 0');

		$cookie = new Cookie('cookieKey=cookieValue; Max-Age=-1');
		$this->assertTrue($cookie->isExpired(), 'expired max-age negative (past)');

		$cookie = new Cookie('cookieKey=cookieValue; Expires=Mon, 04 Jan 2021 07:23:00 GMT; Max-Age=0');
		$this->assertTrue($cookie->isExpired(), 'expired max-age overrides expires date');
	}

	public function testSetCookie ()
	{
		$expiresStr = 'Mon, 4 Jan 2021 07:23:00 GMT';
		$expires    = strtotime($expiresStr);
		$args       = [
			'Expires'  => $expiresStr,
			'Max-Age'  => 3600,
			'Domain'   => 'some-site.com',
			'Path'     => '/docs',
			'SameSite' => 'Strict',
			'Secure'   => TRUE,
			'HttpOnly' => TRUE,
		];

		$cookie = new Cookie();
		$cookie->setCookie('cookieKey', 'cookieValue', $expires, $args['Path'], $args['Domain'], $args['Secure'], $args['HttpOnly'], $args['SameSite']);
		$cookieFull = 'cookieKey=cookieValue; Expires=Mon, 04 Jan 2021 07:23:00 GMT; Domain=some-site.com; Path=/docs; SameSite=Strict; Secure; HttpOnly';
		$this->assertEquals($cookieFull, $cookie->getFullCookie(), 'individual values');

		$cookie = new Cookie();
		$cookie->setCookie('cookieKey', 'cookieValue', ['SameSite' => 'Strict', 'Secure' => TRUE, 'HttpOnly' => FALSE,]);
		$cookieFull = 'cookieKey=cookieValue; SameSite=Strict; Secure';
		$this->assertEquals($cookieFull, $cookie->getFullCookie(), 'options values');

		$cookie = new Cookie();
		$cookie->setCookie('cookieKey', 'cookieValue', $args);
		$cookieFull = 'cookieKey=cookieValue; Expires=Mon, 04 Jan 2021 07:23:00 GMT; Max-Age=3600; Domain=some-site.com; Path=/docs; SameSite=Strict; Secure; HttpOnly';
		$this->assertEquals($cookieFull, $cookie->getFullCookie(), 'all options values');

		$cookie = new Cookie();
		$cookie->setCookie('cookieKey', 'cookieValue', $expires, $args['Path'], $args['Domain'], $args['Secure'], $args['HttpOnly'], $args['SameSite'], $args['Max-Age']);
		$cookieFull = 'cookieKey=cookieValue; Max-Age=3600; Domain=some-site.com; Path=/docs; SameSite=Strict; Secure; HttpOnly';
		$this->assertEquals($cookieFull, $cookie->getFullCookie(), 'all individual values');
	}
}