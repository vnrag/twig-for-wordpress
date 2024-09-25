<?php
/**
 * Created by PhpStorm.
 * User: Mona
 * Date: 23.09.2018
 * Time: 13:44
 */

namespace Schettke\TwigForWordpress;


/**
 * Class PostDecorator
 * @package Schettke\TwigForWordpress
 *
 * Proxy class for WP_Post with some additional convenience functions
 */
class PostDecorator
{
    private $post;
    /**
     * Schettke\Components\CustomPostType\PostType wraps the \WP_Post in getPost(), so we need to alter the getter/setter
     * logic
     * @var bool
     */
    private $isSchettkeCustomPostType;

    //lazy loaded
    protected $meta;
    protected $thumbnails; //array ['size' => thumbnailObj,..]

    public function __construct($post)
    {
        $this->post = $post;
        $this->isSchettkeCustomPostType = is_subclass_of($post, 'Schettke\Components\CustomPostType\PostType');
    }

    public function __call($name, $arguments)
    {
        if(method_exists($this->post, $name)) {
            return call_user_func_array([$this->post, $name], $arguments);
        }
        else if(method_exists($this->post, 'get' .ucfirst($name))) {
            return call_user_func_array([$this->post, 'get' .ucfirst($name)], $arguments);
        }
        else if(method_exists($this->post, 'is' .ucfirst($name))) {
            return call_user_func_array([$this->post, 'is' .ucfirst($name)], $arguments);
        }
        else if(method_exists($this->post, 'has' .ucfirst($name))) {
            return call_user_func_array([$this->post, 'has' .ucfirst($name)], $arguments);
        }
    }

    public function __get($name)
    {
        if(isset($this->post->$name)) {
            return $this->post->$name;
        }
        else if($this->isSchettkeCustomPostType) {
            if(isset($this->post->getPost()->$name)) {
                return $this->post->getPost()->$name;
            }
        }

        return null;
    }

    public function __set($name, $value)
    {
        if($this->isSchettkeCustomPostType) {
            $post = $this->post->getPost();
            $post->$name = $value;
            $this->post->setPost($post);
        }
        else {
            $this->post->$name = $value;
        }
    }

    public function __isset($name)
    {
        $result = (isset($this->post->$name) || ($this->isSchettkeCustomPostType && isset($this->post->getPost()->$name)));
        return $result;
    }

    public function getMeta()
    {
        if($this->meta) {
            return $this->meta;
        }

        $postMeta = get_post_meta($this->__get('ID'));
        foreach ($postMeta as $key => $meta) {
            if (is_array($meta) && 1 === count($meta)) {
                if (is_serialized($meta[0])) {
                    $postMeta[$key] = unserialize($meta[0]);
                } else {
                    $jsonDecode = json_decode($meta[0]);
                    if (is_object($jsonDecode)) {
                        $postMeta[$key] = $jsonDecode;
                    } else {
                        $postMeta[$key] = $meta[0];
                    }
                }
            }
        }
        $this->meta = $postMeta;
        return $this->meta;
    }

    /**
     * @param string $size see https://codex.wordpress.org/Post_Thumbnails#Thumbnail_Sizes
     * @return mixed
     */
    public function getThumbnail($size = 'thumbnail') {
        if(!empty($this->thumbnails[$size])) {
            return $this->thumbnails[$size];
        }

        $thumbnail = new PostThumbnail();
        $thisId = $this->__get('ID');
        $thumbId   = get_post_thumbnail_id($thisId);
        if ($thumbId) {
            $thumbnail->setId($thumbId);
            $meta = wp_get_attachment_metadata($thumbId);
            if($meta) {
                $thumbnail->setMeta($meta);
                $thumbnail->setImageMeta($meta['image_meta']);
            }
            $tmp  = wp_get_attachment_image_src($thumbId, $size);
            if(is_array($tmp)) {
                $thumbnail->setUrl($tmp[0]);
                $thumbnail->setWidth($tmp[1]);
                $thumbnail->setHeight($tmp[2]);
                $thumbnail->setIsIntermediate($tmp[3]);
            }
        }

        if(!$thumbId) {
            //try to load thumbnail from decorated post instead
            if($this->post instanceof PostThumbnailInterface) {
                $thumbnail = $this->post->getThumbnail();
            }
        }

        $this->thumbnails[$size] = $thumbnail;
        return $this->thumbnails[$size];
    }

    public function getPermalink()
    {
        return get_permalink($this->__get('ID'));
    }

    public function getCategories()
    {
        return get_the_category($this->__get('ID'));
    }

    /**
     * @param null $taxonomy
     * @return mixed|string
     * @throws \Exception
     */
    public function getTermString($taxonomy = null)
    {
        // Fix for same method in Schettke\Components\CustomPostType\PostType
        if(null === $taxonomy) {
            if (is_callable([$this->post, 'getTermString'])) {
                return call_user_func_array([$this->post, 'getTermString'], []);
            }
            else {
                throw new \Exception('Can\'t render termString, parameter "taxonomy" is mandatory but empty');
            }
        }

        $terms = get_the_terms($this->__get('ID'), $taxonomy);
        $recipe_cats = '';
        $j=0;
        if(is_array($terms)) {
            foreach ($terms as $filterindex => $filteritem) {
                if ($j > 0) $recipe_cats .= ', ';
                $recipe_cats .= $filteritem->name;
                $j++;
            }
        }

        return $recipe_cats;
    }

}