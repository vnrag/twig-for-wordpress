<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 10.02.19
 * Time: 01:23
 */

namespace Schettke\TwigForWordpress;

/**
 * If a postType was decorated via the PostDecorator, the PostDecorator tries to load its thumbnail automatically
 * from the wordpress attachments. If it fails to do so, it will try to load the thumbnail via the PostThumbnailInterface.
 * You can implement this interface in your PostType to provide this thumbnail fetching fallback to the PostDecorator.
 * Interface PostThumbnailInterface
 * @package Schettke\TwigForWordpress
 */
interface PostThumbnailInterface
{
    /**
     * @return PostThumbnail
     */
    public function getThumbnail();
}