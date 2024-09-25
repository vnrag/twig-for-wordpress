<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 07.11.18
 * Time: 00:21
 */

namespace Schettke\TwigForWordpress;


class ViewLoaderFactory
{
    /**
     * @param $pluginBaseDir
     * @return ViewLoader
     */
    public static function getViewLoader($pluginBaseDir) {
        $configReader = new ConfigReader($pluginBaseDir);

        //load PostLoader
        $candidates = [
            $pluginBaseDir.'/PostLoader.php',
            $pluginBaseDir.'/src/PostLoader.php',
            $pluginBaseDir.'/src/View/PostLoader.php'
        ];
        foreach($candidates as $candidate) {
            if(is_file($candidate)) {
                $postLoaderClass = self::getClass($candidate);
                require_once($candidate);
                break;
            }
        }
        if(!isset($postLoaderClass)) {
            $postLoaderClass = '\Schettke\TwigForWordpress\PostLoader';
        }
        $postLoader = new $postLoaderClass();

        //load ViewLoader
        $candidates = [
            $pluginBaseDir.'/ViewLoader.php',
            $pluginBaseDir.'/src/ViewLoader.php',
            $pluginBaseDir.'/src/View/ViewLoader.php'
        ];
        foreach($candidates as $candidate) {
            if(is_file($candidate)) {
                $viewLoaderClass = self::getClass($candidate);
                require_once($candidate);
                break;
            }
        }
        if(!isset($viewLoaderClass)) {
            $viewLoaderClass = '\Schettke\TwigForWordpress\ViewLoader';
        }
        $viewLoader = new $viewLoaderClass($configReader, $postLoader);

        return $viewLoader;
    }

    /**
     * Returns the fully qualified class name (FQCN) of the given file
     * https://stackoverflow.com/questions/7153000/get-class-name-from-file
     * @param $file
     * @return string
     */
    public static function getClass($file) {
        $fp = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) break;

            $buffer .= fread($fp, 512);
            $tokens = @token_get_all($buffer);

            if (strpos($buffer, '{') === false) continue;

            for (;$i<count($tokens);$i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j=$i+1;$j<count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NAME_QUALIFIED) {
                            $namespace .= '\\'.$tokens[$j][1];
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $namespace . '\\' . $class;
    }
}
