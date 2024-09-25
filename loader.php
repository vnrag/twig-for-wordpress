<?php
/**
 * Require this file from within your plugin root directory to activate this component.
 * For additional configuration details see "config-example.yml".
 */

//get plugin base dir
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
if ( ! isset($backtrace[0]['file'])) {
    error_log('Could not get calling function from backtrace');

    return;
}
$pluginBaseDir = dirname($backtrace[0]['file']);

require_once($pluginBaseDir . '/vendor/autoload.php');

/** @var \Schettke\TwigForWordpress\ViewLoader $viewLoader */
$viewLoader = \Schettke\TwigForWordpress\ViewLoaderFactory::getViewLoader($pluginBaseDir);
$viewLoader->registerCustomTemplates();
