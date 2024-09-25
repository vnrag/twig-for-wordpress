<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 28/07/2018
 * Time: 18:20
 */

namespace Schettke\TwigForWordpress;

use Twig\TwigFunction;


/**
 * Creates a lazy loading <img /> tag
 *
 */
class TwigLazyImageFunction extends TwigFunction
{
    private $pluginBaseDir;
    /**
     * @var string
     */
    private $outputPath;

    /**
     * TwigAssetProxy constructor.
     *
     * @param $pluginBaseDir
     * @param string $outputPath
     */
    public function __construct($pluginBaseDir, $outputPath = 'cache/images')
    {
        parent::__construct('schettkeTwigLazyImageFunction');
        $this->pluginBaseDir = $pluginBaseDir;
        $this->outputPath = $outputPath;
    }

    public function getCallable()
    {
        return array($this,'createLazyLoadedImage');
    }

    /**
     *
     * @param $src string path or url to the image file. only used for parsing the filename and extension.
     * @param array additional attributes for the img tag, p.e. ['class' = 'foo']
     * @param bool $convertOnTheFly defines if the images should be converted on the fly if not found under "path".
     * @return string the image tag as string
     */
    public function createLazyLoadedImage($src, $attributes, $convertOnTheFly = true)
    {
        //return aa placeholder img if no src image was provided
        if(empty($src)) {
            return '<img class="'
                . (isset($attributes['class']) ? $attributes['class'] : '')
                . '" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAADWUlEQVR4Xu3ca3KqQBAFYFyGbMP97wCXoS7DW1Mpc40C8+rHOUPzJ1QC0z3ng8GiKp6WZXmez+cpNv8EHo/HdLrdbs+0c7lc/Ds6cAfX63Wa5/kHJO2kXwSKzxXxyv73DkkgaQsUe5D3zL9AAsUW5PMGWAUJFBuUtdVoEyRQdFG2Hg27IIGig7L3nM6CBIosSu5DUxFIoMig5DBSlWKQQOlDKcGoBgmUNpRSjCaQQKlDqcFoBgmUMpRajC6QQNlHacHoBgmUdZRWDBGQQPmL0oMhBhIoPyi9GKIgUg2VPS7xjpLAEAc5KooUhgrI0VAkMdRAjoIijaEKMjqKBoY6yKgoWhgmIKOhaGKYgYyCoo1hCsKOYoFhDsKKYoXhAsKGYonhBsKCYo3hCoKO4oHhDoKK4oUBAYKG4okBA4KC4o0BBeKNgoABB+KFgoIBCWKNgoQBC2KFgoYBDaKNgogBD6KFgopBASKNgoxBAyKFgo5BBdKLwoBBB9KKwoJBCVKLwoRBC1KKwoZBDZJDYcSgB9lCYcUYAuQThRljGJAXSvrJ/vVSVf+nniaMuqU7I0BAdN6XqViynFHWAJhRqJesveBZUWhBSgIvOcb5Bv8qTwlSE3TNsQg4dCAtAbec44VDBdITbM+5ljg0IBKBSoyhjUMBIhmk5FgaOPAgGgFqjCmFAw2iGZzm2D04sCAWgVnUqMWBBLEMyrJWCQ4ciEdAHjW3cKBAPIPxrP2OAwOCEAhCDxAgCEG8rlLvXtxBvANYW8s9e3IF8Zx47hOPV29uIF4TzkG8/92jRxcQj4nWQHiimIMwYXg86E1BGDGsUcxAmDEsUUxARsCwQlEHGQnDAkUVZEQMbRQ1kJExNFFUQI6AoYUiDnIkDA0UUZAjYkijiIEcGUMSRQQkMP6//erNohukt4HWl37I5/Vk0gXSUxg5UIneWrNpBmktKDFZljFaMmoCaSnEEqJ0n7VZVYPUFpCeION4NZlVgdQMzBicZs+l2RWDlA6oOSn2sUsyLAIpGYg9LKv+c1lmQXIDWE1kpDp7me6CBIbeZbCV7SZIYOhh7L37WgUJDH2MLZQvkMCww1hD+QMSGPYYnyi/IGmH/cu//OKUqZxuiHmep9OyLM+0E5t/Avf7ffoHTv7dKfLuA1AAAAAASUVORK5CYII=" alt="no image"/>';
        }

        //define the needle
        $fileData = pathinfo($src);
        $orgFileName = $fileData['filename'];
        $orgFileExt  = $fileData['extension'];

        //find fitting images
        $searchPath = $this->pluginBaseDir . '/' . $this->outputPath;
        if(is_dir($searchPath)) {
            $searchFiles = array_diff(scandir($searchPath), array('..', '.'));
        }
        else {
            $searchFiles = [];
        }

        $matches = [];
        $i = 0;
        foreach($searchFiles as $file) {
            if(strpos($file, $orgFileName) !== false) {
                $matches[$i]['filename'] = $file;

                // get width identification
                $tmp = pathinfo($file);
                $tmp = $tmp['filename'];
                $tmp = explode('-', $tmp);
                $matches[$i]['width'] = $tmp[(sizeof($tmp) - 1)];

                $matches[$i]['url'] = plugins_url($file, $this->pluginBaseDir . '/' . $this->outputPath . '/nofile.php');
                $i++;
            }
        }

        if(empty($matches)) {
            if($convertOnTheFly) {
                $success = ImageConverter::convert($src, $this->pluginBaseDir . '/' . $this->outputPath);
                if($success) {
                    $imgTag = $this->createLazyLoadedImage($src, $attributes, false);
                    return $imgTag;
                }
            }
        }

        //get largest image as fallback
        $maxWidth = 0;
        $largestMatch['url'] = '';
        foreach($matches as $match) {
            $currentWidth = (int) str_replace('w', '', $match['width']);
            if($currentWidth > $maxWidth) {
                $maxWidth = $currentWidth;
            }
        }
        foreach($matches as $match) {
            if($maxWidth . 'w' === $match['width']) {
                $largestMatch = $match;
                break;
            }
        }

        //compose image tag
        $dataSrc = $largestMatch['url'];

        $dataSrcset = '';
        for($i = 0; $i < sizeof($matches); $i++) {
            $dataSrcset .= $matches[$i]['url'] . ' ' . $matches[$i]['width'];
            if(($i + 1) < sizeof($matches)) {
                $dataSrcset .= ',';
            }
        }

        //create element
        $domDoc = new \DOMDocument();
        $element = $domDoc->createElement('img');
        foreach($attributes as $key => $val) {
            $element->setAttribute($key, $val);
        }

        //set data-sizes to "auto" if not defined in the template
        if(!$element->hasAttribute('data-sizes')) {
            $element->setAttribute('data-sizes', 'auto');
        }

        $element->setAttribute('data-src', $dataSrc);
        $element->setAttribute('data-srcset', $dataSrcset);

        //append "lazyload" css class
        if($element->hasAttribute('class')) {
            $class = $element->getAttribute('class');
            if(false === strpos($class, 'lazyload')) {
                $element->setAttribute('class', $class . ' lazyload');
            }
        }
        else {
            $element->setAttribute('class', 'lazyload');
        }

        $domDoc->appendChild($element);

        return $domDoc->saveHTML();
    }
}
