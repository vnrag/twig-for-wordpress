<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 28/07/2018
 * Time: 18:20
 */

namespace Schettke\TwigForWordpress;

use Twig\TwigFunction;

class TwigAssetFunction extends TwigFunction
{
    private $basePath;

    /**
     * TwigAssetProxy constructor.
     *
     * @param $pluginBasePath
     */
    public function __construct($pluginBasePath)
    {
        parent::__construct('asset');
        $this->basePath = $pluginBasePath;
    }

    public function getCallable()
    {
        return array($this,'getAssetUrl');
    }

    public function getAssetUrl($path) {
        $path = 'public/' . $path;
        $absUrl = plugins_url($path, $this->basePath.'dummy.php');
        $relUrl = wp_make_link_relative($absUrl);
        return $relUrl;
    }
}
