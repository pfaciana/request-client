<?php

use RequestClient\Request\Headers;

class HeadersTest extends \Codeception\Test\Unit
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
	public function testSetHeaders ()
	{
		$headers = new Headers();

		$origHeaders     = "a b c \r\nkey1: value1\r\nkey2: value2a\r\nkey2: value2b\r\n\r\n";
		$expectedHeaders = ['key1' => ['value1'], 'key2' => ['value2a', 'value2b']];

		$this->assertEquals($expectedHeaders, $headers->setHeaders($origHeaders), 'simple set headers');
		$this->assertFalse($headers->setHeaders(1), 'simple set headers');
	}

	public function testGetHeaders ()
	{
		$headers = new Headers();

		$origHeaders = "a b c \r\nkey1: value1\r\nkey2: value2a\r\nkey2: value2b\r\n\r\n";
		$headers->setHeaders($origHeaders);

		$this->assertEquals(['key1' => 'value1', 'key2' => 'value2a; value2b'], $headers->getHeaders(), 'join on `; `');
		$this->assertEquals(['key1' => ['value1'], 'key2' => ['value2a', 'value2b']], $headers->getHeaders(FALSE), 'no joining');
	}

	public function testGetRawHeaders ()
	{
		$headers = new Headers();

		$origHeaders = "a b c \r\nkey1: value1\r\nkey2: value2a\r\nkey2: value2b\r\n\r\n";
		$headers->setHeaders($origHeaders);

		$this->assertEquals("key1: value1\r\nkey2: value2a\r\nkey2: value2b", $headers->getRawHeaders(), 'raw headers');
	}

	public function testGetHeader ()
	{
		$headers = new Headers();

		$origHeaders = "a b c \r\nKey1: value1\r\nkey2: value2a\r\nkey2: value2b\r\n\r\n";
		$headers->setHeaders($origHeaders);

		$this->assertEquals('value1', $headers->getHeader('key1'), 'key1 default join');
		$this->assertEquals(['value1'], $headers->getHeader('key1', FALSE), 'key1 no join');
		$this->assertEquals('value2a; value2b', $headers->getHeader('key2'), 'key2 default join');
		$this->assertEquals('value2a,value2b', $headers->getHeader('key2', ','), 'key2 join on comma');
		$this->assertEquals(['value2a', 'value2b'], $headers->getHeader('key2', FALSE), 'key2 no join');
		$this->assertEquals(NULL, $headers->getHeader('key3'), 'key does not exist');
	}
}