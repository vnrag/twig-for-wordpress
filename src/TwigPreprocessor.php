<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 21.11.18
 * Time: 18:27
 */

namespace Schettke\TwigForWordpress;



class TwigPreprocessor
{
    /**
     * @var bool
     */
    private $activateLazyLoading;

    /**
     * TwigPreprocessor constructor.
     * @param bool $activateLazyLoading
     */
    public function __construct($activateLazyLoading = true)
    {
        $this->activateLazyLoading = $activateLazyLoading;
    }

    public function process($twigSource) {
        if($this->activateLazyLoading) {
            $twigSource = $this->convertImgTagsToLazyLoading($twigSource);
        }

        return $twigSource;
    }

    /**
     * Converts <img /> tags to a twig function call to the "TwigLazyImageFunction"
     * @param $twigSource
     * @return mixed
     */
    private function convertImgTagsToLazyLoading($twigSource) {
        $processedSource = preg_replace_callback('/< *img[^>]+>/', [$this, 'convertImgTag'], $twigSource);
        return $processedSource;
    }


    private function convertImgTag($inputHtml) {
        if(is_array($inputHtml) && 1 === sizeof($inputHtml)) {
            $inputHtml = $inputHtml[0];
        }

        $domDoc = new \DOMDocument();
        $domDoc->loadHTML($inputHtml);
        $domNodeList = $domDoc->getElementsByTagName('img');
        if(0 === $domNodeList->length) {
            return $inputHtml;
        }

        $srcString = '';
        /** @var \DOMElement $domElement */
        foreach($domNodeList as $domElement) {
            if ($domElement->hasAttributes()) {
                $src = null;
                $attributes = [];
                foreach ($domElement->attributes as $attribute) {
                    $name = $attribute->nodeName;
                    $value = $attribute->nodeValue;
                    if ('src' === $name) {
                        $srcBracesStripped = $this->stripCurlyBraces($value);
                        if ($srcBracesStripped) {
                            $srcString = $value;
                        } else {
                            $srcString = "'" . $value . "'";
                        }
                    } else if ('srcset' === $name) {
                        //Do not process images already containing a srcset
                        return $inputHtml;
                    } else if ('class' === $name && strpos('layload', $value) === false) {
                        // add class "lazyload"
                        $attributes['class'] = $value . ' lazyload';
                    } else {
                        //save other attributes
                        $attributes[$name] = $value;
                    }
                }

                //convert attributes to string
                $i = 0;
                $attrString = '{';
                foreach ($attributes as $key => $val) {
                    $attrString .= "'" . $key . "'";
                    $attrString .= ' : ';
                    $bracesStripped = $this->stripCurlyBraces($val);
                    if ($bracesStripped) {
                        $attrString .= $val;
                    } else {
                        $attrString .= "'" . $val . "'";
                    }
                    if (($i++ + 1) < sizeof($attributes)) {
                        $attrString .= ',';
                    }
                }
                $attrString .= '}';

                $functionCall = '{{ schettkeTwigLazyImageFunction(' . $srcString . ',' . $attrString . ') }}';

                return $functionCall;
            }
        }
    }

    /**
     * detect if this is a {{ twig_variable }} and remove the curly braces for the following function call
     * @param $value string the value to process (by reference)
     * @return bool if there was some processing
     */
    private function stripCurlyBraces(&$value) {
        if(preg_match('/.*\{\{ *([^} ]+) *\}\}/', $value, $matches)) {
            if(2 === sizeof($matches)) {
                $value = $matches[1];
                return true;
            }
        }
        return false;
    }
}