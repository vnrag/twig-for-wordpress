<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 25/07/2018
 * Time: 11:10
 */

namespace Schettke\TwigForWordpress;

use Schettke\TwigForWordpress\ViewComponents\Minifier;

class ViewLoader
{
    const VIEW_TYPE_SINGLE   = 1;
    const VIEW_TYPE_ARCHIVE  = 2;
    const VIEW_TYPE_FRAGMENT = 3;

    /** @var ConfigReader */
    private $configReader;

     /** @var PostLoader */
    private $postLoader;

    /** @var array */
    private $customVariables;

    /** @var string */
    private $customViewName;

    /** @var ViewComponentInterface[] */
    private $viewComponents;

    /**
     * ViewLoader constructor.
     *
     * @param ConfigReader $configReader
     * @param PostLoader $postLoader
     */
    public final function __construct(ConfigReader $configReader, PostLoader $postLoader)
    {
        $this->configReader = $configReader;
        $this->postLoader = $postLoader;
    }

    public function registerCustomTemplates()
    {
        add_filter('single_template', array($this, 'getSingleTemplate'), PHP_INT_MAX);
        add_filter('archive_template', array($this, 'getArchiveTemplate'), PHP_INT_MAX);
    }

    public final function getSingleTemplate($single_template)
    {
        global $post;
        if ($postType = $this->isMatchingPostType($post->post_type)) {
            global $schettke_twig_for_wordpress_view;
            $schettke_twig_for_wordpress_view = $this->createViewFromPostId($post->ID);
            return dirname(__FILE__).'/include_template.php';
        } else {
            return $single_template;
        }
    }

    public final function getArchiveTemplate($archive_template)
    {
        global $post;
        global $wp_query;

        if(isset($post)) {
            $postType = $post->post_type;
        }
        //fix for empty post (p.e. the archive has no entries)
        else if(isset($wp_query) && isset($wp_query->queried_object) && isset($wp_query->queried_object->name)) {
                $postType = $wp_query->queried_object->name;
        }
        else {
            return $archive_template;
        }

        if ($postType = $this->isMatchingPostType($postType)) {
            global $schettke_twig_for_wordpress_view;
            $schettke_twig_for_wordpress_view = $this->createArchiveView($postType);
            return dirname(__FILE__).'/include_template.php';
        } else {
            return $archive_template;
        }
    }

