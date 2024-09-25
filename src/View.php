<?php

/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 21.03.2017
 * Time: 15:10
 */

namespace Schettke\TwigForWordpress;

use Twig\Extensions\IntlExtension;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;
use Twig_Loader_Preprocessor;

class View
{
    /** @var  Twig_Environment $twig */
    private $twig;

    /** @var array Variables injected into twig template */
    private $context;

    /** @var  boolean indicates if this view is ready to be rendered */
    private $isPrepared = false;

    /** @var string absolute path (with trailing slash) to the root folder of the plugin implementing this component */
    private $pluginBasePath;

    /** @var string defined via config file */
    private $viewBaseDir;
    private $viewCssFilename;
    private $viewJsFilename;
    private $viewLazyLoading;
    private $debugMode;

    /** @var  ViewComponentInterface[] Components to include in the rendering process */
    private $viewComponents;

    /** @var string filename of the view to render */
    private $viewName = 'single.twig';

    /** @var null|\Exception may contain an exception form the viewLoader to print onscreen */
    private $error;

    /**
     * @param string $scriptHandle name of the handle for wp_enqueue_script. will be suffixed with "_js". The
     * corresponding javascript object (injected with wp_localized_script) will be suffixed with "_ajax".
     * wp_enqueue style also uses this handle suffixing it with "_css" or "_base_css".
     * If this View was created by the ViewLoader, the scriptHandle will automatically be set to the post type slug.
     * if no handle is set, uniqid() will be used.
     */
    private $scriptHandle = '';

    /**
     * View constructor.
     *
     * @param $pluginBasePath string absolute path to the root folder of the wordpress-plugin implementing this component
     * @param $viewBaseDir string|false relative path for the twig templates. defaults to "base" (folder views/base)
     * @param $viewCssFilename string|false filename of the additional css-file to load (should lay in public/css/)
     * @param $viewJsFilename string|false filename of the js file to load (should lay in public/js/)
     * @param $debugMode bool force twig to run in debug mode (cache disabled, strict variables)
     * @param bool $viewLazyLoading enqueue js image lazy loader
     */
    public function __construct($pluginBasePath, $viewBaseDir, $viewCssFilename, $viewJsFilename, $debugMode = false, $viewLazyLoading = true)
    {
        if (false !== $viewBaseDir){
            $viewBaseDir = ltrim($viewBaseDir, '/');
            if(strpos($viewBaseDir, 'views/') !== 0) {
                $viewBaseDir = 'views/' . $viewBaseDir;
            }
        }
        if (false !== $viewCssFilename) {
            $viewCssFilename = ltrim($viewCssFilename, '/');
            if(strpos($viewCssFilename, 'public/css/') !== 0) {
                $viewCssFilename = 'public/css/'.$viewCssFilename;
            }
        }
        if (false !== $viewJsFilename) {
            $viewJsFilename = ltrim($viewJsFilename, '/');
            if(strpos($viewJsFilename, 'public/js/') !== 0) {
                $viewJsFilename = 'public/js/'.$viewJsFilename;
            }
        }

        //add trailing slash to basePath
        if('/' !== substr($pluginBasePath, -1)) {
            $pluginBasePath .= '/';
        }

        $this->setScriptHandle(uniqid());
        $this->pluginBasePath  = $pluginBasePath;
        $this->viewBaseDir     = $viewBaseDir;
        $this->viewCssFilename = $viewCssFilename;
        $this->viewJsFilename  = $viewJsFilename;
        $this->debugMode       = $debugMode;
        $this->viewLazyLoading = $viewLazyLoading;
    }

    /**
     * @return string absolute path (with trailing slash) to the root folder of the plugin implementing this component
     */
    public final function getPluginBasePath()
    {
        return $this->pluginBasePath;
    }

    /**
     * @return string name of the template file to load, p.e. "single.twig"
     */
    public final function getViewName()
    {
        return $this->viewName;
    }

    /**
     * @param string $viewName name of the template file to load, p.e. "single.twig"
     */
    public final function setViewName($viewName)
    {
        $this->viewName = $viewName;
    }

    public final function setViewComponents($viewComponents)
    {
        $this->viewComponents = $viewComponents;
    }

