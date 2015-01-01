<?php
/**
 * Functions specific to Shop custom post type
 * Created by PhpStorm.
 * User: prsolans
 * Date: 12/13/14
 * Time: 4:42 PM
 */

add_action('init', 'create_shop_post_type');
/**
 * Create Shop Custom Post Type
 */
function create_shop_post_type()
{
    register_post_type('shop',
        array(
            'labels' => array(
                'name' => __('Shops'),
                'singular_name' => __('Shop')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 4,
            'menu_icon' => 'dashicons-cart',
            'taxonomies' => array('category', 'post_tag'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
        )
    );

}

/**
 * Display table of shop ratings for a specific author
 * @param array $posts - Collection of posts
 * @param string $username - Related to a specific author
 */
function display_shop_table($posts, $username)
{
    $usernameToLower = strtolower($username);

    if ($posts) {
        echo '<div class="rating-table">
            <h1>' . $username . '</h1>
            <table>
                <thead>
                    <th>Shop</th><th class="center">Ease</th><th class="center">Quality</th><th class="center">Ambiance</th></tr>
                </thead>
                <tbody>';
        foreach ($posts as $post) {
            echo '<tr ><td ><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . '</a ></td >';
            echo '<td class="center">' . get_field($usernameToLower . '_shop_ease', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_shop_quality', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_shop_ambiance', $post->ID) . '</td ></tr >';
        }

        echo '</tbody></table></div>';
    }
}

/**
 * Create array of all ratings for a single service
 * @param $postId
 * @return array
 */
function get_all_ratings_for_a_shop($postId)
{
    // Confirm whether both authors have submitted reviews
    // TODO: Create more thorough test to confirm if a user has submitted reviews, create a flag for all three or something
    $divideBy = 1;
    $scores['incomplete'] = true;
    if (get_field('prs_shop_ease', $postId) && get_field('allykc_shop_ease', $postId)) {
        $divideBy = 2;
        $scores['incomplete'] = false;
    }

    $easeScore = (get_field('prs_shop_ease', $postId) + get_field('allykc_shop_ease', $postId)) / $divideBy;
    $qualityScore = (get_field('prs_shop_quality', $postId) + get_field('allykc_shop_quality', $postId)) / $divideBy;
    $ambianceScore = (get_field('prs_shop_ambiance', $postId) + get_field('allykc_shop_ambiance', $postId)) / $divideBy;
    $totalScore = $easeScore + $qualityScore + $ambianceScore;

    if ($totalScore == 0) {
        $scores['easeScore'] = '*';
        $scores['qualityScore'] = '*';
        $scores['ambianceScore'] = '*';
        $scores['totalScore'] = '*';
        $scores['overallScore'] = '*';
    } else {
        $scores['easeScore'] = $easeScore;
        $scores['qualityScore'] = $qualityScore;
        $scores['ambianceScore'] = $ambianceScore;
        $scores['totalScore'] = $totalScore;
        $scores['overallScore'] = round($totalScore / 3, 1);
    }
    return $scores;
}