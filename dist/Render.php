<?php
namespace Coercive\Utility\Render;

use Exception;

/**
 * Render
 * PHP Version 	7
 *
 * @version		Beta
 * @package		Coercive\Utility\Render
 * @link		@link https://github.com/Coercive/Render
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2016 - 2017 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class Render {

	const DEFAUT_EXTENSION = 'php';

	/** @var string Root Paths */
	private $_sDirectory = '',
			$_sTemplate = '';

	/** @var array Injected paths/datas */
	private $_aPaths = [],
			$_aGlobalDatas = [],
			$_aDatas = [],
			$_aFiles = [];

    /** @var string Processed Views Content */
    private $_sViews = '',
            $_sLayout = '';

	/**
	 * PURGE
	 *
	 * @param bool $bData [optional] : Delete injected datas
     * @return Render
	 */
	private function _purge($bData = false) {
		if($bData) { $this->_aGlobalDatas = $this->_aDatas = []; }
		$this->_aPaths = $this->_aFiles = [];
        return $this;
	}

    /**
     * PREPARE VIEW PATH
     *
     * @param string $sViewPath
     * @return array
     * @throws Exception
     */
	private function _prepareViewPath($sViewPath) {

        # Delete spaces and start/end slashes
        $sPath = trim(str_replace(' ', '', $sViewPath), '/');

        # TEMPLATE
        preg_match('`^(?P<template>[a-z0-9_-]*)/.*`i', $sPath, $aMatches);
        if(empty($aMatches['template']) || !is_dir($this->_sDirectory.$aMatches['template'])) {
            throw new Exception('No template found, path : ' . $sPath);
        }
        $sTemplate = $aMatches['template'];

        # EXTENSION
        preg_match('`\.(?P<extension>[a-z0-9]+)$`i', $sPath, $aMatches);
        $sExtension = empty($aMatches['extension']) ? self::DEFAUT_EXTENSION : strtolower($aMatches['extension']);
        $sAddExt = empty($aMatches['extension']) ? '.' . self::DEFAUT_EXTENSION : '';

        # VIEW
        $sFile = realpath($this->_sDirectory . $sPath . $sAddExt);
        if (!$sFile || !is_readable($sFile)) {
            throw new Exception("View file not found : {$this->_sDirectory}{$sPath}{$sAddExt}");
        }

        # FILE
        return [
            'path' => $sFile,
            'template' => $sTemplate,
            'extension' => $sExtension
        ];

    }

	/**
	 * Render constructor.
     *
     * @param string $sRootDirectory : Root directory witch contain templates
	 */
	public function __construct($sRootDirectory) {
		$this->_sDirectory = rtrim($sRootDirectory, '/') . '/';
	}

    /**
     * Views content
     *
     * @return string
     */
    public function getViews() {
        return $this->_sViews;
    }

    /**
     * SETTER Global Datas
     *
     * @param array $aDatas
     * @return Render
     * @throws Exception
     */
    public function setGlobalDatas($aDatas) {
        if(!is_array($aDatas)) { throw new Exception('Datas must be array type'); }
        $this->_aGlobalDatas = $aDatas ?: [];
        return $this;
    }

	/**
	 * SETTER Datas
	 *
	 * @param array $aDatas
	 * @return Render
     * @throws Exception
	 */
	public function setDatas($aDatas) {
        if(!is_array($aDatas)) { throw new Exception('Datas must be array type'); }
		$this->_aDatas = $aDatas;
		return $this;
	}

	/**
	 * SETTER path
	 *
	 * @param mixed $mPath
	 * @return Render
     * @throws Exception
	 */
	public function setPath($mPath) {

		if(!$mPath) { return $this; }
		$this->_aPaths = (array) $mPath;

		# Prepare file path
		foreach ($this->_aPaths as $sPath) {
			$this->_aFiles[] = $this->_prepareViewPath($sPath);
		}

		return $this;
	}

	/**
	 * RENDER LAYOUT
     *
     * @return string
     * @throws Exception
	 */
	public function render() {

		# INIT
		$this->_sTemplate = '';
		$this->_sViews = '';

        # Prepare global datas
        if ($this->_aGlobalDatas) { extract($this->_aGlobalDatas); }

		# Prepare datas
		if ($this->_aDatas) { extract($this->_aDatas); }

		# Buffer views
		if ($this->_aFiles) {
			ob_start();
			foreach ($this->_aFiles as $aFile) {
				if(!$this->_sTemplate) $this->_sTemplate = $aFile['template'];
				require($aFile['path']);
			}
			$this->_sViews = ob_get_contents();
			ob_end_clean();
		}

		# Load layout
		$sLayout = realpath("{$this->_sDirectory}/{$this->_sTemplate}/layout/layout.php");
		if (is_readable($sLayout)) {
			ob_start();
			require_once($sLayout);
			$this->_sLayout = ob_get_contents();
			ob_end_clean();
		} else {
		    throw new Exception("Demande de rendu d'un layout inexistant : {$this->_sDirectory}/{$this->_sTemplate}/layout/layout.php");
		}

		# Delete datas
		$this->_purge(true);

		return $this->_sLayout;

	}

    /**
     * RENDER VIEW ONLY
     *
     * @param string $sViewPath
     * @param array $aDatas
     * @return string
     * @throws Exception
     */
	public function view($sViewPath, $aDatas = []) {

        # Prepare view
        $sPath = $this->_prepareViewPath($sViewPath)['path'];

        # Verify datas
        if(!is_array($aDatas)) {
            throw new Exception('Datas must be array');
        }

        # Prepare global datas
        if ($this->_aGlobalDatas) { extract($this->_aGlobalDatas); }

        # Prepare specific view datas
        if ($aDatas) { extract($aDatas); }

        # Buffer views
        ob_start();
        require($sPath);
        $sView = ob_get_contents();
        ob_end_clean();

        return $sView;

    }
}