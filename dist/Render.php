<?php
namespace Coercive\Utility\Render;

use Exception;

/**
 * Render
 *
 * @package		Coercive\Utility\Render
 * @link		https://github.com/Coercive/Render
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   2018 Anthony Moral
 * @license 	MIT
 */
class Render
{
	const DEFAUT_EXTENSION = 'php';

	/** @var string Root Paths */
	private
		$directory = '',
		$template = '',
		$forceTemplate = '';

	/** @var array Injected paths/datas */
	private
		$paths = [],
		$globals = [],
		$datas = [],
		$files = [];

    /** @var string Processed Views Content */
    private
	    $views = '',
	    $layout = '';

	/**
	 * PURGE
	 *
	 * @param bool $data [optional] : Delete injected datas
     * @return Render
	 */
	private function _purge(bool $data = false): Render
	{
		if($data) { $this->globals = $this->datas = []; }
		$this->paths = $this->files = [];
		$this->forceTemplate = '';
        return $this;
	}

    /**
     * PREPARE VIEW PATH
     *
     * @param string $path
     * @return array
     * @throws Exception
     */
	private function _prepareViewPath($path): array
	{
        # Delete spaces and start/end slashes
        $path = trim(str_replace(' ', '', $path), '/');

        # TEMPLATE
        preg_match('`^(?P<template>[a-z0-9_-]*)/.*`i', $path, $matches);
        if(empty($matches['template']) || !is_dir($this->directory. $matches['template'])) {
            throw new Exception('No template found, path : ' . $path);
        }
        $template = $matches['template'];

        # EXTENSION
        preg_match('`\.(?P<extension>[a-z0-9]+)$`i', $path, $matches);
        $extension = empty($matches['extension']) ? self::DEFAUT_EXTENSION : strtolower($matches['extension']);
        $addExt = empty($matches['extension']) ? '.' . self::DEFAUT_EXTENSION : '';

        # VIEW
        $file = realpath($this->directory . $path . $addExt);
        if (!$file || !is_readable($file)) {
            throw new Exception("View file not found : {$this->directory}{$path}{$addExt}");
        }

        # FILE
        return [
            'path' => $file,
            'template' => $template,
            'extension' => $extension
        ];
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
     * SETTER Global Datas
     *
     * @param array $datas
     * @return Render
     * @throws Exception
     */
    public function setGlobalDatas(array $datas): Render
    {
        if(!is_array($datas)) { throw new Exception('Datas must be array type'); }
        $this->globals = $datas ?: [];
        return $this;
    }

	/**
	 * SETTER Datas
	 *
	 * @param array $datas
	 * @return Render
     * @throws Exception
	 */
	public function setDatas(array $datas): Render
	{
        if(!is_array($datas)) { throw new Exception('Datas must be array type'); }
		$this->datas = $datas;
		return $this;
	}

	/**
	 * SETTER path
	 *
	 * @param mixed $paths
	 * @return Render
     * @throws Exception
	 */
	public function setPath($paths): Render
	{
		if(!$paths) { return $this; }
		$this->paths = (array) $paths;

		# Prepare file path
		foreach ($this->paths as $path) {
			$this->files[] = $this->_prepareViewPath($path);
		}

		return $this;
	}

	/**
	 * FORCE TEMPLATE SETTER
	 *
	 * @param string $template
	 * @return Render
	 * @throws Exception
	 */
	public function forceTemplate(string $template): Render
	{
		if(!$template || !is_string($template)) { throw new Exception('Template empty or not string type'); }
		$this->forceTemplate = trim($template, '/');
		return $this;
	}

	/**
	 * RENDER LAYOUT
     *
     * @return string
     * @throws Exception
	 */
	public function render(): string
	{
		# INIT
		$this->template = '';
		$this->views = '';

        # Prepare global datas
        if ($this->globals) { extract($this->globals); }

		# Prepare datas
		if ($this->datas) { extract($this->datas); }

		# Buffer views
		if ($this->files) {
			ob_start();
			foreach ($this->files as $file) {
				if(!$this->template && $file['template']) $this->template = $file['template'];
				require($file['path']);
			}
			$this->views = ob_get_contents();
			ob_end_clean();
		}

		# Load layout
		$template = $this->forceTemplate ?: $this->template;
		$layoutPath = "{$this->directory}/{$template}/layout/layout." . self::DEFAUT_EXTENSION;
		$layout = realpath($layoutPath);
		if (is_readable($layout)) {
			ob_start();
			require($layout);
			$this->layout = ob_get_contents();
			ob_end_clean();
		} else {
		    throw new Exception("Demande de rendu d'un layout inexistant : {$layoutPath}");
		}

		# Delete datas
		$this->_purge(true);

		return $this->layout;
	}

    /**
     * RENDER VIEW ONLY
     *
     * @param string $path
     * @param array  $datas
     * @return string
     * @throws Exception
     */
	public function view(string $path, $datas = []): string
	{
        # Prepare view
		$path = $this->_prepareViewPath($path)['path'];

        # Verify datas
        if(!is_array($datas)) {
            throw new Exception('Datas must be array');
        }

        # Prepare global datas
        if ($this->globals) { extract($this->globals); }

        # Prepare specific view datas
        if ($datas) { extract($datas); }

        # Buffer views
        ob_start();
        require($path);
        $view = ob_get_contents();
        ob_end_clean();
        return $view;
    }
}
