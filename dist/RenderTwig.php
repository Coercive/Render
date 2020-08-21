<?php
namespace Coercive\Utility\Render;

use Exception;
use Twig_Test;
use Twig_Filter;
use Twig_Function;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_ExtensionInterface;
use Twig_NodeVisitorInterface;
use Twig_TokenParserInterface;

/**
 * Render Twig
 *
 * @package Coercive\Utility\Render
 * @link https://github.com/Coercive/Render
 *
 * @author Anthony Moral <contact@coercive.fr>
 * @copyright 2020 Anthony Moral
 * @license MIT
 */
class RenderTwig
{
	const DEFAULT_EXTENSION = '.html.twig';

	/** @var string Root adn directories paths */
	private $root = '';
	private $directories = [];

	/** @var string Auto extension for view path and view path */
	private $extension = self::DEFAULT_EXTENSION;
	private $view = '';

	/** @var array Injected datas */
	private $globals = [];
	private $datas  = [];

	/** @var array Injected methods */
	private $filters = [];
	private $functions = [];
	private $tests = [];
	private $tokenParsers = [];
	private $nodeVisitors = [];
	private $extensions = [];

	/** @var array Twig environnement options */
	private $options = [
		'debug' => false,
		'charset' => 'utf-8',
		'cache' => false,
		'strict_variables' => false,
		'autoescape' => false
	];

	/**
	 * RenderTwig constructor.
	 *
	 * @param string $root [optional]
	 */
	public function __construct(string $root = null)
	{
		$this->root = $root;
	}

	/**
	 * ADD Directories paths
	 *
	 * @param array $directories : Root directories witch contain templates files
	 * @return RenderTwig
	 */
	public function addDirectories(array $directories): RenderTwig
	{
		foreach ($directories as $directory) {
			$this->addDirectory($directory);
		}
		return $this;
	}

	/**
	 * SETTER Directories paths
	 *
	 * @param array $directories : Root directories witch contain templates files
	 * @return RenderTwig
	 */
	public function setDirectories(array $directories): RenderTwig
	{
		$this->clearsDirectories();
		$this->addDirectories($directories);
		return $this;
	}

	/**
	 * Add Directory path
	 *
	 * @param string $directory : Root directory witch contain templates files
	 * @return RenderTwig
	 */
	public function addDirectory(string $directory): RenderTwig
	{
		$this->directories[] = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		return $this;
	}

	/**
	 * SETTER Directory path
	 *
	 * @param string $directory : Root directory witch contain templates files
	 * @return RenderTwig
	 */
	public function setDirectory(string $directory): RenderTwig
	{
		$this->clearsDirectories();
		$this->addDirectory($directory);
		return $this;
	}

	/**
	 * Delete directory list
	 *
	 * @return RenderTwig
	 */
	public function clearsDirectories(): RenderTwig
	{
		$this->directories = [];
		return $this;
	}

	/**
	 * ADD TWIG FILTER
	 *
	 * @param string $name
	 * @param callable|null $callable [optional]
	 * @param array $options [optional]
	 * @return RenderTwig
	 */
	public function addFilter(string $name, $callable = null, array $options = []): RenderTwig
	{
		$this->filters[$name] = new Twig_Filter($name, $callable, $options);
		return $this;
	}

	/**
	 * ADD TWIG FUNCTION
	 *
	 * @param string $name
	 * @param callable|null $callable [optional]
	 * @param array $options [optional]
	 * @return RenderTwig
	 */
	public function addFunction(string $name, $callable = null, array $options = []): RenderTwig
	{
		$this->functions[$name] = new Twig_Function($name, $callable, $options);
		return $this;
	}

	/**
	 * ADD TWIG TEST
	 *
	 * @param string $name
	 * @param callable|null $callable [optional]
	 * @param array $options [optional]
	 * @return RenderTwig
	 */
	public function addTest(string $name, $callable = null, array $options = []): RenderTwig
	{
		$this->tests[$name] = new Twig_Test($name, $callable, $options);
		return $this;
	}

	/**
	 * ADD TWIG TAG
	 *
	 * @param Twig_TokenParserInterface $parser
	 * @return RenderTwig
	 */
	public function addTokenParser(Twig_TokenParserInterface $parser): RenderTwig
	{
		$this->tokenParsers[] = $parser;
		return $this;
	}

	/**
	 * ADD TWIG NODE
	 *
	 * @param Twig_NodeVisitorInterface $visitor
	 * @return RenderTwig
	 */
	public function addNodeVisitor(Twig_NodeVisitorInterface $visitor): RenderTwig
	{
		$this->nodeVisitors[] = $visitor;
		return $this;
	}

