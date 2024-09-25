<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 09.10.2017
 * Time: 22:59
 */

namespace Schettke\TwigForWordpress\ViewComponents;

class BootstrapCheckboxes implements \Schettke\TwigForWordpress\ViewComponentInterface
{
    public function init()
    {
        $dir = dirname(__FILE__, 3).'/dummy';
        $url = plugins_url('public/css/awesome-bootstrap-checkbox.css', $dir);
        wp_enqueue_style('schettke_downloads_awesome-bootstrap-checkbox', $url);
    }

    /** the string by which this component should be added to the twig context. Returning NULL will not add this
     * component to the context (but init() it anyways) */
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
