<?php
/**
 * Functions specific to Music custom post type
 * Created by PhpStorm.
 * User: prsolans
 * Date: 12/13/14
 * Time: 4:35 PM
 */


add_action('init', 'create_music_post_type');
/**
 * Create Music Custom Post Type
 */
function create_music_post_type()
{
    register_post_type('music',
        array(
            'labels' => array(
                'name' => __('Music'),
                'singular_name' => __('Music')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 4,
            'menu_icon' => 'dashicons-format-audio',
            'taxonomies' => array('category', 'post_tag'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
        )
    );
}

/**
 * Display table of music ratings for a specific author
 * @param array $posts - Collection of posts
 * @param string $username - Related to a specific author
 */
//function display_music_table($posts, $username)
//{
//    $usernameToLower = strtolower($username);
//
//    if ($posts) {
//        echo '<div class="rating-table">
//                            <h1>' . $username . '</h1>
//                            <table id="' . $usernameToLower . 'Scores">
//                                <thead>
//                                    <th>Music</th><th class="center">Service</th><th class="center">Food</th><th class="center">Ambiance</th></tr>
//                                </thead>
//                                <tbody>';
//        foreach ($posts as $post) {
//
//            echo '<tr ><td class="name-cell"><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . '</a ></td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_music_service', $post->ID) . '</td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_music_food', $post->ID) . '</td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_music_ambiance', $post->ID) . '</td ></tr >';
//        }
//
//        echo '</tbody></table></div>';
//    }
//}