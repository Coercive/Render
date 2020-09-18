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
 * @copyright 2020 Anthony Moral
 * @license MIT
 */
class Render
{
	const DEFAULT_EXTENSION = 'php';
	const DEFAULT_TEMPLATE = 'default';

	/** @var string Root Paths */
	private
		$directory = '',
		$template = '',
		$forceTemplate = '';

	/** @var array Injected paths/datas */
	private
		$globals = [],
		$datas = [],
		$files = [];

    /** @var string Processed Views Content */
    private
	    $views = '',
	    $layout = '';

    /** @var Exception[] List of errors for debug */
    private $exceptions = [];

    /** @var Closure customer debug function that get Exception as parameter like : function(Exception $e) { ... } */
    private $closure = null;

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
			$this->template = self::DEFAULT_TEMPLATE;
		}
		else {
			$this->template = $matches['template'];
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
            'template' => $this->template,
            'extension' => $extension
        ];
    }

	/**
	 * Add Exception for external debug handler
	 *
	 * @param Exception $e
	 * @return Render
	 */
	private function addException(Exception $e): Render
	{
		$this->exceptions[] = $e;
		if(null !== $this->closure) {
			($this->closure)($e);
		}
		return $this;
	}

	/**
	 * Render constructor.
     *
     * @param string $directory : Root directory witch contain templates
	 */
	public function __construct(string $directory)
	{
		$this->directory = rtrim($directory, '/') . '/';
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
		$this->files = [$this->file($path)];
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
		$this->files[] = $this->file($path);
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
	 * Force template setter
	 *
	 * When declare multiple views, the automatic detect can fail
	 * So you can override template here
	 *
	 * @param string $template
	 * @return Render
	 */
	public function forceTemplate(string $template): Render
	{
		$this->forceTemplate = trim($template, '/');
		return $this;
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
		$this->template = '';
		$this->views = '';
		$this->forceTemplate = '';
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
		$this->layout = '';
		$this->views = '';

		# Skip on error
		if($this->exceptions) {
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
			foreach ($this->files as $file) {
				if($file['path']) {
					require($file['path']);
				}
			}
			$this->views = ob_get_contents();
			ob_end_clean();
		}

		# Load layout
		$template = $this->forceTemplate ?: $this->template;
		$layoutPath = $this->directory . $template . '/layout/layout.' . self::DEFAULT_EXTENSION;
		$layout = realpath($layoutPath);
		if (is_file($layout)) {
			ob_start();
			require($layout);
			$this->layout = ob_get_contents();
			ob_end_clean();
		}
		else {
		    $e = new Exception("Layout file does not exist : $layoutPath");
		    $this->addException($e);
			$this->layout = '';
		}

		# Delete datas
		$this->reset(true);
		return $this->layout;
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
		$path = $this->file($path)['path'] ?? '';
		if(!$path) {
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
        require($path);
        $view = ob_get_contents();
        ob_end_clean();
        return $view;
    }
}
