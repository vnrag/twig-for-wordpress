<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 18.10.2017
 * Time: 11:33
 */

namespace Schettke\TwigForWordpress;

interface ViewComponentInterface
{
    /** do any work necessary prior to rendering here, like enqueuing scripts and styles */
    public function init();

    /** the string by which this component should be added to the twig context. Returning NULL will not add this
     * component to the context (but init() it anyways) */
    public function getContextIdentifier();

    /** return the object to use in twig templates. use $this to inject the whole component with all its methods */
    public function getContext();
}
