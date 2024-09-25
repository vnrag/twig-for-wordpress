<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 09.10.2017
 * Time: 20:12
 */

namespace Schettke\TwigForWordpress\ViewComponents;

use \MatthiasMullie\Minify\CSS;
use \MatthiasMullie\Minify\JS;

/**
 * Concats and minimizes css and js files enqueued via wp_enqueue_script and wp_enqueue_style.
 */
class Minifier implements \Schettke\TwigForWordpress\ViewComponentInterface
{
    /** @var string relative root path to merge and minify, e.g. "wp-content/plugins/schettke-downloads-v2"
     * will proccess all css/js files lying in this directory or one of it's subdirectories. */
    protected $scope;

    /** @var string abspath to the plugin root dir */
    protected $rootDir;

    /** @var string unique handle for the minified files */
    protected $handle;

    /** @var  string[] dependencies of the js scripts */
    protected $jsDeps = [];
    /** @var  string[] dependencies of the css files */
    protected $cssDeps = [];

    /** @var  string path where to place the minified css file, relative to rootDir.
     * Injected in the contructor, e.g. "public/css/schettke_downloads.min.css" */
    protected $relCssOutputPath;
    /** @var  string path where to place the minified js file, relative to rootDir. */
    protected $relJsOutputPath;

    /** @var  string absolute path to the minified css file */
    protected $jsOutputFilePath;
    /** @var  string absolute path to the minified js file */
    protected $cssOutputFilePath;

    /** @var  string url to the minified css file */
    protected $jsOutputFileUrl;
    /** @var  string url to the minified js file */
    protected $cssOutputFileUrl;

    /** @var string[]|false array of script handles to deregister or false for nop */
    protected $deregisterScripts = [];

    /** @var  JS */
    protected $jsMinifier;
    /** @var  CSS */
    protected $cssMinifier;

    /** @var bool $useCache if true only the script/style already on disk will be enqueued */
    protected $useCache;

    /** @var bool this component only hooks into wordpress if it's enabled */
    protected $isEnabled;

    /** @var  string[] js-data injected via wp_localize_script */
    protected $extras;

    /**
     * Minify constructor.
     *
     * @param $relCssOutputPath
     * @param $relJsOutputPath
     * @param $deregisterScripts
     * @param bool $useCache if true only the script/style already on disk will be enqueued and script-changes WILL
     * NOT BE PROCESSED
     * @param bool $isEnabled this component only hooks into wordpress if it's enabled
     */
    public function __construct(
        $relCssOutputPath,
        $relJsOutputPath,
        $deregisterScripts,
        $useCache = false,
        $isEnabled = true
    ) {
        if ( ! $isEnabled) {
            return;
        }
        $this->isEnabled        = $isEnabled;
        $this->relCssOutputPath = $relCssOutputPath;
        $this->relJsOutputPath  = $relJsOutputPath;
        $this->useCache         = $useCache;
        if (false !== $deregisterScripts) {
            $this->deregisterScripts = $deregisterScripts;
        }

        $this->rootDir = dirname(__FILE__, 6);
        $this->scope   = str_replace(ABSPATH, '', $this->rootDir);

        $this->handle = 'schettke_minifier_'.uniqid();

        $this->cssOutputFilePath = $this->rootDir.'/'.$relCssOutputPath;
        $this->jsOutputFilePath  = $this->rootDir.'/'.$relJsOutputPath;

        if ( ! $this->useCache) {
            if (file_exists($this->cssOutputFilePath)) {
                unlink($this->cssOutputFilePath);
            }
            if (file_exists($this->jsOutputFilePath)) {
                unlink($this->jsOutputFilePath);
            }
        }

        if ( ! file_exists($this->cssOutputFilePath)) {
            $this->useCache = false;
            $this->createOutputFolder($this->cssOutputFilePath);
        }
        if ( ! file_exists($this->jsOutputFilePath)) {
            $this->useCache = false;
            $this->createOutputFolder($this->jsOutputFilePath);
        }

        if ( ! $this->useCache) {
            $this->jsMinifier  = new JS();
            $this->cssMinifier = new CSS();
        }

        $this->cssOutputFileUrl = plugins_url($this->relCssOutputPath, $this->rootDir.'/notafile');
        $this->jsOutputFileUrl  = plugins_url($this->relJsOutputPath, $this->rootDir.'/notafile');
    }

