<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 21.03.2017
 * Time: 18:13
 */

namespace Schettke\TwigForWordpress;

//http://inchoo.net/dev-talk/wordpress/twig-wordpress-part2/
class TwigWpProxy
{
    public function __call($function, $arguments)
    {

        if ('includeBwrCrossSell' === $function) {
            $Crosssell_ID   = $arguments[0];
            $D_Campaingn_ID = $arguments[1];
            include(WP_PLUGIN_DIR.'/schettke-bwr-bridge/src/View/load_downloads_template.php');

            return null;
        }

        if ( ! function_exists($function)) {
            return null;
        }

        return call_user_func_array($function, $arguments);
    }
}
