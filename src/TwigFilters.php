<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 02.04.2017
 * Time: 19:37
 */

namespace Schettke\TwigForWordpress;

use Twig_Extension;
use Twig_SimpleFilter;

class TwigFilters extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter("ellipsis", array($this, "ellipsis")),
            new Twig_SimpleFilter('wpContentFilter', array($this, 'wpContentFilter')),
            new Twig_SimpleFilter('stripBlockElements', array($this, 'stripBlockElements')),
        );
    }

    public function ellipsis($text, $length = 340)
    {
        if (strlen($text) > $length) {
            $text = wordwrap($text, $length);
            $text = substr($text, 0, strpos($text, "\n")).'...';
        }

        return $text;
    }

    public function wpContentFilter($text)
    {
        return apply_filters('the_content', $text);
    }

    public function stripBlockElements($text)
    {
        $allowedTags = wp_kses_allowed_html();

        return wp_kses($text, $allowedTags);
    }
}
