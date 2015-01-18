<?php
/**
 * Functions specific to Movies custom post type
 * Created by PhpStorm.
 * User: prsolans
 * Date: 1/10/15
 * Time: 4:35 PM
 */


add_action('init', 'create_movie_post_type');
/**
 * Create Movie Custom Post Type
 */
function create_movie_post_type()
{
    register_post_type('movie',
        array(
            'labels' => array(
                'name' => __('Movies'),
                'singular_name' => __('Movie')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 4,
            'menu_icon' => 'dashicons-format-video',
            'taxonomies' => array('category', 'post_tag'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
        )
    );
}