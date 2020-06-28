<?php

use RequestClient\Request\UserAgentTrait;

class UserAgentTraitTest extends \Codeception\Test\Unit
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
	public function testSetUserAgents ()
	{
		/** @var UserAgentTrait $mock */
		$mock = $this->getMockForTrait(UserAgentTrait::class);

		$userAgent1 = 'some random user agent 1';
		$userAgent2 = 'some random user agent 2';

		$this->assertEquals([$userAgent2], $mock->setUserAgents($userAgent2), 'second user agent as string');

		$this->assertEquals([$userAgent1], $mock->setUserAgents($userAgent1), 'first user agent as string');

		$this->assertEquals([$userAgent1, $userAgent2], $mock->setUserAgents($userAgent2, TRUE), 'append second user agent as string');

		$this->assertEquals([$userAgent1], $mock->setUserAgents([$userAgent1]), 'first user agent as array');

		$this->assertFalse($mock->setUserAgents(), 'send empty value');
	}

	public function testGetUserAgents ()
	{
		/** @var UserAgentTrait $mock */
		$mock = $this->getMockForTrait(UserAgentTrait::class);

		$userAgents = $mock->getUserAgents();

		$expect = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_1) AppleWebKit/537.35 (KHTML, like Gecko) Chrome/27.0.1253.110 Safari/537.35';
		$this->assertEquals($expect, $userAgents[0], 'first user agent');

		$this->assertCount(11, $userAgents, 'check the length of user agents');
	}

	public function testGetUserAgent ()
	{
		/** @var UserAgentTrait $mock */
		$mock = $this->getMockForTrait(UserAgentTrait::class);

		$expect = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_1) AppleWebKit/537.35 (KHTML, like Gecko) Chrome/27.0.1253.110 Safari/537.35';
		$this->assertEquals($expect, $mock->getUserAgent(0), 'first user agent');

		$this->assertIsString($mock->getUserAgent(), 'some random user agent');

		$this->assertIsString($mock->getUserAgent(NULL), 'lookup not found');
	}
}