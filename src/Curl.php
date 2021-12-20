<?php

namespace RequestClient;

/*
 * Class and Trait structures
 *
 * Curl
 *    extends CurlSession
 *        includes CurlBrowserOptions (as $this->options)
 *            use CurlProxyTrait
 *                use EnvironmentTrait
 *        extends CurlBrowser
 *            use CurlOptionsTrait
 *            extends Browser
 *                use UserAgentTrait
 *                use QueryTrait
 *                use EnvironmentTrait (same as CurlBrowserOptions usage, but it exposes this to the user instance)
 *
 */

class Curl extends CurlSession
{
	public function request ($url, $options = [])
	{
		$options['retry'] = (array_key_exists('retry', $options) && is_int($options['retry']) && $options['retry'] > 0) ? $options['retry'] : 0;

		do {
			$response = $this->init($url, $options)->exec();
		} while ($this->requestFailed() && $options['retry']-- > 0 && !sleep($options['retryDelay'] ??= 3));

		return $response;
	}
}