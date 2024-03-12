<?php
namespace Coercive\Utility\Render;

use Closure;
use Exception;

/**
 * Render
 *
 * @package Coercive\Utility\Render
 * @link https://github.com/Coercive/Render
 *
 * @author Anthony Moral <contact@coercive.fr>
 * @copyright 2023 Anthony Moral
 * @license MIT
 */
class Render
{
	const DEFAULT_EXTENSION = 'php';
	const DEFAULT_TEMPLATE = 'default';

	const TEMPLATE_ORDER_GET_FIRST = 'first';
	const TEMPLATE_ORDER_GET_LAST = 'last';

	/** @var string Root Paths */
	private
		$directory,
		$customTemplate = '',
		$firstTemplate = '',
		$lastTemplate = '',
		$order = self::TEMPLATE_ORDER_GET_FIRST;

	/** @var array Injected paths/datas */
	private
		$globals = [],
		$datas = [],
		$files = [];

	/** @var string Processed Views Content */
	private $views = '';

	/** @var Exception[] List of errors for debug */
	private $exceptions = [];

	/** @var Closure customer debug function that get Exception as parameter like : function(Exception $e) { ... } */
	private $closure = null;

	/**
	 * @param string $template
	 * @return void
	 */
	private function setTemplates(string $template)
	{
		$this->lastTemplate = trim($template, '/');
		if(!$this->firstTemplate) {
			$this->firstTemplate = trim($template, '/');
		}
	}

	/**
	 * PREPARE VIEW PATH
	 *
	 * @param string $path
	 * @return array
	 */
	private function file(string $path): array
	{
		# Delete spaces and start/end slashes
		$path = trim(str_replace(' ', '', $path), '/');
		if(!$path) {
			$e = new Exception('Empty given path');
			$this->addException($e);
		}

		# Detect template
		preg_match('`^(?P<template>[a-z0-9_-]*)/.*`i', $path, $matches);
		if(empty($matches['template']) || !is_dir($this->directory . $matches['template'])) {
			$e = new Exception('Template directory does not exist : ' . $path);
			$this->addException($e);
			$template = self::DEFAULT_TEMPLATE;
		}
		else {
			$template = $matches['template'];
		}

		# Detect extension
		preg_match('`\.(?P<extension>[a-z0-9]+)$`i', $path, $matches);
		$extension = empty($matches['extension']) ? self::DEFAULT_EXTENSION : strtolower($matches['extension']);
		$addExt = empty($matches['extension']) ? '.' . self::DEFAULT_EXTENSION : '';

		# Handle view file
		$target = $this->directory . $path . $addExt;
		$file = realpath($target);
		if (!$file || !is_file($file)) {
			$e = new Exception("View file does not exist : $target");
			$this->addException($e);
			$file = '';
		}

		# Build return statement
		return [
			'path' => $file,
			'template' => $template,
			'extension' => $extension
		];
	}

	/**
	 * Add Exception for external debug handler
	 *
	 * @param Exception $e
	 * @return void
	 */
	private function addException(Exception $e)
	{
		$this->exceptions[] = $e;
		if(null !== $this->closure) {
			($this->closure)($e);
		}
	}

	/**
	 * Render constructor.
	 *
	 * @param string $directory : Root directory witch contain templates
	 * @param string $template [optional]
	 * @return void
	 */
	public function __construct(string $directory, string $template = '')
	{
		$this->directory = rtrim($directory, '/') . '/';
		if($template) {
			$this->setTemplate($template);
		}
	}

	/**
	 * Views content
	 *
	 * @return string
	 */
	public function getViews(): string
	{
		return $this->views;
	}

	/**
	 * Set global data (override)
	 *
	 * @param array $data
	 * @return Render
	 */
	public function setGlobalDatas(array $data): Render
	{
		$this->globals = $data;
		return $this;
	}

	/**
	 * Add global data (merge)
	 *
	 * @param array $data
	 * @return Render
	 */
	public function addGlobalDatas(array $data): Render
	{
		$this->globals = array_merge($this->globals, $data);
		return $this;
	}

	/**
	 * Add datas (override)
	 *
	 * @param array $data
	 * @return Render
	 */
	public function setDatas(array $data): Render
	{
		$this->datas = $data;
		return $this;
	}

	/**
	 * Add datas (merge)
	 *
	 * @param array $data
	 * @return Render
	 */
	public function addDatas(array $data): Render
	{
		$this->datas = array_merge($this->datas, $data);
		return $this;
	}

