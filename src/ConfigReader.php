<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 25/07/2018
 * Time: 12:32
 */

namespace Schettke\TwigForWordpress;

use Symfony\Component\Yaml\Yaml;

class ConfigReader
{
    protected $configValues;

    protected $pluginBaseDir;

    protected $configFile;

    public function __construct($pluginBaseDir)
    {
        //remove trailing slash
        if ('/' === substr($pluginBaseDir, -1)) {
            $pluginBaseDir = rtrim($pluginBaseDir, '/');
        }
        $this->pluginBaseDir = $pluginBaseDir;
        $this->configFile = $this->getConfigFilePath();
        $this->configValues   = Yaml::parse(file_get_contents($this->configFile));
    }

    /**
     * Tries to load a config file with the host name in it, p.e. config/www.ppm-online.org.yml
     *
     * @return string absolute path to the matching config file
     */
    protected function getConfigFilePath()
    {
        //per convention the config folder should be loacted on layer beneath the plugin dir.
        $configDir = $this->pluginBaseDir.'/config/';

        //try to load config matching hostname
        $configFile = $configDir.$_SERVER['HTTP_HOST'].'.yml';

        if (is_file($configFile)) {
            return $configFile;
        } else {
            //try to load default.yml from config-folder
            $defaultFile = $configDir.'default.yml';
            if (is_file($defaultFile)) {
                return $defaultFile;
            } else {
                die("<h3>Schettke\TwigForWordpress could not find the config file</h3>
                    <br />Search locations:<br />$configFile<br />$defaultFile");
            }
        }
    }

    /**
     * @return string the absolute path to the loaded config file
     */
    public final function getConfigFile()
    {
        return $this->configFile;
    }

    public function getPluginBaseDir()
    {
        return $this->pluginBaseDir;
    }

    public function getParameter($key)
    {
        return (isset($this->configValues['parameters'][$key])) ? $this->configValues['parameters'][$key] : null;
    }
}
