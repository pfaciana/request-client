<?php

use \AspectMock\Test as test;
use \RequestClient\CurlSessionManager;

class CurlSessionManagerTest extends \Codeception\Test\Unit
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

	public function warning_handler ($errno, $errstr)
	{
		$this->assertEquals(E_USER_WARNING, $errno, "`{$errstr}` should be a E_USER_WARNING error");
	}

	// tests
	public function testIsValid ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com', 'https://d.com', 'https://e.com',];

		$manager = new CurlSessionManager($urls, [], FALSE);

		$this->assertTrue($manager->isValid(), 'manager is valid');

		$manager = new CurlSessionManager([], [], FALSE);

		$this->assertFalse($manager->isValid(), 'empty manager is not valid');
	}

	public function testSetGet ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com', 'https://d.com', 'https://e.com',];

		$manager = new CurlSessionManager($urls, [], FALSE);

		$this->assertEquals(6, $manager->getThreads(), 'get number of default threads');

		$manager->set('throttle', .1);
		$this->assertEquals(.1, $manager->getThrottle(), 'get throttle time');

		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($manager->set('started', 10), 'make sure reserved properties are not overridden');
		restore_error_handler();

		$manager->set(['threads' => 3, 'throttle' => .5]);
		$this->assertEquals(3, $manager->get('threads'), 'get number of threads from array setting');
		$this->assertEquals(.5, $manager->get('throttle'), 'get throttle time from array setting');

		$this->assertEquals('test', $manager->get('dne', 'test'), 'get default for key that does not exist');
	}

	public function testInit ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com', 'https://d.com', 'https://e.com',];

		$manager = new CurlSessionManager($urls, [], FALSE);

		$this->assertFalse($manager->get('started'), 'has not started');

		$manager->init();

		$this->assertEquals(0, $manager->get('started'), 'has started');

		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($manager->init(), 'make sure you cant restart before its finished');
		restore_error_handler();
	}

	public function testClose ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com'];

		$manager = new CurlSessionManager($urls);

		usleep($micro_seconds = 100000); // 1/10 sec

		$manager->close();

		$this->assertTrue($manager->getDuration() > $micro_seconds / 1e6 * .9, 'test time');
		$this->assertTrue($manager->getEndTime() > $manager->getStartTime(), 'test time');
	}

	public function testErrors ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com'];

		$manager = new CurlSessionManager($urls);

		$this->assertFalse($manager->hasErrors(), 'errors start out empty');

		$manager->addError('first error');

		$this->assertTrue($manager->hasErrors(), 'after adding an error');
		$this->assertEquals([['first error']], $manager->getErrors(), 'get first error');

		$this->assertFalse($manager->hasErrors(10), 'check errors at a position before adding');

		$manager->addError('random error', 10);
		$manager->addError('random error 2', 10);

		$this->assertTrue($manager->hasErrors(10), 'check errors at a position after adding');
		$this->assertEquals(['random error', 'random error 2'], $manager->getErrors(10), 'get errors at a position');
		$this->assertEquals([['first error'], 10 => ['random error', 'random error 2']], $manager->getErrors(), 'get all errors');

		$manager->groupFailed('CURLM_BAD_HANDLE');

		$this->assertTrue($manager->hasErrors(-1), 'check the group error was saved to position -1');
	}

	public function testAddingSessionsToGroup ()
	{
		$urls = ['https://a.com', 'https://b.com', 'https://c.com', 'https://d.com', 'https://e.com',];

		$manager = new CurlSessionManager($urls, ['threads' => 2]);

		$this->assertEquals(0, $manager->getStarted(), 'get started before start');
		$this->assertEquals(0, $manager->getSuccess(), 'get success before start');
		$this->assertEquals(0, $manager->getFailed(), 'get failed before start');
		$this->assertEquals(0, $manager->getCompleted(), 'get completed before start');
		$this->assertEquals(count($urls), $manager->getTotal(), 'get completed before start');

		curl_multi_setopt($mch = curl_multi_init(), CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$manager->addSessionsToGroup($mch);

		$this->assertEquals(2, $manager->get('started'), 'get started before processing');
		$this->assertEquals(0, $manager->get('success'), 'get success before processing');
		$this->assertEquals(0, $manager->get('failed'), 'get failed before processing');
		$this->assertEquals(0, $manager->get('completed'), 'get completed before processing');

		$manager->addSessionsToGroup($mch);

		$this->assertEquals(2, $manager->get('started'), 'get started after trying to add more sessions before processing');
		$this->assertEquals(0, $manager->get('success'), 'get success after trying to add more sessions before processing');
		$this->assertEquals(0, $manager->get('failed'), 'get failed after trying to add more sessions before processing');
		$this->assertEquals(0, $manager->get('completed'), 'get completed after trying to add more sessions before processing');

		$queuedUrls = [];

		// Callback
		test::double('RequestClient\CurlSession', [
			'setOptions' => function ($options = []) use (&$queuedUrls) {
				$queuedUrls[] = $options['testUrl'];
			},
		]);

		$manager = new CurlSessionManager($urls, ['threads' => 2]);

		$manager->addSessionsToGroup($mch, function ($options, $url, $session, $manager) {
			return $options + ['testUrl' => $url];
		});

		$this->assertEquals(array_slice($urls, 0, 2), $queuedUrls, 'check callback');

		test::clean();

		// Handle
		test::double('RequestClient\CurlSession', [
			'getHandle' => function ($options = []) {
				return $this->pos % 2 ? $this->ch : FALSE;
			},
		]);

		$manager = new CurlSessionManager($urls, ['threads' => 2]);

		$manager->addSessionsToGroup($mch);

		$this->assertEquals(4, $manager->get('started'), 'get started after failed');
		$this->assertEquals(0, $manager->get('success'), 'get success after failed');
		$this->assertEquals(2, $manager->get('failed'), 'get failed after failed');
		$this->assertEquals(2, $manager->get('completed'), 'get completed after failed');

		$this->assertTrue($manager->hasErrors(), 'errors after failed');
		$this->assertTrue($manager->hasErrors(0), 'errors at index 0 after failed');
		$this->assertFalse($manager->hasErrors(1), 'errors at index 1 after failed');
		$this->assertTrue($manager->hasErrors(2), 'errors at index 2 after failed');
		$this->assertFalse($manager->hasErrors(3), 'errors at index 3 after failed');
		$this->assertFalse($manager->hasErrors(4), 'errors at index 4 after failed');

		$this->assertCount(2, $manager->get('sessions'), 'count active sessions after failed');

		test::clean();

		// Empty Urls
		$urls = ['https://a.com', '', '', '', 'https://e.com',];

		$manager = new CurlSessionManager($urls, ['threads' => 2]);

		$manager->addSessionsToGroup($mch);

		$this->assertEquals(5, $manager->get('started'), 'get started after missing urls');
		$this->assertEquals(0, $manager->get('success'), 'get success after missing urls');
		$this->assertEquals(3, $manager->get('failed'), 'get failed after missing urls');
		$this->assertEquals(3, $manager->get('completed'), 'get completed after missing urls');

		$this->assertTrue($manager->hasErrors(), 'errors after failed');
		$this->assertFalse($manager->hasErrors(0), 'errors at index 0 after missing urls');
		$this->assertTrue($manager->hasErrors(1), 'errors at index 1 after missing urls');
		$this->assertTrue($manager->hasErrors(2), 'errors at index 2 after missing urls');
		$this->assertTrue($manager->hasErrors(3), 'errors at index 3 after missing urls');
		$this->assertFalse($manager->hasErrors(4), 'errors at index 4 after missing urls');

		$this->assertCount(2, $manager->get('sessions'), 'count active sessions after missing urls');
	}

	public function testProcessingSessions ()
	{
		$randomContent = 'lorem';

		test::func('RequestClient', 'curl_multi_getcontent', $randomContent);
		test::func('RequestClient', 'curl_getinfo', function ($ch) {
			$getInfo = [
				'http_code'      => 200,
				'url'            => 'https://some-domain.com/page',
				'content_type'   => 'text/html; charset=utf-8',
				'filetime'       => 1586482703,
				'request_size'   => 431,
				'size_upload'    => 0.0,
				'header_size'    => 318,
				'size_download'  => 31368.0,
				'total_time'     => 0.2584549,
				'local_ip'       => '192.168.1.192',
				'request_header' => "GET /page HTTP/2\r\nHost: some-domain.com\r\n\r\n\r\n",
			];

			return $getInfo;
		});


		$urls = ['https://a.com', 'https://b.com', 'https://c.com', 'https://d.com', 'https://e.com',];

		$manager = new CurlSessionManager($urls, ['threads' => 3]);

		curl_multi_setopt($mch = curl_multi_init(), CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$manager->addSessionsToGroup($mch);

		$this->assertFalse($manager->hasErrors(array_values($manager->get('sessions'))[0]->getHandle()), 'no errors for the first handle');

		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}

		$this->assertEquals(3, $manager->get('started'), 'get started after first round of processing');
		$this->assertEquals(3, $manager->get('success'), 'get success after first round of processing');
		$this->assertEquals(0, $manager->get('failed'), 'get failed after first round of processing');
		$this->assertEquals(3, $manager->get('completed'), 'get completed after first round of processing');

		// Not a resource
		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($manager->processSession('not a resource!'), 'make sure a resource is passed');
		restore_error_handler();

		// Failed
		$manager->addSessionsToGroup($mch);

		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch, NULL, 'Not Found');
		}

		$this->assertEquals(5, $manager->get('started'), 'get started after second round of processing');
		$this->assertEquals(3, $manager->get('success'), 'get success after second round of processing');
		$this->assertEquals(2, $manager->get('failed'), 'get failed after second round of processing');
		$this->assertEquals(5, $manager->get('completed'), 'get completed after second round of processing');

		// Callback
		$urls = ['https://a.com', 'https://b.com',];

		$manager = new CurlSessionManager($urls, ['threads' => 1]);

		curl_multi_setopt($mch = curl_multi_init(), CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$manager->addSessionsToGroup($mch);
		$firstResponse = NULL;
		$manager->processSession(array_values($manager->get('sessions'))[0]->getHandle(), function ($response, $session, $manager) use (&$firstResponse) {
			$firstResponse = $response;
		});
		$this->assertEquals($randomContent, $firstResponse, 'callback for index 0');

		test::func('RequestClient', 'curl_multi_getcontent', '');
		test::func('RequestClient', 'curl_getinfo', ['http_code' => 0, 'url' => 'https://b.com', 'request_header' => "\r\n",]);

		$manager->addSessionsToGroup($mch);
		$secondResponse = NULL;
		$manager->processSession(array_values($manager->get('sessions'))[0]->getHandle(), function ($response, $session, $manager) use (&$secondResponse) {
			$secondResponse = $response;
		}, 'Error Found');
		$this->assertFalse($secondResponse, 'callback for index 1');

		// Callback skipped
		$thirdResponse = NULL;

		set_error_handler([$this, 'warning_handler']);
		$this->assertFalse($manager->processSession('not a resource', function ($response, $session, $manager) use (&$thirdResponse) {
			$thirdResponse = 'third response';
		}, 'Error Found'), 'make sure callback is not called when a valid resource is not passed');
		restore_error_handler();

		$this->assertNull($thirdResponse, '(no) callback for index 2');
	}

	public function testThrottle ()
	{
		test::func('RequestClient', 'curl_multi_getcontent', '');
		test::func('RequestClient', 'curl_getinfo', ['http_code' => 0, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);

		$urls = ['https://a.com', 'https://b.com', 'https://c.com'];

		$throttle = .25;
		$manager  = new CurlSessionManager($urls, ['throttle' => $throttle]);

		curl_multi_setopt($mch = curl_multi_init(), CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$manager->addSessionsToGroup($mch);
		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}
		$manager->close();

		$throttleTime = $throttle * (count($urls) - 1);

		$this->assertTrue($manager->get('duration') > $throttleTime * .9, 'test throttle');
	}

	public function testCalculationHelpers ()
	{
		test::func('RequestClient', 'curl_multi_getcontent', '');
		test::func('RequestClient', 'curl_getinfo', ['http_code' => 0, 'url' => 'https://some-site.com', 'request_header' => "\r\n",]);

		$urls = [];
		for ($i = 0; $i < 7; $i++) {
			$urls[] = uniqid('https://some-site-') . '-url.com';
		}

		$threads  = 2;
		$throttle = .25;
		$manager  = new CurlSessionManager($urls, ['threads' => $threads, 'throttle' => $throttle]);

		curl_multi_setopt($mch = curl_multi_init(), CURLMOPT_MAXCONNECTS, $manager->get('threads'));

		$this->assertEquals(0, $manager->getItemPerSecond(), 'get item/sec before start');
		$this->assertEquals(0, $manager->getSecondsPerItem(), 'get sec/item before start');
		$this->assertEquals(0, $manager->getPercent(), 'get percent before start');
		$this->assertEquals(0, $manager->getPercent(1), 'get percent w/ precision of 1 before start');
		$this->assertEquals(0, $manager->getPercent(2, TRUE), 'get raw percent w/ precision of 2 before start');

		$manager->addSessionsToGroup($mch);

		$this->assertEquals(0, $manager->getItemPerSecond(), 'get item/sec after first session has started');
		$this->assertEquals(0, $manager->getSecondsPerItem(), 'get sec/item after first session has started');
		$this->assertEquals(0, $manager->getPercent(), 'get percent after first session has started');
		$this->assertEquals(0, $manager->getPercent(1), 'get percent w/ precision of 1 after first session has started');
		$this->assertEquals(0, $manager->getPercent(2, TRUE), 'get raw percent w/ precision of 2 after first session has started');

		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}
		$completed = $threads * 1;

		$this->assertTrue($manager->getItemPerSecond() > 1 / ($throttle * ($completed - 1) / $completed) * .9, 'get item/sec after first session');
		$this->assertTrue($manager->getSecondsPerItem() > $throttle * ($completed - 1) / $completed * .9, 'get sec/item after first session');
		$this->assertEquals(2 / 7 * 100, $manager->getPercent(), 'get percent after first session');
		$this->assertEquals(28.5, $manager->getPercent(1), 'get percent w/ precision of 1 after first session');
		$this->assertEquals(.2857, $manager->getPercent(2, TRUE), 'get raw percent w/ precision of 2 after first session');

		$manager->addSessionsToGroup($mch);
		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}
		$completed = $threads * 2;

		$this->assertTrue($manager->getItemPerSecond() > 1 / ($throttle * ($completed - 1) / $completed) * .9, 'get item/sec after second session');
		$this->assertTrue($manager->getSecondsPerItem() > $throttle * ($completed - 1) / $completed * .9, 'get sec/item after second session');
		$this->assertEquals(4 / 7 * 100, $manager->getPercent(), 'get percent after second session');
		$this->assertEquals(57.1, $manager->getPercent(1), 'get percent w/ precision of 1 after second session');
		$this->assertEquals(.5714, $manager->getPercent(2, TRUE), 'get raw percent w/ precision of 2 after second session');

		$manager->addSessionsToGroup($mch);
		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}
		$manager->addSessionsToGroup($mch);
		foreach ($manager->get('sessions') as $ch => $session) {
			$manager->processSession($ch);
		}
		$completed = count($urls);

		$this->assertTrue($manager->getItemPerSecond() > 1 / ($throttle * ($completed - 1) / $completed) * .9, 'get item/sec after second session');
		$this->assertTrue($manager->getSecondsPerItem() > $throttle * ($completed - 1) / $completed * .9, 'get sec/item after second session');
		$this->assertEquals(100, $manager->getPercent(), 'get percent after second session');
		$this->assertEquals(100, $manager->getPercent(1), 'get percent w/ precision of 1 after second session');
		$this->assertEquals(1, $manager->getPercent(2, TRUE), 'get raw percent w/ precision of 2 after second session');

	}
}