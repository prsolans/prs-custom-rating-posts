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

add_action('acf/input/admin_head', 'display_restaurant_admin_conditional_logic');

/**
 * Control conditional admin elements for Food items based upon category taxonomy selection
 * TODO: Add click action so that checking category items immediately affects admin field availability (instead of on refresh as currently set up)0
 */
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
                    if (jQuery('#in-category-20').is(':checked') || jQuery('#in-category-15').is(':checked')) { // if Restaurant OR Quick Eats
                        jQuery('#acf-allykc_restaurant_food').hide();
                        jQuery('#acf-prs_restaurant_food').show();
                    }
                    if (jQuery('#in-category-21').is(':checked')) { // if Bar
                        jQuery('#acf-allykc_restaurant_crowd').hide();
                        jQuery('#acf-prs_restaurant_crowd').show();
                    }
                });

                // Allykc ratings conditional logic
                jQuery('a[data-key="field_548a22dacbb7e"]').click(function (event) {
                    if (jQuery('#in-category-20').is(':checked') || jQuery('#in-category-15').is(':checked')) { // if Restaurant OR Quick Eats
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


/**
 * Create array of ratings that have been submitted for a restaurant by a specific author
 * @param $author
 * @param $postId
 * @return array
 */
function get_restaurant_ratings_by_author($author, $postId)
{

    $serviceField = $author . '_restaurant_service';
    $foodField = $author . '_restaurant_food';
    $crowdField = $author . '_restaurant_crowd';
    $ambianceField = $author . '_restaurant_ambiance';

    $ratings = array();

    if (get_field($serviceField, $postId)) {
        $ratings['Service'] = get_field($serviceField, $postId);
    }
    if (get_field($foodField, $postId)) {
        $ratings['Food'] = get_field($foodField, $postId);
    }
    if (get_field($crowdField, $postId)) {
        $ratings['Crowd'] = get_field($crowdField, $postId);
    }
    if (get_field($ambianceField, $postId)) {
        $ratings['Ambiance'] = get_field($ambianceField, $postId);
    }

    return $ratings;
}

/**
 * Display table of ratings submitted for a restaurant by a specific author
 *     // TODO: Improve on strtoupper usage - get actual author display name
 * @param $author
 */
function display_restaurant_ratings_by_author($author)
{

    $postId = get_the_ID();

    $ratings = get_restaurant_ratings_by_author($author, $postId);

    if($ratings) {

        echo '<div class="rating-block"><h3>' . strtoupper($author) . ' says</h3>';

        foreach ($ratings AS $key => $value) {
            echo "<label>";
            echo $key;
            echo ":</label>";
            echo $value;
            echo "<br/>";
        }
        echo '</div>';
    }
}

/**
 * Generate overall score based upon all ratings submitted by both authors
 * @return float
 */
function get_overall_restaurant_ratings($postID)
{

    $prs = get_restaurant_ratings_by_author('prs', $postID);
    $allykc = get_restaurant_ratings_by_author('allykc', $postID);

    $prsScore = 0;
    $allykcScore = 0;

    $ratings = array();
    $ratings['count'] = 0;

    foreach ($prs AS $rating) {
        $prsScore = $prsScore + $rating;
    }

    foreach ($allykc AS $rating) {
        $allykcScore = $allykcScore + $rating;
    }

    if((count($prs) + count($allykc)) > 0) {
        if(count($prs) > 0){ $ratings['count']++;}
        if(count($allykc) > 0){ $ratings['count']++;}
        $ratings['overallScore'] = round(($prsScore + $allykcScore) / (count($prs) + count($allykc)), 1);
        return $ratings;
    }

    return false;
}




/**
 * UNUSED - Display table of restaurant ratings for a specific author
 * @param array $posts - Collection of posts
 * @param string $username - Related to a specific author
 */
//function display_restaurant_table($posts, $username)
//{
//    $usernameToLower = strtolower($username);
//
//    if ($posts) {
//        echo '<div class="rating-table">
//                            <h1>' . $username . '</h1>
//                            <table id="' . $usernameToLower . 'Scores">
//                                <thead>
//                                    <th>Restaurant</th><th class="center">Service</th><th class="center">Food</th><th class="center">Ambiance</th></tr>
//                                </thead>
//                                <tbody>';
//        foreach ($posts as $post) {
//
//            echo '<tr ><td class="name-cell"><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . '</a ></td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_service', $post->ID) . '</td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_food', $post->ID) . '</td >';
//            echo '<td class="center">' . get_field($usernameToLower . '_restaurant_ambiance', $post->ID) . '</td ></tr >';
//        }
//
//        echo '</tbody></table></div>';
//    }
//}
//

/**
 * Create array of all ratings for a single restaurant
 * @param $postId
 * @return array
 */
//function get_all_ratings_for_a_restaurant($postId)
//{
//    // Confirm whether both authors have submitted reviews
//    $divideBy = 1;
//    $scores['incomplete'] = true;
//    if (get_field('allykc_restaurant_service', $postId) && get_field('prs_restaurant_service', $postId)) {
//        $divideBy = 2;
//        $scores['incomplete'] = false;
//    }
//
//    $serviceScore = (get_field('prs_restaurant_service', $postId) + get_field('allykc_restaurant_service', $postId)) / $divideBy;
//    $ambianceScore = (get_field('prs_restaurant_ambiance', $postId) + get_field('allykc_restaurant_ambiance', $postId)) / $divideBy;
//
//    $foodScore = (get_field('prs_restaurant_food', $postId) + get_field('allykc_restaurant_food', $postId)) / $divideBy;
//
//    $totalScore = $serviceScore + $foodScore + $ambianceScore;
//
//    if ($totalScore == 0) {
//        $scores['serviceScore'] = '*';
//        $scores['foodScore'] = '*';
//        $scores['ambianceScore'] = '*';
//        $scores['totalScore'] = '*';
//        $scores['overallScore'] = '*';
//    } else {
//        $scores['serviceScore'] = $serviceScore;
//        $scores['foodScore'] = $foodScore;
//        $scores['ambianceScore'] = $ambianceScore;
//        $scores['totalScore'] = $totalScore;
//        $scores['overallScore'] = round($totalScore / 3, 1);
//    }
//    return $scores;
//}