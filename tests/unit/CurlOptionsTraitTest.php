<?php

use RequestClient\Request\CurlOptionsTrait;

class CurlOptionsTraitTest extends \Codeception\Test\Unit
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

	public function warning_handler ($errno, $errstr)
	{
		$this->assertEquals(E_USER_WARNING, $errno, "`{$errstr}` should be a E_USER_WARNING error");
	}

	// tests
	public function testGetCurlOptions ()
	{
		/** @var CurlOptionsTrait $mock */
		$mock = $this->getMockForTrait(CurlOptionsTrait::class);

		$options = $mock->getCurlOptions();

		$this->assertCount(15, $options, 'number of default options');

		$this->assertEquals(300, $options[CURLOPT_TIMEOUT], 'default timeout length');

		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($mock->getCurlOptions('not an array'), 'param is not an array');
		restore_error_handler();
	}

	public function testGetCurlOption ()
	{
		/** @var CurlOptionsTrait $mock */
		$mock = $this->getMockForTrait(CurlOptionsTrait::class);

		$this->assertEquals(300, $mock->getCurlOption(CURLOPT_TIMEOUT), 'default timeout length');
	}

	public function testSetCurlOptions ()
	{
		/** @var CurlOptionsTrait $mock */
		$mock = $this->getMockForTrait(CurlOptionsTrait::class);

		$options = $mock->setCurlOptions([CURLOPT_URL => 'https://some-url.com'], TRUE);
		$this->assertEquals('https://some-url.com', $options[CURLOPT_URL], 'add url');
		$this->assertCount(1, $options, 'merge should only have 1 item');

		$options = $mock->setCurlOptions([CURLOPT_TIMEOUT => 300], TRUE);
		$this->assertEquals(300, $options[CURLOPT_TIMEOUT], 'merge timeout');
		$this->assertCount(2, $options, 'merge should only have 2 items');

		$options = $mock->setCurlOptions([CURLOPT_CONNECTTIMEOUT => 300], FALSE);
		$this->assertEquals(300, $options[CURLOPT_CONNECTTIMEOUT], 'set connect timeout');
		$this->assertCount(1, $options, 'non-merge should only have 1 item');

		$this->assertFalse($mock->setCurlOptions(), 'param is empty');

		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($mock->setCurlOptions('not an array'), 'param is not an array');
		restore_error_handler();
	}
}