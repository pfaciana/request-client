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
		return $this->init($url, $options)->exec();
	}
}