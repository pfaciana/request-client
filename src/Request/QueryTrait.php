<?php


namespace RequestClient\Request;

use DOMXPath;
use JmesPath;
use Minifier\TinyMinify;

trait QueryTrait
{
	protected $qp;
	protected $json;
	protected $response;

	abstract public function getResponse ();

	public function minifyResponse ()
	{
		return $this->minify(NULL, TRUE);
	}

	public function minify ($html = NULL, $save = FALSE)
	{
		if (!empty($html) && !is_string($html)) {
			return $html;
		}

		$html = TinyMinify::html($html ?: $this->getResponse());

		$save && ($this->response = $html);

		return $html;
	}

	public function normalizeQueryArgs ($args)
	{
		if (empty($args)) {
			return [];
		}

		if (func_num_args() > 1) {
			$args = func_get_args();
		}
		elseif (!is_array($args)) {
			$args = [$args];
		}

		if (count($args) > 2) {
			return $args;
		}

		if (count($args) == 2) {
			if (is_string($args[1])) {
				return [$args[0], $args[1], []];
			}
			else {
				return [$args[0], NULL, $args[1]];
			}
		}

		if (is_string($args[0])) {
			return [NULL, $args[0], []];
		}
		elseif (is_array($args[0])) {
			return [NULL, NULL, $args[0]];
		}
		else {
			return [$args[0], NULL, []];
		}

	}

	public function getQp ()
	{
		return $this->qp;
	}

	public function setQp ($document = NULL, $options = [])
	{
		return $this->qp = $this->processQp($document, NULL, $options);
	}

	protected function processQp ($qp = NULL, $selector = NULL, $options = [])
	{
		if (function_exists('tidy_repair_string') && is_string($qp)) {
			$qp = tidy_repair_string($qp);
		}

		if (isset($options['use_parser']) && $options['use_parser'] === 'xml') {
			return qp($qp, $selector, $options);
		}

		return html5qp($qp, $selector, $options);
	}

	/* Legacy for v1.3.0 */
	public function query ()
	{
		return $this->queryHtml(...func_get_args());
	}

	public function queryHtml ($document = NULL, $selector = NULL, $options = [])
	{
		if (func_num_args() > 0 && func_num_args() < 3) {
			list($document, $selector, $options) = $this->normalizeQueryArgs(func_get_args());
		}

		$options += ['minify' => TRUE, 'reset' => FALSE];

		if (is_null($document)) {
			if (empty($this->qp) || $options['reset']) {
				$qp = $options['minify'] ? $this->minify() : $this->getResponse();
			}
			else {
				$qp = $this->qp;
			}
		}
		else {
			$qp = $options['minify'] ? $this->minify($document) : $document;
		}

		if (empty($this->qp) || $options['reset']) {
			$qp = $this->setQp($qp, $options);
		}

		$qp = $this->processQp($qp, $selector, $options);

		return $qp;
	}

	public function jQuery ($selector, $context = NULL, $options = [])
	{
		return $this->queryHtml($context, $selector, $options);
	}

	public function xPath ($selector, $context = NULL, $options = [])
	{
		$DOMNodeList = [];

		if (!is_iterable($context)) {
			$context = [$context];
		}

		foreach ($context as $document) {
			$qp = $this->queryHtml($document, NULL, $options);

			$doc = new \DomDocument;

			if (function_exists('libxml_use_internal_errors')) {
				libxml_use_internal_errors(TRUE);
			}

			if (!empty($document) && !is_string($document)) {
				$doc->loadXML($qp->xml());
			}
			else {
				$doc->loadHTML($qp->html());
			}

			if (function_exists('libxml_clear_errors')) {
				libxml_clear_errors();
			}

			$xpath = new DomXPath($doc);

			$DOMNodeList = array_merge($DOMNodeList, iterator_to_array($xpath->query($selector)));
		}

		return $DOMNodeList;
	}

	public function getJson ()
	{
		return $this->json;
	}

	public function setJson ($document = NULL, $options = [])
	{
		return $this->json = $this->processJson($document, $options);
	}

	public function filterJson ($callback, $options = [])
	{
		return $this->setJson(NULL, ['callback' => $callback] + $options);
	}

	protected function processJson ($document = NULL, $options = [])
	{
		$document = $document ?? $this->getResponse();

		$options += ['assoc' => TRUE, 'depth' => 512, 'bitmask' => 0, 'callback' => NULL];

		if (is_callable($options['callback'])) {
			$document = call_user_func($options['callback'], $document, $options, $this);
		}

		if (is_scalar($document)) {
			$document = json_decode($document, $options['assoc'], $options['depth'], $options['bitmask']);
		}

		return $document;
	}

	public function queryJson ($document = NULL, $selector = NULL, $options = [])
	{
		if (func_num_args() > 0 && func_num_args() < 3) {
			list($document, $selector, $options) = $this->normalizeQueryArgs(func_get_args());
		}

		$options += ['reset' => FALSE];

		if (is_null($document)) {
			if (empty($this->json) || $options['reset']) {
				$json = $this->getResponse();
			}
			else {
				$json = $this->json;
			}
		}
		else {
			$json = $document;
		}

		if (empty($this->json) || $options['reset']) {
			$json = $this->setJson($json, $options);
		}
		else {
			$json = $this->processJson($json, $options);
		}

		$result = JmesPath\Env::search($selector, $json);

		return $result;
	}

	public function jPath ($selector, $context = NULL, $options = [])
	{
		return $this->queryJson($context, $selector, $options);
	}
}