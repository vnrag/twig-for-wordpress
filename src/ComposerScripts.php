<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 26/07/2018
 * Time: 10:39
 */

namespace Schettke\TwigForWordpress;

class ComposerScripts
{
    /**
     * Creates the plugins default folder structure and files
     */
    public static function postInstall()
    {
        $pluginFolder = realpath(dirname(__FILE__).'/../../../../');

        //config folder
        $configFolder = $pluginFolder.'/config';
        if (!is_dir($configFolder)) {
            mkdir($configFolder);
        }
        $configFileDefault = $configFolder.'/default.yml';
        if (!is_file($configFileDefault)) {
            $exampleFile = realpath(dirname(__FILE__).'/../config-example.yml');
            copy($exampleFile, $configFileDefault);
        }

        //public folder
        $publicFolder = $pluginFolder.'/public';
        if(!is_dir($publicFolder)) {
            mkdir($publicFolder);
        }
        $cssFolder = $publicFolder.'/css';
        if(!is_dir($cssFolder)) {
            mkdir($cssFolder);
        }
        $cssBaseFile = $cssFolder.'/base.css';
        if(!is_file($cssBaseFile)) {
            touch($cssBaseFile);
        }
        $jsFolder = $publicFolder.'/js';
        if(!is_dir($jsFolder)) {
            mkdir($jsFolder);
        }

        //views folder
        $viewsFolder = $pluginFolder.'/views';
        if(!is_dir($viewsFolder)) {
            mkdir($viewsFolder);
        }
        $viewsBaseFolder = $viewsFolder.'/base';
        if(!is_dir($viewsBaseFolder)) {
            mkdir($viewsBaseFolder);
        }
        $singleTwigFile = $viewsBaseFolder.'/single.twig';
        $archiveTwigFile = $viewsBaseFolder.'/archive.twig';
        $viewFiles = array_diff(scandir($viewsBaseFolder), ['.','..']);
        $singleViewFileFound  = false;
        $archiveViewFileFound = false;
        foreach($viewFiles as $viewFile) {
            if(preg_match('/^single-.*\.twig$/', $viewFile)) {
                $singleViewFileFound = true;
            }
            if(preg_match('/^archive-.*\.twig$/', $viewFile)) {
                $archiveTwigFile = true;
            }
        }
        if(!$singleViewFileFound && !is_file($singleTwigFile)) {
            touch($singleTwigFile);
        }
        if(!$archiveViewFileFound && !is_file($archiveTwigFile)) {
            touch($archiveTwigFile);
        }

        echo "Successfully created folder structure for TwigForWordpress!\n";
    }
}