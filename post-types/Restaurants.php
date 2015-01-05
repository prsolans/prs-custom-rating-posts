<?php
/**
 * Functions specific to Restaurant custom post type
 * Created by PhpStorm.
 * User: prsolans
 * Date: 12/13/14
 * Time: 4:35 PM
 */


add_action('init', 'create_restaurant_post_type');
/**
 * Create Restaurant Custom Post Type
 */
function create_restaurant_post_type()
{
    register_post_type('restaurant',
        array(
            'labels' => array(
                'name' => __('Restaurants'),
                'singular_name' => __('Restaurant')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_position' => 4,
            'menu_icon' => 'dashicons-location',
            'taxonomies' => array('category', 'post_tag'),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail')
        )
    );

}

/**
 * Display table of restaurant ratings for a specific author
 * @param array $posts - Collection of posts
 * @param string $username - Related to a specific author
 */
function display_restaurant_table($posts, $username)
{
    $usernameToLower = strtolower($username);

    if ($posts) {
        echo '<div class="rating-table">
                            <h1>' . $username . '</h1>
                            <table id="' . $usernameToLower . 'Scores">
                                <thead>
                                    <th>Restaurant</th><th class="center">Service</th><th class="center">Food</th><th class="center">Ambiance</th></tr>
                                </thead>
                                <tbody>';
        foreach ($posts as $post) {

            echo '<tr ><td class="name-cell"><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . '</a ></td >';
            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_service', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_food', $post->ID) . '</td >';
            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_ambiance', $post->ID) . '</td ></tr >';
        }

        echo '</tbody></table></div>';
    }
}

/**
 * Create array of all ratings for a single restaurant
 * @param $postId
 * @return array
 */
function get_all_ratings_for_a_restaurant($postId)
{
    // Confirm whether both authors have submitted reviews
    // TODO: Create more thorough test to confirm if a user has submitted reviews, create a flag for all three or something
    $divideBy = 1;
    $scores['incomplete'] = true;
    if (get_field('allykc_restaurant_service', $postId) && get_field('prs_restaurant_service', $postId)) {
        $divideBy = 2;
        $scores['incomplete'] = false;
    }

    $serviceScore = (get_field('prs_restaurant_service', $postId) + get_field('allykc_restaurant_service', $postId)) / $divideBy;
    $foodScore = (get_field('prs_restaurant_food', $postId) + get_field('allykc_restaurant_food', $postId)) / $divideBy;
    $ambianceScore = (get_field('prs_restaurant_ambiance', $postId) + get_field('allykc_restaurant_ambiance', $postId)) / $divideBy;
    $totalScore = $serviceScore + $foodScore + $ambianceScore;

    if ($totalScore == 0) {
        $scores['serviceScore'] = '*';
        $scores['foodScore'] = '*';
        $scores['ambianceScore'] = '*';
        $scores['totalScore'] = '*';
        $scores['overallScore'] = '*';
    } else {
        $scores['serviceScore'] = $serviceScore;
        $scores['foodScore'] = $foodScore;
        $scores['ambianceScore'] = $ambianceScore;
        $scores['totalScore'] = $totalScore;
        $scores['overallScore'] = round($totalScore / 3, 1);
    }
    return $scores;
}

add_action('acf/input/admin_head', 'display_restaurant_admin_conditional_logic');

function display_restaurant_admin_conditional_logic()
{
    ?>

    <style>
        #acf-prs_restaurant_food, #acf-allykc_restaurant_food, #acf-prs_restaurant_crowd, #acf-allykc_restaurant_crowd {
            display: none;
        }
    </style>

    <script>
        jQuery(document).live('acf/setup_fields', function (e, postbox) {

                // PRS ratings conditional logic
                jQuery('a[data-key="field_549313af85ef6"]').click(function (event) {
                    if (jQuery('#in-category-20').is(':checked')) {
                        jQuery('#acf-allykc_restaurant_food').hide();
                        jQuery('#acf-prs_restaurant_food').show();
                    }
                    if (jQuery('#in-category-21').is(':checked')) {
                        jQuery('#acf-allykc_restaurant_crowd').hide();
                        jQuery('#acf-prs_restaurant_crowd').show();
                    }
                });

                // Allykc ratings conditional logic
                jQuery('a[data-key="field_548a22dacbb7e"]').click(function (event) {
                    if (jQuery('#in-category-20').is(':checked')) { // if Restaurant
                        jQuery('#acf-prs_restaurant_food').hide();
                        jQuery('#acf-allykc_restaurant_food').show();
                    }
                    if (jQuery('#in-category-21').is(':checked')) { // if Bar
                        jQuery('#acf-prs_restaurant_crowd').hide();
                        jQuery('#acf-allykc_restaurant_crowd').show();
                    }
                });

                // Ratings tab
                jQuery('a[data-key="field_54a9c37379194"]').click(function (event) {
                    jQuery('#acf-prs_restaurant_food').hide();
                    jQuery('#acf-allykc_restaurant_food').hide();
                    jQuery('#acf-prs_restaurant_crowd').hide();
                    jQuery('#acf-allykc_restaurant_crowd').hide();
                });
            }
        );
    </script>
<?php
}

