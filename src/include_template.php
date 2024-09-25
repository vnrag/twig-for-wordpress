<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 17.04.2017
 * Time: 14:13
 */

try {
    /** @var \Schettke\TwigForWordpress\View $schettke_twig_for_wordpress_view */
    global $schettke_twig_for_wordpress_view;
    $schettke_twig_for_wordpress_view->render();
} catch (\Exception $e) {
    trigger_error($e->getMessage(), E_USER_ERROR);
    wp_redirect('/');
}
