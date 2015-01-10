<?php
/**
 * Plugin Name: AKC/PRS - Custom Ratings Posts
 * Description: Plugin for creating custom post type for rating experiences.
 * Version: 0.25
 * Author: prsolans
 * License: GPL2
 *
 * Date: 1/5/15
 * Time: 9:40 PM
 */

require_once('admin/options.php');

require_once('post-types/Experiences.php');
require_once('post-types/Restaurants.php');
require_once('post-types/Services.php');
require_once('post-types/Shops.php');


add_action('init', 'register_location_taxonomy');

/**
 * Register Location taxonomy for all relevant custom post types
 */
function register_location_taxonomy()
{
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name' => _x('Locations', 'taxonomy general name'),
        'singular_name' => _x('Location', 'taxonomy singular name'),
        'search_items' => __('Search Locations'),
        'all_items' => __('All Locations'),
        'parent_item' => __('Parent Location'),
        'parent_item_colon' => __('Parent Location:'),
        'edit_item' => __('Edit Location'),
        'update_item' => __('Update Location'),
        'add_new_item' => __('Add New Location'),
        'new_item_name' => __('New Location Name'),
        'menu_name' => __('Locations'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'location'),
    );

    register_taxonomy('location', array('experience', 'restaurant', 'service', 'shop'), $args);
}

/**
 * Collect posts and send to appropriate display function
 * @param string $posttype - Post type for the ratings
 * @param string $username - Author name whose table will be displayes
 */
function display_user_ratings_table($posttype, $username)
{
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $posttype
    ));

    if ($posttype == 'restaurant') {
        display_restaurant_table($posts, $username);
    } elseif ($posttype == 'experience') {
        display_experience_table($posts, $username);
    } elseif ($posttype == 'shop') {
        display_shop_table($posts, $username);
    } elseif ($posttype == 'service') {
        display_service_table($posts, $username);
    }
}

/**
 * Display To Do and On the Radar right sidebar for categories and post types
 * @param $posttype
 * @param $category
 * @return bool|null
 */
function display_category_to_do_list($posttype, $category)
{
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $posttype,
        'category_name' => $category,
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'Upcoming'
            )
        )
    ));


    if ($posts) {
        echo "<div class='rating-sidebar-block shadow-box-border'><h2>Upcoming</h2>";

        echo "<ul>";
        foreach ($posts AS $item) {
            $upcomingDate = get_upcoming_post_date($item->ID);
            echo "<li>" . $upcomingDate . " - <a href='" . get_permalink($item->ID) . "'>" . get_the_title($item->ID) . "</a></li>";
        }
        echo "</ul>";

        echo "</div>";

    } else {
        return false;
    }

    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $posttype,
        'category_name' => $category,
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'On the Radar'
            )
        )
    ));

    if ($posts) {
        echo "<div class='rating-sidebar-block shadow-box-border'><h2>On the Radar</h2>";

        echo "<ul>";
        foreach ($posts AS $item) {
            echo "<li><a href='" . get_permalink($item->ID) . "'>" . get_the_title($item->ID) . "</a></li>";
        }
        echo "</ul>";

        echo "</div>";
    } else {
        return false;
    }

    return null;
}

/**
 * Get formatted date for front-end list presentation
 * @param $postID
 * @return null|string
 */
function get_upcoming_post_date($postID)
{

    $post = get_post_meta($postID);

    if ($post) {

        $upcomingDate = new DateTime($post['upcoming_date'][0]);

        return $upcomingDate->format('m/d');
    }

    return null;
}

/**
 * Get headings for table presentation of posttype ratings
 * @param $posttype
 * @return array
 */
function get_table_headings($posttype)
{

    $headings = array();

    if ($posttype == 'restaurant') {
        $headings = array('Food', 'Service', 'Ambiance');
    } elseif ($posttype == 'experience') {
        $headings = array('Venue', 'Fun', 'Intangibles');
    } elseif ($posttype == 'service') {
        $headings = array('Ease', 'Quality', 'People');
    } elseif ($posttype == 'shop') {
        $headings = array('Ease', 'Quality', 'Ambiance');
    }

    return $headings;
}

/**
 * Get specific rating names for post type
 * @param $posttype
 * @return array
 */
function get_posttype_rating_types($posttype)
{
    $ratings = array();

    if ($posttype == 'restaurant') {
        $ratings = array('foodScore', 'serviceScore', 'ambianceScore');
    } elseif ($posttype == 'experience') {
        $ratings = array('venueScore', 'funScore', 'intangiblesScore');
    } elseif ($posttype == 'service') {
        $ratings = array('easeScore', 'qualityScore', 'peopleScore');
    } elseif ($posttype == 'shop') {
        $ratings = array('easeScore', 'qualityScore', 'ambianceScore');
    }

    return $ratings;
}

