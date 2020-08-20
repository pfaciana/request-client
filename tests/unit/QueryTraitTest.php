<?php

use RequestClient\Request\QueryTrait;

class QueryTraitTest extends \Codeception\Test\Unit
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
	public function testNormalizeQueryArgs ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<h1>Header A</h1>
							<h2>Sub-Header B</h2>
							<p class="item">content</p>
							<a class="item btn" href="/some-location">link</a>
						</body>
					</html>';
		$s = '.item';
		$o = ['minify' => TRUE];
		$q = html5qp($d);

		$this->assertEquals([$d, $s, $o], $mock->normalizeQueryArgs([$d, $s, $o]), 'All 3 params');
		$this->assertEquals([$q, $s, $o], $mock->normalizeQueryArgs([$q, $s, $o]), 'All 3 params w/ $qp');

		// If passing 2 params the first one MUST be the dom (string or obj)
		$this->assertEquals([$d, $s, []], $mock->normalizeQueryArgs([$d, $s]), 'string, string');
		$this->assertEquals([$q, $s, []], $mock->normalizeQueryArgs([$q, $s]), 'obj, string');
		$this->assertEquals([$d, NULL, $o], $mock->normalizeQueryArgs([$d, $o]), 'string, array');
		$this->assertEquals([$q, NULL, $o], $mock->normalizeQueryArgs([$q, $o]), 'obj, array');

		// A single param that's a string, MUST be a selector
		$this->assertEquals([$q, NULL, []], $mock->normalizeQueryArgs([$q]), 'obj');
		$this->assertEquals([NULL, $s, []], $mock->normalizeQueryArgs([$s]), 'string');
		$this->assertEquals([NULL, NULL, $o], $mock->normalizeQueryArgs([$o]), 'array');

		// Edge cases
		$this->assertEquals([], $mock->normalizeQueryArgs(NULL), 'NULL');
		$this->assertEquals([], $mock->normalizeQueryArgs([]), 'empty array');
		$this->assertEquals([NULL, $s, []], $mock->normalizeQueryArgs($s), '1 non-array param');
		$this->assertEquals([$d, $s, $o], $mock->normalizeQueryArgs($d, $s, $o), '3 non-array params');
	}

	public function testSetGetQp ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<h1>Header A</h1>
							<h2>Sub-Header B</h2>
							<p class="item">content</p>
							<a class="item btn" href="/some-location">link</a>
						</body>
					</html>';
		$o = ['use_parser' => 'xml'];

		$this->assertEquals(html5qp(tidy_repair_string($d))->html(), $mock->setQp($d)->html(), 'As HTML5');
		$this->assertEquals(qp($d, NULL, $o)->xml(), $mock->setQp($d, $o)->xml(), 'As XML');

		$qp = $mock->setQp($d);
		$this->assertEquals($qp, $mock->getQp(), 'get $qp');
	}

	public function testJQuery ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<h1>Header A</h1>
							<h2>Sub-Header B</h2>
							<p class="item">content</p>
							<img
								src="/image.jpg"
								alt="image alt tag"
								class="responsive"
								>
							<a class="item btn" href="/some-location">link</a>
						</body>
					</html>';

		$this->assertCount(2, $mock->jQuery('.item', $d), 'set document and query');
		$this->assertCount(1, $mock->jQuery('.responsive'), 'query existing document');

		$d2 = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<h1>Header A</h1>
							<h2>Sub-Header B</h2>
							<div class="items">
								<h3>Sub-Header C</h3>
								<p class="item">content 1</p>
								<p class="item">content 2</p>
								<p class="item">content 3</p>
								<footer>footer</footer>
							</div>
							<h2>Sub-Header D</h2>
							<p><a class="btn" href="/some-location">link</a></p>
						</body>
					</html>';

		$this->assertCount(3, $mock->jQuery('.item', $d2), 'query document, but dont save');
		$this->assertCount(2, $mock->jQuery('.item'), 'previous document not saved');
		$this->assertCount(3, $mock->jQuery('.item', $d2, ['reset' => TRUE]), 'query document, but save');
		$this->assertCount(3, $mock->jQuery('.item'), 'previous document was saved');

		$items = $mock->jQuery('.items');
		$this->assertCount(3, $mock->jQuery('p', $items), '3 paragraph tags in div context');
		$this->assertCount(4, $mock->jQuery('p'), '4 paragraph tags in the entire document');
	}

	public function testXPath ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<article>
								<h1 data-counter="1">Header A</h1>
								<h2 data-counter="2">Sub-Header B</h2>
								<div class="items">
									<h3 data-counter="3">Sub-Header C</h3>
									<p class="item">content 1</p>
									<p class="item">content 2</p>
									<p class="item">content 3</p>
									<footer>footer</footer>
								</div>
								<div>
									<p>
										<img
											src="/image.jpg"
											alt="image alt tag"
											class="responsive"
											>
									</p>
								</div>
								<h2 data-counter="4">Sub-Header D</h2>
								<p><a class="btn" href="/some-location">link</a></p>
							</article>
						</body>
					</html>';

		$this->assertCount(1, $mock->xPath('//img', $d), 'set $qp and query');
		$this->assertCount(6, $mock->xPath('//*[starts-with(name(), "h")]'), 'query existing $qp for all tags that start with h');
		$this->assertCount(4, $mock->xPath('//body//*[starts-with(name(), "h")]'), 'query existing $qp for tags that start with h inside the body tag');
		$this->assertCount(4, $mock->xPath('//h1 | //h2 | //h3'), 'get all H1-H3s');

		foreach ($mock->xPath('//h1 | //h2 | //h3') as $index => $header) {
			$this->assertEquals($index + 1, qp($header)->attr('data-counter'), 'check that the headers come back in the correct order');
		}

		$mock->setQp($d);

		$body = $mock->xPath('//body');
		$this->assertCount(4, $mock->xPath('//*[starts-with(name(), "h")]', $body[0]), 'query a sub-content - DOMElement');

		$divs = $mock->xPath('//div');
		$this->assertCount(4, $mock->xPath('//*[starts-with(name(), "p")]', $divs), 'query a sub-content - array of DOMElements');
		$this->assertCount(4, $mock->xPath('//div//*[starts-with(name(), "p")]'), 'nested query is same as test above');
	}

	public function testJson ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = (object) [
			'a' => 1,
			'b' => [
				'b2a' => 'some value',
				'b2b' => (object) [
					'b3a' => TRUE,
					'b3b' => FALSE,
				],
				'b2c' => ['1.234', '5.678', 90],
			],
			'd' => '0',
			'c' => NULL,
		];

		$this->assertEquals('5.678', $mock->queryJson(json_encode($d), 'b.b2c[1]'), 'set and query response');
		$this->assertTrue($mock->jPath('b.b2b.b3a'), 'query response');
		$this->assertEquals(json_decode(json_encode($d), TRUE), $mock->getJson(), 'check response was saved');


		$d = [
			'foo' => 'bar',
			'baz' => 'boom',
			'cow' => 'milk',
			'php' => 'hypertext processor',
		];

		$callback = function ($document, $options, $client) {
			parse_str($document, $output);

			return $output;
		};

		$mock->setJson(http_build_query($d), ['callback' => $callback]);

		$this->assertEquals(json_decode(json_encode($d), TRUE), $mock->getJson(), 'check that json ran through the callback');

		$d = [
			'a' => TRUE,
			'b' => 'two',
			'c' => [1, 2, 3],
		];

		$this->assertTrue($mock->jPath('a', $d), 'query custom json');
		$this->assertEquals('bar', $mock->jPath('foo'), 'make sure the old json is still saved');
		$mock->setJson($d, ['reset' => TRUE]);
		$this->assertTrue($mock->jPath('a'), 'check to make sure the new json is saved');
	}

	public function testMiscQuery ()
	{
		/** @var QueryTrait $mock */
		$mock = $this->getMockForTrait(QueryTrait::class);

		$d = '<!DOCTYPE html>
					<html lang="en">
						<head>
							<title>Page Title</title>
						</head>
						<body>
							<h1>Header A</h1>
							<h2>Sub-Header B</h2>
							<p class="item">content</p>
							<img
								src="/image.jpg"
								alt="image alt tag"
								class="responsive"
								>
							<a class="item btn" href="/some-location">link</a>
						</body>
					</html>';

		$mock->setQp($d);

		$this->assertCount(2, $mock->queryHtml(NULL, '.item'), 'just selector against existing $qp');
		$this->assertCount(2, $mock->queryHtml('.item'), 'shorthand selector');
	}
}