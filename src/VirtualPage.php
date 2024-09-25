<?php
  /**
   * Created by PhpStorm.
   * User: Electric-Ric
   * Date: 10/06/2018
   * Time: 12:57
   */

  namespace Schettke\TwigForWordpress;


  class VirtualPage
  {
    protected $args = array();

      /**
       * __construct
       * @param $slug
       * @param $title
       * @param null $content
       * @author Ohad Raz
       */
    function __construct($title, $slug, $content = null){
        $this->args['page_title'] = $title;
        $this->args['slug'] = $slug;
        $this->args['post_content'] = $content;
        add_filter('the_posts',array($this,'virtualPage'));
    }

    /**
     * catches the request and returns the page as if it was retrieved from the database
     * @param  array $posts
     * @return array
     * @author Ohad Raz
     */
    public function virtualPage($posts){
      global $wp,$wp_query;

      //check if user is requesting our fake page
      if( count($posts) == 0
          && (strtolower($wp->request) == $this->getPageSlug()
          || isset($wp->query_vars['page_id'])
          && $wp->query_vars['page_id'] == $this->getPageSlug())){

        //create a fake post
        $post = new \stdClass;
        $post->post_author = 1;
        $post->post_name = $this->getPageSlug();
        $post->guid = get_bloginfo('wpurl' . '/' . $this->getPageSlug());
        $post->post_title = $this->getPageTitle();
        //put your custom content here
        $post->post_content = $this->getPageContentFiltered();
        //just needs to be a number - negatives are fine
        $post->ID = null;
        $post->post_status = 'static';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->comment_count = 0;
        $post->post_type = 'schettkeVirtualPage';
        //dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
        $post->post_date = current_time('mysql');
        $post->post_date_gmt = current_time('mysql',1);

        $post = (object) array_merge((array) $post, (array) $this->args);
        $posts = NULL;
        $posts[] = $post;

        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        unset($wp_query->query["error"]);
        $wp_query->query_vars["error"]="";
        $wp_query->is_404 = false;
      }

      return $posts;
    }

    public function setPageTitle($pageTitle) {
      $this->args['page_title'] = $pageTitle;
    }

    public function getPageTitle() {
      return $this->args['page_title'];
    }

    public function setPageSlug($pageSlug) {
      $this->args['slug'] = $pageSlug;
    }

    public function getPageSlug() {
      return $this->args['slug'];
    }

    public function setPageContent($pageContent) {
      $this->args['post_content'] = $pageContent;
    }

    public function getPageContent() {
      return !empty($this->args['post_content']) ? $this->args['post_content'] : '';
    }

    public function getPageContentFiltered() {
      $content = $this->getPageContent();
      $content = str_replace(["\n","\r"], '', $content);

      return $content;
    }
  }