    protected function createOutputFolder($outputFilePath)
    {
        $pathinfo  = pathinfo($outputFilePath);
        $outputDir = $pathinfo['dirname'];
        if ( ! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        if ( ! file_exists($outputDir.'/.htaccess')) {
            file_put_contents($outputDir.'/.htaccess', 'allow from all');
        }
    }

    public function init()
    {
        if ($this->isEnabled) {
            add_action('wp_print_styles', array($this, 'minifyScripts'), 100);
        }
    }

    public function minifyScripts()
    {
        /** @var \WP_Dependencies */
        global $wp_styles;
        /** @var \WP_Dependencies */
        global $wp_scripts;

        if ($wp_scripts instanceof \WP_Dependencies) {
            foreach ($wp_scripts->registered as $script) {
                /** @var \_WP_Dependency $script */

                if (in_array($script->handle, $this->deregisterScripts)) {
                    wp_deregister_script($script->handle);
                    continue;
                }

                if (false !== strpos($script->src, $this->scope)) {
                    $this->processScript($script);
                    wp_deregister_script($script->handle);
                }
            }
        }

        if ($wp_styles instanceof \WP_Dependencies) {
            foreach ($wp_styles->registered as $style) {
                /** @var \_WP_Dependency $script */
                if (false !== strpos($style->src, $this->scope)) {
                    if ( ! $this->useCache) {
                        $this->processStyle($style);
                    }
                    wp_deregister_style($style->handle);
                }
            }
        }

        $this->printExtras();

        if ( ! $this->useCache) {
            $this->minify();
        }
        $this->enqueueMinified();

        remove_action('wp_print_styles', array($this, 'minifyScripts'), 100);
    }

    protected function processScript(\_WP_Dependency $script)
    {
        $absPath = $this->getAbsPathFromUrl($script->src);
        if (null !== $absPath) {
            if ( ! empty($script->extra)) {
                foreach ($script->extra as $plainJs) {
                    $this->extras[] = $plainJs;
                }
            }
            if ( ! $this->useCache) {
                foreach ($script->deps as $dependency) {
                    if ( ! in_array($dependency, $this->jsDeps)) {
                        $this->jsDeps[] = $dependency;
                    }
                }
                $this->jsMinifier->add($absPath);
            }
        }
    }

    protected function getAbsPathFromUrl($url)
    {
        $args = wp_parse_url($url);
        if ( ! empty($args['path'])) {
            return ABSPATH.ltrim($args['path'], '/');
        }

        return null;
    }

    protected function processStyle(\_WP_Dependency $style)
    {
        $absPath = $this->getAbsPathFromUrl($style->src);
        if (null !== $absPath) {
            $this->cssMinifier->add($absPath);

            foreach ($style->deps as $dependency) {
                if ( ! in_array($dependency, $this->cssDeps)) {
                    $this->cssDeps[] = $dependency;
                }
            }
        }
    }

    protected function printExtras()
    {
        echo '<script type="text/javascript">';
        foreach ($this->extras as $extra) {
            echo $extra;
        }
        echo '</script>';
    }

    protected function minify()
    {
        $this->jsMinifier->minify($this->jsOutputFilePath);
        $this->cssMinifier->minify($this->cssOutputFilePath);
    }

    protected function enqueueMinified()
    {
        wp_enqueue_script($this->handle.'_js', $this->jsOutputFileUrl, $this->jsDeps);
        wp_enqueue_style($this->handle.'_css', $this->cssOutputFileUrl, $this->cssDeps);
    }

    public function getContextIdentifier()
    {
        return null;
    }

    /** return the object to use in twig templates. use $this to inject the whole component with all its methods */
    public function getContext()
    {
        return null;
    }
}