/**
 * Display table of overall ratings
 * @param string $posttype - Name of custom posttype being requested
 * @param string $category - Name of category name being requested
 */
function display_category_ratings_table($posttype, $category)
{
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $posttype,
        'category_name' => $category,
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'Been There, Done That',
            )
        )
    ));

    $cleanCategory = str_replace(' ', '-', strtolower($category));

    $heading = get_table_headings($posttype);
    $ratings = get_posttype_rating_types($posttype);

    if ($posts) {
        echo '<div class="rating-table overall-rating-table">
        <table id="overallScores-' . $cleanCategory . '" class="tablesorter">
            <thead>
                <th>' . $category . '</th>
                <th class="center">Overall</th>
                <th class="center collapsible">' . $heading[0] . '</th>
                <th class="center collapsible">' . $heading[1] . '</th>
                <th class="center collapsible">' . $heading[2] . '</th>
                <th class="center collapsible">Date</th>
            </thead>
            <tbody>';
        foreach ($posts as $post) {
            $scores = get_all_ratings($heading, $ratings, $posttype, $post->ID);

            $incomplete = '';
            if ($scores['incomplete'] == true) {
                $incomplete = '*';
            }

            echo '<tr ><td class="name-cell"><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . $incomplete . '</a ></td >';
            echo '<td class="center">' . $scores['overallScore'] . '</td >';
            echo '<td class="center collapsible">' . $scores[$ratings[0]] . '</td >';
            echo '<td class="center collapsible">' . $scores[$ratings[1]] . '</td >';
            echo '<td class="center collapsible">' . $scores[$ratings[2]] . '</td >';
            echo '<td class="center collapsible">' . get_the_date('m/d/y', $post->ID) . '</td ></tr >';
        }

        echo '</tbody></table><label>* - complete ratings to come</label></div>';
    } else {
        return false;
    }

    return null;
}

/**
 * Create array of all ratings for a single item
 * @param $postId
 * @return array
 */
function get_all_ratings($heading, $ratings, $posttype, $postId)
{

    $scores = get_ratings_for_single_post($heading, $posttype, $postId);

    $allScores = calculate_post_ratings($scores, $ratings);

    return $allScores;
}

/**
 * Get custom field ratings from post records
 * @param $heading
 * @param $posttype
 * @param $postId
 * @return array
 */
function get_ratings_for_single_post($heading, $posttype, $postId)
{

    $authors = array('prs', 'allykc');

    $fieldnames = array();

    foreach ($authors as $author) {
        array_push($fieldnames, $author . '_' . $posttype . '_' . strtolower($heading[0]));
        array_push($fieldnames, $author . '_' . $posttype . '_' . strtolower($heading[1]));
        array_push($fieldnames, $author . '_' . $posttype . '_' . strtolower($heading[2]));
    }

    $ratingsSubmitted = 0;

    $scores = array();

    foreach ($fieldnames as $rating) {
        if (get_field($rating, $postId)) {
            array_push($scores, get_field($rating, $postId));
            $ratingsSubmitted++;
        } else {
            array_push($scores, '0');
        }
    }

    $scores['count'] = $ratingsSubmitted;

    return $scores;
}

/**
 * Calculates the individual and combined scores for a post type
 * @param $scores
 * @param $ratings
 * @return array
 */
function calculate_post_ratings($scores, $ratings)
{
    //calculate combined scores
    $score1 = $scores[0] + $scores[3];
    $score2 = $scores[1] + $scores[4];
    $score3 = $scores[2] + $scores[5];
    $totalScore = $score1 + $score2 + $score3;

    if ($scores['count'] == 6) {
        $calculatedScores[$ratings[0]] = $score1 / 2;
        $calculatedScores[$ratings[1]] = $score2 / 2;
        $calculatedScores[$ratings[2]] = $score3 / 2;
        $calculatedScores['totalScore'] = $totalScore / 2;
        $calculatedScores['overallScore'] = round($totalScore / 6, 1);
        $calculatedScores['incomplete'] = false;
    } elseif ($scores['count'] == 3) {
        $calculatedScores[$ratings[0]] = $score1;
        $calculatedScores[$ratings[1]] = $score2;
        $calculatedScores[$ratings[2]] = $score3;
        $calculatedScores['totalScore'] = $totalScore;
        $calculatedScores['overallScore'] = round($totalScore / 3, 1);
        $calculatedScores['incomplete'] = true;
    } else {
        $calculatedScores[$ratings[0]] = '*';
        $calculatedScores[$ratings[1]] = '*';
        $calculatedScores[$ratings[2]] = '*';
        $calculatedScores['totalScore'] = '*';
        $calculatedScores['overallScore'] = '*';
        $calculatedScores['incomplete'] = true;
    }

    return $calculatedScores;
}

/**
 * Display sortable table of ratings for a range of different post types
 * @param $posttype
 */