	/**
	 * Set view path to the file stack (override)
	 *
	 * @param string $path
	 * @return Render
	 */
	public function setPath(string $path): Render
	{
		$file = $this->file($path);
		$this->files = [$file];
		$this->setTemplates($file['template']);
		return $this;
	}

	/**
	 * Add view path to the file stack 'merge)
	 *
	 * @param string $path
	 * @return Render
	 */
	public function addPath(string $path): Render
	{
		$file = $this->file($path);
		$this->files[] = $file;
		$this->setTemplates($file['template']);
		return $this;
	}

	/**
	 * Get Exception list for external debug
	 *
	 * @return Exception[]
	 */
	public function getExceptions(): array
	{
		return $this->exceptions;
	}

	/**
	 * Set a debug function
	 *
	 * It will log all given exceptions like :
	 * function(Exception $e) { ... }
	 *
	 * Can be reset with give no parameter
	 *
	 * @param Closure|null $function
	 * @return $this
	 */
	public function debug(Closure $function = null): Render
	{
		$this->closure = $function;
		return $this;
	}

	/**
	 * Template : get first from view for render
	 *
	 * @return Render
	 */
	public function setTemplateOrderGetFirst(): Render
	{
		$this->order = self::TEMPLATE_ORDER_GET_FIRST;
		return $this;
	}

	/**
	 * Template : get last from view for render
	 *
	 * @return Render
	 */
	public function setTemplateOrderGetLast(): Render
	{
		$this->order = self::TEMPLATE_ORDER_GET_LAST;
		return $this;
	}

	/**
	 * Force template setter
	 *
	 * When declare multiple views, the automatic detect can fail
	 * So you can override template here
	 *
	 * @param string $customTemplate
	 * @return Render
	 */
	public function setTemplate(string $customTemplate): Render
	{
		$this->customTemplate = trim($customTemplate, '/');
		return $this;
	}

	/**
	 * Get used template for render
	 *
	 * @return string
	 */
	public function getTemplate(): string
	{
		if($this->customTemplate) {
			return $this->customTemplate;
		}

		if($this->order === self::TEMPLATE_ORDER_GET_FIRST) {
			return $this->firstTemplate;
		}

		if($this->order === self::TEMPLATE_ORDER_GET_LAST) {
			return $this->lastTemplate;
		}

		return '';
	}

	/**
	 * Reset class
	 *
	 * @param bool $datas [optional] : Delete injected datas
	 * @param bool $globals [optional] : Delete injected globals
	 * @return Render
	 */
	public function reset(bool $datas = false, bool $globals = false): Render
	{
		if($datas) {
			$this->datas = [];
		}
		if($globals) {
			$this->globals = [];
		}
		$this->exceptions = [];
		$this->files = [];
		$this->customTemplate = '';
		$this->firstTemplate = '';
		$this->lastTemplate = '';
		$this->views = '';
		return $this;
	}

	/**
	 * Render layout
	 *
	 * @return string
	 */
	public function render(): string
	{
		# INIT
		$this->views = '';

		# Skip on error
		if($this->exceptions) {
			return '';
		}

		# Get template
		if(!$___t = $this->getTemplate()) {
			return '';
		}

		# Prepare global datas
		if ($this->globals) {
			extract($this->globals);
		}

		# Prepare datas
		if ($this->datas) {
			extract($this->datas);
		}

		# Buffer views
		if ($this->files) {
			ob_start();
			foreach ($this->files as $___f) {
				if($___f['path']) {
					require($___f['path']);
				}
			}
			$this->views = ob_get_contents();
			ob_end_clean();
		}

		# Load layout
		$___l = $this->directory . $___t . '/layout/layout.' . self::DEFAULT_EXTENSION;
		if ($___l && is_file($___l)) {
			ob_start();
			require($___l);
			$layout = ob_get_contents();
			ob_end_clean();
		}
		else {
			$e = new Exception("Layout file does not exist : $___l");
			$this->addException($e);
			$layout = '';
		}

		# Delete datas
		$this->reset(true);
		return $layout;
	}

	/**
	 * RENDER VIEW ONLY
	 *
	 * @param string $path
	 * @param array $datas [optional]
	 * @return string
	 */
	public function view(string $path, array $datas = []): string
	{
		# Prepare view
		$___p = $this->file($path)['path'] ?? '';
		if(!$___p) {
			return '';
		}

		# Prepare global datas
		if ($this->globals) {
			extract($this->globals);
		}

		# Prepare specific view datas
		if ($datas) {
			extract($datas);
		}

		# Buffer views
		ob_start();
		require($___p);
		$view = ob_get_contents();
		ob_end_clean();
		return $view;
	}
}