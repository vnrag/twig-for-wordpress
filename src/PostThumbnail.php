<?php
/**
 * Created by PhpStorm.
 * User: ric
 * Date: 10.02.19
 * Time: 01:14
 */

namespace Schettke\TwigForWordpress;


class PostThumbnail
{
    private $id              = 0;
    private $url             = '';
    private $width           = 0;
    private $height          = 0;
    private $is_intermediate = true;
    private $meta            = [];
    private $image_meta      = [];

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return bool
     */
    public function isIntermediate()
    {
        return $this->is_intermediate;
    }

    /**
     * @param bool $is_intermediate
     */
    public function setIsIntermediate($is_intermediate)
    {
        $this->is_intermediate = $is_intermediate;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * @return array
     */
    public function getImageMeta()
    {
        return $this->image_meta;
    }

    /**
     * @param array $image_meta
     */
    public function setImageMeta($image_meta)
    {
        $this->image_meta = $image_meta;
    }


}