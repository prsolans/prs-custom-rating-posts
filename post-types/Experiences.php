<?php
/**
 * Functions specific to Experience custom post type
 * Date: 12/13/14
 * Time: 4:40 PM
 */

add_action('init', 'create_experience_post_type');
/**
 * Create Experience Custom Post Type
 */
function create_experience_post_type()
{
    register_post_type('experience',
        array(
            'labels' => array(
                'name' => __('Experiences'),
                'singular_name' => __('Experience')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 4,
            'menu_icon' => 'dashicons-smiley',
            'hierarchical' => true,
            'taxonomies' => array('category', 'post_tag', 'venue'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes')
        )
    );

    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name' => _x('Venues', 'taxonomy general name'),
        'singular_name' => _x('Venue', 'taxonomy singular name'),
        'search_items' => __('Search Venues'),
        'all_items' => __('All Venues'),
        'parent_item' => __('Parent Venue'),
        'parent_item_colon' => __('Parent Venue:'),
        'edit_item' => __('Edit Venue'),
        'update_item' => __('Update Venue'),
        'add_new_item' => __('Add New Venue'),
        'new_item_name' => __('New Venue Name'),
        'menu_name' => __('Venues'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'venue'),
    );

    register_taxonomy('venue', 'experience', $args);
}


/**
 * Display table of experience ratings for a specific author
 * @param array $posts - Collection of posts
 * @param string $username - Related to a specific author
 */
function display_experience_table($posts, $username)
{
    $usernameToLower = strtolower($username);

    if ($posts) {
        echo '<div class="rating-table">
            <h1>' . $username . '</h1>
            <table>
                <thead>
                    <th>Experience</th><th class="center">Venue</th><th class="center">Fun</th><th class="center">Intangibles</th></tr>
                </thead>
                <tbody>';
        foreach ($posts as $post) {
            echo '<tr ><td ><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . '</a ></td >';
            echo '<td class="center">' . get_field($usernameToLower . '_experience_venue', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_experience_fun', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_experience_intangibles', $post->ID) . '</td ></tr >';
        }

        echo '</tbody></table></div>';
    }
}

/**
 * Create array of all ratings for a single experience
 * @param $postId
 * @return array
 */
function get_all_ratings_for_an_experience($postId)
{
    // Confirm whether both authors have submitted reviews
    // TODO: Create more thorough test to confirm if a user has submitted reviews, create a flag for all three or something
    $divideBy = 1;
    $scores['incomplete'] = true;
    if (get_field('allykc_experience_venue', $postId) && get_field('prs_experience_venue', $postId)) {
        $divideBy = 2;
        $scores['incomplete'] = false;
    }

    $venueScore = (get_field('prs_experience_venue', $postId) + get_field('allykc_experience_venue', $postId)) / $divideBy;
    $funScore = (get_field('prs_experience_fun', $postId) + get_field('allykc_experience_fun', $postId)) / $divideBy;
    $intangiblesScore = (get_field('prs_experience_intangibles', $postId) + get_field('allykc_experience_intangibles', $postId)) / $divideBy;
    $totalScore = $venueScore + $funScore + $intangiblesScore;

    if ($totalScore == 0) {
        $scores['venueScore'] = '*';
        $scores['funScore'] = '*';
        $scores['intangiblesScore'] = '*';
        $scores['totalScore'] = '*';
        $scores['overallScore'] = '*';
    } else {
        $scores['venueScore'] = $venueScore;
        $scores['funScore'] = $funScore;
        $scores['intangiblesScore'] = $intangiblesScore;
        $scores['totalScore'] = $totalScore;
        $scores['overallScore'] = round($totalScore / 3, 1);
    }
    return $scores;
}