function display_ratings_table($posttype)
{

    echo "<div class='two-thirds-left'><h2>Ratings</h2>";

    $catID = get_category_by_slug(get_the_title());

    if ($catID->parent == 0) {

        $args = array(
            'parent' => $catID->term_id,
            'taxonomy' => 'category'
        );

        $category = get_categories($args);

        if ($category) {

            foreach ($category AS $item) {
                display_category_ratings_table($posttype, $item->cat_name);
                ?>
                <script>
                    jQuery(document).ready(function () {
                            jQuery("#overallScores-<?php echo str_replace(' ', '-', strtolower($item->cat_name)); ?>").tablesorter({sortList: [[1, 1]]});
                        }
                    );
                </script>
            <?php
            }
        } else {
            display_category_ratings_table($posttype, get_the_title());
            ?>
            <script>
                jQuery(document).ready(function () {
                        jQuery("#overallScores-<?php echo $catID->slug; ?>").tablesorter({sortList: [[1, 1]]});
                    }
                );
            </script>
        <?php
        }
    } else {

        display_category_ratings_table($posttype, get_the_title());
        ?>
        <script>
            jQuery(document).ready(function () {
                    jQuery("#overallScores-<?php echo $catID->slug; ?>").tablesorter({sortList: [[1, 1]]});
                }
            );
        </script>
    <?php
    }

    ?>
</div>
<?php
}

/**
 * Display Upcoming and Radar lists on right sidebar of "display" category pages
 * @param $posttype
 */
function display_rating_sidebar($posttype)
{

    echo '<div class="one-third-right">';

    display_category_to_do_list($posttype, get_the_title());

    echo '</div>';
}

/**
 * Display ratings for current or previous month
 * @param bool $lastMonth - if true, display for last month
 */
function display_recent_ratings($lastMonth = false)
{
    $monthToDisplay = date('F');
    $numericalMonth = date('m');
    $yearToDisplay = date('Y');

    if ($lastMonth == true) {
        if ($monthToDisplay == 'January') {
            $yearToDisplay = date('Y', strtotime('-1 years'));
        }
        $monthToDisplay = date('F', strtotime('-1 months'));
        $numericalMonth = date('m', strtotime('-1 months'));
    }

    echo "<h2>Best of " . $monthToDisplay . "</h2>";

    $posts = get_posts(array(
        'numberposts' => 5,
        'post_type' => array('restaurant', 'experience', 'service', 'shop'),
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'Been There, Done That',
            )
        ),
        'orderby' => 'post_date',
        'order' => 'ASC',
        'date_query' => array(
            array(
                'year' => $yearToDisplay,
                'month' => $numericalMonth
            ),
        )
    ));
    if ($posts) {
        echo "<ul>";

        $i = 0;

        // CREATE ARRAY of recent ratings posts and details
        foreach ($posts AS $item) {
            $scores = get_all_ratings_for_a_restaurant($item->ID);

            $list[$i]['link'] = get_permalink($item->ID);
            $list[$i]['title'] = $item->post_title;
            $list[$i]['overallScore'] = $scores['overallScore'];
            $i++;
        }

        // SORT list items by overallScore
        usort($list, function ($a, $b) {
            return $b['overallScore'] - $a['overallScore'];
        });

        foreach ($list AS $item) {
            echo "<li><a href='" . $item['link'] . "'> " . $item['title'] . "</a> - " . $item['overallScore'] . "</li>";
        }
        echo "</ul>";

    } else {
        echo "Nothing to report so far this month.";
    }
}

/**
 * Display events marked as upcoming from all custom post types
 */
function display_upcoming_events()
{

    echo "<h2>Upcoming Fun Stuff</h2>";

    $posts = get_posts(array(
        'numberposts' => 5,
        'post_type' => array('restaurant', 'experience', 'service', 'shop'),
        'meta_query' => array(
            array(
                'key' => 'status',
                'value' => 'Upcoming',
            )
        ),
        'orderby' => 'post_date',
        'order' => 'ASC'
    ));

    echo "<ul>";
    if ($posts) {
        foreach ($posts AS $item) {
            echo "<li><a href='" . get_permalink($item->ID) . "'> " . $item->post_title . "</a></li>";
        }
    }
    echo "</ul>";
}

/**
 * Get formatted address from the Google Maps field
 * @param $location
 * @return string
 */
function get_location_address($location)
{
    $address = $location['address'];
    $lines = explode(',', $address);

    $htmlAddress = '';

    if ($lines) {
        $count = count($lines);

        for ($i = 0; $i < ($count - 3); $i++) {
            $htmlAddress .= $lines[$i] . '<br/>';

        }

        for ($i; $i < ($count - 2); $i++) {
            $htmlAddress .= $lines[$i] . ', ';
        }

        $htmlAddress .= $lines[$i];
    }
    return $htmlAddress;
}