    private function prepare()
    {
        if ( ! $this->isPrepared) {
            $this->loadTwig();
            if(!empty($this->viewComponents)) {
                foreach ($this->viewComponents as $component) {
                    $component->init();
                }
            }

            $this->enqueueScripts();

            if($this->viewLazyLoading) {
                $this->enqueueLazyLoadingScript();
            }
            $this->addToContext('lazyLoadingEnabled', ($this->viewLazyLoading));

            $this->enqueueStyles();
            $this->enqueueViewComponents();
        }
        $this->isPrepared = true;
    }

    private function loadTwig()
    {
        try {
            $loader = new Twig_Loader_Filesystem($this->getTemplatePaths());
        } catch (\Exception $twig_error_loader) {
            trigger_error($twig_error_loader->getMessage(), E_USER_WARNING);
            trigger_error("Ignoring config template_path and loading base template instead.", E_USER_WARNING);

            $loader = new Twig_Loader_Filesystem($this->pluginBasePath.'views/base');
        }

        $twigCacheDir = ($this->debugMode) ? false : $this->pluginBasePath.'cache/twig';

        //needs the "TwigLazyImageFunction" !
        $twigLoaderPreprocessor = new Twig_Loader_Preprocessor($loader, [new TwigPreprocessor($this->viewLazyLoading), 'process']);

        $this->twig = new Twig_Environment($twigLoaderPreprocessor, array(
            'cache'            => $twigCacheDir,
            'debug'            => $this->debugMode,
            'strict_variables' => $this->debugMode,
            'autoescape'       => false,
        ));

        $this->twig->addFunction(new TwigLazyImageFunction($this->pluginBasePath));

        if ($this->debugMode) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }
      
      	// allow localized dates (needs php-intl extension)
        if(extension_loaded('intl')) {
            $this->twig->addExtension(new IntlExtension());
        }

