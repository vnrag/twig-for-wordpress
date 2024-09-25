<?php
/**
 * Created by PhpStorm.
 * User: Electric-Ric
 * Date: 29/07/2018
 * Time: 20:37
 */

namespace Schettke\TwigForWordpress;


class PostLoader
{
    /**
     * Loads a wordpress post and attaches some extra information to it
     *
     * post->meta : metadata from get_post_meta
     * post->permalink : the permalink
     * post->thumbnail : the attached thumbnail or false if none.
     * the thumbnail has the fields "url", "width", "height" and "is_intermediate"
     * post->categories : the post categories
     *
     * @param $postId
     *
     * @return null|PostDecorator
     */
    public function load($postId)
    {
        $post = get_post($postId);
        if (null === $post) {
            return null;
        }
        $postDecorator = new PostDecorator($post);

        return $postDecorator;
    }

    /**
     * Takes an args array and returns decorated posts
     *
     * @param $queryArgs array args object for \WP_Query
     *
     * @return array an array of hydrated posts
     */
    public function loadAll($queryArgs)
    {
        $posts = get_posts($queryArgs);
        foreach ($posts as $key => $post) {
            $posts[$key] = new PostDecorator($post);
        }

        return $posts;
    }
}