    private function createArchiveView($postType)
    {
        $view = $this->createView(self::VIEW_TYPE_ARCHIVE, $postType);
        $args = $this->getArchiveQueryArgs($postType);
        if(empty($args)) {
            $error = new \Exception('ViewLoader::getArchiveQueryArgs was overwritten, 
            but does not return an array with args for \WP_Query.');
            $view->setError($error);
        }
        else {
            $posts = $this->postLoader->loadAll($args);
            $view->addToContext('posts', $posts);
            $this->addCustomVariables($postType, self::VIEW_TYPE_ARCHIVE, $posts);
            $view = $this->addCustomVariablesToContext($view);
            if(!empty($this->customViewName)) {
                $view->setViewName($this->customViewName);
            }
        }
        return $view;
    }

    private function createViewFromPostId($postId)
    {
        $post = $this->postLoader->load($postId);
        $view = $this->createView(self::VIEW_TYPE_SINGLE, $post->post_type, $post);
        $view->addToContext('post', $post);
        $this->addCustomVariables($post->post_type, self::VIEW_TYPE_SINGLE, $post);
        $view = $this->addCustomVariablesToContext($view);
        if(!empty($this->customViewName)) {
            $view->setViewName($this->customViewName);
        }
        return $view;
    }

    public function createViewFragment($viewName = 'fragment.twig')
    {
        /** @var ViewFragment $view */
        $view = $this->createView(self::VIEW_TYPE_FRAGMENT);
        $view->setViewName($viewName);
        return $view;
    }

    private function addCustomVariablesToContext(View $view) {
        if(!empty($this->customVariables)) {
            foreach($this->customVariables as $key => $var) {
                $view->addToContext($key, $var);
            }
        }
        return $view;
    }

    /**
     * Overwrites the template to load (p.e. "single.twig").
     * Can be called from within "addCustomVariables()"
     * @param string $customViewName
     */
    protected final function setViewName($customViewName = '') {
        $this->customViewName = $customViewName;
    }



    protected final function addViewComponent(ViewComponentInterface $viewComponent) {
        $this->viewComponents[] = $viewComponent;
    }

    /**
     * @param int $viewType
     * @param null $postType
     *
     * @param null|\WP_Post $post
     * @return View|ViewFragment
     */
    private function createView($viewType = self::VIEW_TYPE_SINGLE, $postType = null, $post = null)
    {
        $viewClass = '\Schettke\TwigForWordpress\View';

        switch ($viewType) {
            case self::VIEW_TYPE_SINGLE :
                $singleClass = $this->configReader->getParameter('view.single_class');
                if ($singleClass) {
                    $viewClass = $singleClass;
                }
                break;
            case self::VIEW_TYPE_ARCHIVE :
                $archiveClass = $this->configReader->getParameter('view.archive_class');
                if ($archiveClass) {
                    $viewClass = $archiveClass;
                }
                break;
            case self::VIEW_TYPE_FRAGMENT :
                $viewClass = '\Schettke\TwigForWordpress\ViewFragment';
                break;
        }

        $pluginBaseDir  = $this->configReader->getPluginBaseDir();
        $viewBaseDir    = $this->configReader->getParameter('view.basedir');
        $cssPath        = $this->configReader->getParameter('view.css_file');
        $jsPath         = $this->configReader->getParameter('view.js_file');
        $debugMode      = $this->configReader->getParameter('view.debug_mode');
        $lazyLoading    = $this->configReader->getParameter('view.lazyloading');

        /** @var View|ViewFragment $view */
        $view = new $viewClass($pluginBaseDir, $viewBaseDir, $cssPath, $jsPath, $debugMode, $lazyLoading);

        // Include Minifier
        $minifierEnabled = $this->configReader->getParameter('view.minifier_enabled');

        if($minifierEnabled && !$debugMode
            && (self::VIEW_TYPE_SINGLE === $viewType || self::VIEW_TYPE_ARCHIVE === $viewType)) {
            switch($viewType) {
                case self::VIEW_TYPE_SINGLE:
                    $cssPath = 'cache/minifier/' . $postType . '_single.min.css';
                    $jsPath  = 'cache/minifier/' . $postType . '_single.min.js';
                    break;
            case self::VIEW_TYPE_ARCHIVE:
                $cssPath =  'cache/minifier/' . $postType . '.min.css';
                $jsPath  =  'cache/minifier/' . $postType . '.min.js';
                break;
            }
            $minfier = new Minifier($cssPath, $jsPath, [], !$debugMode);
            $this->viewComponents[] = $minfier;
        }

        $this->addViewComponents($postType, $viewType, $post);
        $view->setViewComponents($this->viewComponents);

        $view->addToContext('post_type', $postType);

        if(!empty($this->customViewName)) {
            $view->setViewName($this->customViewName);
        }
        else {
            $view->setViewName($this->getTwigFile($viewType, $postType));
        }

        if($postType) {
            $view->setScriptHandle($postType);
        }
        return $view;
    }

    private function getTwigFile($viewType = self::VIEW_TYPE_SINGLE, $postType = null)
    {
        //return default files
        if (null === $postType) {
            switch ($viewType) {
                case self::VIEW_TYPE_SINGLE :
                    return 'single.twig';
                case self::VIEW_TYPE_ARCHIVE :
                    return 'archive.twig';
                case self::VIEW_TYPE_FRAGMENT :
                    return 'fragment.twig';
            }
        }

        //try to resolve single-{post_type}.twig or archive-{post_type}.twig
        $searchPath  = $this->configReader->getPluginBaseDir().'/views/';
        $viewBaseDir = $this->configReader->getParameter('view.basedir');
        if ($viewBaseDir) {
            $viewBaseDir = rtrim($viewBaseDir, '/');
        }
        $searchPath .= ($viewBaseDir) ? $viewBaseDir : 'base';
        $searchPath .= '/';

        switch ($viewType) {
            case self::VIEW_TYPE_SINGLE :
                $fileName   = 'single-'.$postType.'.twig';
                $searchPath .= $fileName;
                if (is_file($searchPath)) {
                    return $fileName;
                } else {
                    return 'single.twig';
                }
                break;
            case self::VIEW_TYPE_ARCHIVE :
                $fileName   = 'archive-'.$postType.'.twig';
                $searchPath .= $fileName;
                if (is_file($searchPath)) {
                    return $fileName;
                } else {
                    return 'archive.twig';
                }
                break;
        }
    }

    /**
     * compares the given post type with those from the config.
     *
     * @param $postType string the given post type
     *
     * @return false|string false if it does not match or the post type as string.
     */
    private function isMatchingPostType($postType)
    {
        $postTypes = $this->configReader->getParameter('view.post_types');
        if (is_string($postTypes)) {
            return ($postTypes === $postType) ? $postType : false;
        }

        foreach ($postTypes as $type) {
            if ($type === $postType) {
                return $postType;
            }
        }

        return false;
    }

    /**
     * @return PostLoader
     */
    public final function getPostLoader()
    {
        return $this->postLoader;
    }

    /**
     * @return string absolute path to the basedir of the plugin using this component (without trailing slash)
     * p.e. /var/customers/webs/dev/wp-content/plugins/schettke-shop
     */
    public final function getPluginBaseDir() {
        return $this->configReader->getPluginBaseDir();
    }

    /**
     * Call §this->addToContext('key', $variable) to make the variable available as {{ key }} in twig.
     *
     * Variables can be of any type (strings, objects, arrays, functions...). Access to properties of objects or
     * fields of arrays is possible with the dot-notation, p.e. {{ key.myfield }}. functions can be called
     * with {{ key(parameters) }}.
     * @param $key
     * @param $variable
     */
    protected final function addToContext($key, $variable) {
        $this->customVariables[$key] = $variable;
    }

    /**
     * Overwrite this method to load additional variables into the twig template.
     *
     * Call §this->addToContext('key', $variable) to make the variable available as {{ key }} in twig.
     *
     * Variables can be of any type (strings, objects, arrays, functions...). Access to properties of objects or
     * fields of arrays is possible with the dot-notation, p.e. {{ key.myfield }}. functions can be called
     * with {{ key(parameters) }}.
     *
     * @param string $postType the current postType
     * @param $viewType integer one of the class constants, p.e. self::VIEW_TYPE_SINGLE or self::VIEW_TYPE_ARCHIVE
     * @param null|\WP_Post|\WP_Post[] $post Wordpress post object for singleView, $posts array for archiveView or
     * null for fragments. Posts are loaded via the PostLoader, so they may be decorated (class PostDecorator).
     */
    protected function addCustomVariables($postType, $viewType, $post = null) {
    }

    /**
     * Overwrite this method to load additional view components into the twig template.
     *
     * Call §this->addViewComponent(ViewComponentInterface $viewComponent) from inside this function.
     *
     * @param string $postType the current postType
     * @param $viewType integer one of the class constants, p.e. self::VIEW_TYPE_SINGLE or self::VIEW_TYPE_ARCHIVE
     * @param null|\WP_Post $post Wordpress post object or null if this is an archive view
     */
    protected function addViewComponents($postType, $viewType, $post = null) {
    }

    /**
     * Return the desired args for building the archive page with WP_Query.
     **
     * @param $postType string the current postType
     *
     * @return array
     */
    protected function getArchiveQueryArgs($postType) {
        $args = [
            'posts_per_page' => -1,
            'post_type' => $postType,
            'orderby' => 'date',
            'order'=>'ASC'
        ];
        return $args;
    }
}
