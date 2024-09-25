<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 06.11.18
 * Time: 22:58
 */

namespace Schettke\TwigForWordpress;


class ViewFragment extends View
{
    private $includeCSS = false;
    private $includeJS = false;

    public static function create($viewName) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        if ( ! isset($backtrace[0]['file'])) {
            error_log('Could not get calling function from backtrace');

            return false;
        }
        $pluginBaseDir = dirname($backtrace[0]['file']);

        //we need to make sure that the correct pluginBaseDir is determined, even if this component is
        //used multiple times in a single wordpress instance.
        //we assume the default wordpress directory structure here..
        preg_match('#.*?/wp-content/plugins/[^/]+#', $pluginBaseDir, $matches);
        if(isset($matches[0])) {
            $pluginBaseDir = $matches[0];
        }
        else {
            error_log('Could not match calling plugin basedir');
        }

        $viewLoader = ViewLoaderFactory::getViewLoader($pluginBaseDir);
        return $viewLoader->createViewFragment($viewName);
    }

    public function includeCSS($include = true) {
        $this->includeCSS = $include;
    }

    public function includeJS($include = true) {
        $this->includeJS = $include;
    }

    protected function enqueueScripts()
    {
        if($this->includeJS) {
            parent::enqueueScripts();
        }
    }

    protected function enqueueStyles()
    {
        if($this->includeCSS) {
            parent::enqueueStyles();
        }
    }
}