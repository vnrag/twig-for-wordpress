<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 20.11.18
 * Time: 22:18
 */

namespace Schettke\TwigForWordpress;



class ImageConverter
{
    /**
     * Converts the given image to various sizes and puts them in the output folder with their width
     * appended to their filename, p.e. "EXL-Excel-fuer-die-Praxis-115w.jpg"
     * @param $imgUrl string full url to the original image
     * @param $outputDir string absolute path to the folder for output files (no trailing slash)
     * @return bool success
     */
    public static function convert($imgUrl, $outputDir) {
        try {
            //fake user agent for ithemes security
            $options = array(
                'http'=>array(
                    'method'=>"GET",
                    'header'=>
                        "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"
                )
            );
            $context = stream_context_create($options);
            $imgData = file_get_contents($imgUrl, false, $context);
            if(false === $imgData) {
                throw new \Exception('Could not get image from ' . $imgUrl);
            }

            //get filename from url
            $fileData = pathinfo($imgUrl);
            $orgFileName = $fileData['filename'];
            $orgFileExt  = $fileData['extension'];

            //create tmp file
            $tmpFile = tmpfile();
            fwrite($tmpFile, $imgData);
            $metaData = stream_get_meta_data($tmpFile);
            $tmpFilename = $metaData["uri"];

            //get full image size
            $wpImageEditor = wp_get_image_editor($tmpFilename);
            if ( is_wp_error( $wpImageEditor )) {
                throw new \Exception('Could not load image: ' . $wpImageEditor->get_error_message());
            }
            $size = $wpImageEditor->get_size();
            $fullWidth = $size['width'];

            //create output folder
            if(!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            //make output folder accessible
            file_put_contents($outputDir . '/' . '.htaccess', "order deny,allow\nallow from all");

            //copy full image
            copy($tmpFilename, $outputDir . '/' . $orgFileName . '-' . $fullWidth . 'w.' . $orgFileExt );

            //calculate resize values and copy them
            $newWidth = $fullWidth;
            while($newWidth > 100) {
                $newWidth -= 100;
                $wpImageEditor = wp_get_image_editor($tmpFilename);
                $wpImageEditor->resize($newWidth, null);
                $wpImageEditor->save($outputDir . '/' . $orgFileName . '-' . $newWidth . 'w.' . $orgFileExt);
            }

            return true;
        }
        catch(\Exception $e) {
            error_log($e);
            return false;
        }
    }
}