        $this->addWpFunctionsToTwig();
        $this->addAssetFunctionToTwig();
        $this->twig->addExtension(new TwigFilters());
    }

    private function getTemplatePaths()
    {
        if ($this->viewBaseDir && 'views/base' !== $this->viewBaseDir) {
            $result[] = $this->pluginBasePath.$this->viewBaseDir;
        }
        $result[] = $this->pluginBasePath.'views/base';

        return $result;
    }

    private function addWpFunctionsToTwig()
    {
        $proxy               = new TwigWpProxy();
        $this->addToContext('wp', $proxy);
    }

    private function addAssetFunctionToTwig() {
        $this->twig->addFunction(new TwigAssetFunction($this->pluginBasePath));
    }

    /**
     * Here we enqueue "view.js_file" from the config. You might overwrite this method
     * (preferably with a call to super()) to enqueue additional scripts.
     */
    protected function enqueueScripts()
    {
        if(did_action('wp_enqueue_scripts')) {
            $this->enqueueScriptFunction();
        }
        else {
            add_action('wp_enqueue_scripts', [$this, 'enqueueScriptFunction'] ,PHP_INT_MAX);
        }
    }

    /**
     * Hooked into wordpress, do not call directly.
     */
    public function enqueueScriptFunction()
    {
        // add optional dependencies (to ensure our scripts / styles are loaded after them)
        $deps = [];
        if (wp_scripts()->query('jquery') instanceof \_WP_Dependency) {
            $deps[] = 'jquery';
        }
        if (wp_scripts()->query('bootstrap') instanceof \_WP_Dependency) {
            $deps[] = 'bootstrap';
        }

        //do not load js file if explicitly disabled in config (js_path = false)
        if (false !== $this->viewJsFilename) {
            //trick plugins_url() into thinking we are in the plugins base directory
            $baseDir = $this->pluginBasePath . 'index.php';

            //load default if config entry missing
            if (null === $this->viewJsFilename || '' === $this->viewJsFilename) {
                $this->viewJsFilename = 'public/js/default.js';
            }

            $url = plugins_url($this->viewJsFilename, $baseDir);
            wp_enqueue_script($this->scriptHandle . '_js', $url, $deps, '1.0', true);
            wp_localize_script($this->scriptHandle . '_js', $this->scriptHandle . '_ajax',
                array('data' => array('ajaxurl' => admin_url('admin-ajax.php'))));
        };
    }

    private function enqueueLazyLoadingScript() {
        $url = plugins_url('lazysizes.min.js', dirname(dirname(__FILE__)) . '/public/js/nofile.php');

        if(did_action('wp_enqueue_scripts')) {
            wp_enqueue_script('schettke-twig-for-wordpress-lazyloading', $url , [], '1.0', false);
        }
        else {
            add_action('wp_enqueue_scripts', function() use ($url) {
                wp_enqueue_script('schettke-twig-for-wordpress-lazyloading', $url , [], '1.0', false);
            });
        }
    }

    /**
     * @param string $scriptHandle name of the handle for wp_enqueue_script. will be suffixed with "_js". The
     * corresponding javascript object (injected with wp_localized_script) will be suffixed with "_ajax".
     * wp_enqueue style also uses this handle suffixing it with "_css" or "_base_css".
     * If this View was created by the ViewLoader, the scriptHandle will automatically be set to the post type slug.
     * if no handle is set, uniqid() will be used.
     */
    public final function setScriptHandle($scriptHandle)
    {
        $this->scriptHandle = $scriptHandle;
    }

    /**
     * Here we enqueue the "public/css/base.css" and the "view.css_file" from the config. You might overwrite this
     * method (preferably with a call to super()) to enqueue additional styles.
     */
    protected function enqueueStyles()
    {
        if(did_action('wp_enqueue_scripts')) {
            $this->enqueueStylesFunction();
        }
        else {
            add_action('wp_enqueue_scripts', [$this, 'enqueueStylesFunction'], PHP_INT_MAX);
        }
    }

    /**
     * Hooked into wordpress, do not call directly.
     */
    public function enqueueStylesFunction() {
        // add optional dependencies (to ensure our scripts / styles are loaded after them)
        $deps = [];
        if(wp_styles()->query('bootstrap') instanceof \_WP_Dependency) {
            $deps[] = 'bootstrap';
        }

        //trick plugins_url() into thinking we are in the plugins base directory
        $baseDir = $this->pluginBasePath.'index.php';

        //load public/css/base.css if it exists
        $baseCssPath    = 'public/css/base.css';
        $baseCssAbsPath = $this->pluginBasePath.$baseCssPath;
        if (file_exists($baseCssAbsPath)) {
            $baseCssUrl = plugins_url($baseCssPath, $baseDir);
            wp_enqueue_style($this->scriptHandle.'_base_css', $baseCssUrl, $deps, '1.0');
        }

        //load additional css file from config if not explicitly disabled (css_path = false)
        if (false !== $this->viewCssFilename) {
            $extraCssAbsPath = $this->pluginBasePath.$this->viewCssFilename;
            if (file_exists($extraCssAbsPath)) {
                $extraCssUrl = plugins_url($this->viewCssFilename, $baseDir);
                wp_enqueue_style($this->scriptHandle.'_css', $extraCssUrl, $deps, '1.0');
            } else {
                trigger_error('Could not load css file from config: '.$extraCssAbsPath.' does not exist.',
                    E_USER_WARNING);
            }
        }
    }

    /**
     * Add a variable to twig, so you can use it in templates with {{ key }}.
     *
     * Variables can be of any type (strings, objects, arrays, functions...). Access to properties of objects or
     * fields of arrays is possible with the dot-notation, p.e. {{ key.myfield }}. functions can be called
     * with {{ key(parameters) }}.
     *
     * @param $key string key under which this variable should be accessible
     * @param $val string|int|bool|array|object|callable the variable to inject
     */
    public final function addToContext($key, $val)
    {
        $this->context[$key] = $val;
    }

    private function enqueueViewComponents()
    {
        if(!empty($this->viewComponents)) {
            foreach ($this->viewComponents as $viewComponent) {
                $contextIdentifier = $viewComponent->getContextIdentifier();
                if (null !== $contextIdentifier) {
                    $this->addToContext($contextIdentifier, $viewComponent->getContext());
                }
            }
        }
    }

    public final function render()
    {
        echo $this->getOutputAsString();
    }

    public final function getOutputAsString()
    {
        $this->prepare();
        try {
            if($this->error) {
                throw($this->error);
            }
            return $this->twig->render($this->viewName, $this->context);
        }
        catch (\Exception $e) {
            $errorMsg = '<h3>Could not render twig template "' . $this->viewName . '"</h3>';
            $errorMsg .= 'Error : ' . $e->getMessage() . '<br />';
            $errorMsg .= 'File&nbsp;&nbsp; : ' . $e->getFile() . ':' . $e->getLine();
            return $errorMsg;
        }
    }

    /**
     * If an exception is passed to this view it will print an error message with details instead of the template.
     * @param \Exception $error
     */
    public final function setError(\Exception $error)
    {
        $this->error = $error;
    }


}