	/**
	 * ADD TWIG NODE
	 *
	 * @param Twig_ExtensionInterface $extension
	 * @return RenderTwig
	 */
	public function addExtension(Twig_ExtensionInterface $extension): RenderTwig
	{
		$this->extensions[] = $extension;
		return $this;
	}

    /**
     * ADD TWIG GLOBALS VAR
     *
     * @param array $datas
     * @return RenderTwig
     */
    public function addGlobals(array $datas): RenderTwig
    {
	    foreach ($datas as $name => $global) {
		    $this->globals[$name] = $global;
	    }
        return $this;
    }

	/**
	 * SETTER Datas
	 *
	 * @param array $datas
	 * @return RenderTwig
	 */
	public function setDatas(array $datas): RenderTwig
	{
		$this->datas = $datas;
		return $this;
	}

	/**
	 * SETTER path
	 *
	 * @param string $extension [optional]
	 * @return RenderTwig
	 */
	public function setFileExtension(string $extension = self::DEFAULT_EXTENSION): RenderTwig
	{
		$this->extension = $extension;
		return $this;
	}

	/**
	 * SETTER path
	 *
	 * @param string $view
	 * @return RenderTwig
	 */
	public function setPath(string $view): RenderTwig
	{
		# Ajoute automatiquement l'exention en fin de nom de fichier
		if($this->extension && !strpos($view, $this->extension)) {
			$view .= $this->extension;
		}

		# Set
		$this->view = $view;
		return $this;
	}

	/**
	 * SETTER twig environnement options
	 *
	 * @param array $options
	 * @return RenderTwig
	 */
	public function setOptions(array $options): RenderTwig
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * Setter Debug Mode
	 *
	 * @param bool $state
	 * @return RenderTwig
	 */
	public function setDebug(bool $state): RenderTwig
	{
		$this->options['debug'] = $state;
		return $this;
	}

	/**
	 * Setter Charset
	 *
	 * @param string $charset
	 * @return RenderTwig
	 */
	public function setCharset(string $charset): RenderTwig
	{
		$this->options['charset'] = $charset;
		return $this;
	}

	/**
	 * Setter Cache
	 *
	 * @param bool $state
	 * @param string $path
	 * @param int $mode [optional] - octale
	 * @return RenderTwig
	 * @throws Exception
	 */
	public function setCache(bool $state, string $path, $mode = 0777): RenderTwig
	{
		# Set the dirpath
		$dest = realpath($path);
		if (!$dest || !is_dir($dest)) {
			# Create directory
			if (!@mkdir($path, $mode, true)) {
				throw new Exception("Can't create cache directory : $path");
			}
			$dest = realpath($path);
		}

		# Active le cache avec la destination fournie (ou false si échec realpath)
		$this->options['cache'] = $state ? $dest : false;
		return $this;
	}

	/**
	 * Clear twig cache directory
	 *
	 * @return $this
	 */
	public function clearCache(): RenderTwig
	{
		if($this->options['cache'] && is_dir($this->options['cache'])) {
			@system('rm -rf ' . escapeshellarg($this->options['cache']));
		}
		return $this;
	}

	/**
	 * Setter Strict Variables
	 *
	 * @param bool $state
	 * @return RenderTwig
	 */
	public function setStrictVariables(bool $state): RenderTwig
	{
		$this->options['strict_variables'] = $state;
		return $this;
	}

	/**
	 * Setter Autoescape
	 *
	 * @param bool $state
	 * @return RenderTwig
	 */
	public function setAutoescape(bool $state): RenderTwig
	{
		$this->options['autoescape'] = $state;
		return $this;
	}

	/**
	 * Render Template
     *
     * @return string
     * @throws Exception
	 */
	public function render(): string
	{
		# Prepare Twig loader
		$loader = new Twig_Loader_Filesystem($this->directories, $this->root);

		# Prepare Twig environnement with options
		$twig = new Twig_Environment($loader, $this->options);

		# Ajout des variables globales
		foreach ($this->globals as $name => $var) {
			$twig->addGlobal($name, $var);
		}

		# Ajout des filtres
		foreach ($this->filters as $filter) {
			$twig->addFilter($filter);
		}

		# Ajout des fonctions
		foreach ($this->functions as $function) {
			$twig->addFunction($function);
		}

		# Ajout des tests
		foreach ($this->tests as $test) {
			$twig->addTest($test);
		}

		# Ajout des tags
		foreach ($this->tokenParsers as $parser) {
			$twig->addTokenParser($parser);
		}

		# Ajout des noeuds template
		foreach ($this->nodeVisitors as $visitor) {
			$twig->addNodeVisitor($visitor);
		}

		# Ajout des extensions twig
		foreach ($this->extensions as $extension) {
			$twig->addExtension($extension);
		}

		# Return the prepared template
		return $twig->render($this->view, $this->datas);
	}
}
