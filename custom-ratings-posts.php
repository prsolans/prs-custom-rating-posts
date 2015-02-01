<?php
/**
 * Plugin Name: AKC/PRS - Custom Ratings Posts
 * Description: Plugin for creating custom post type for rating experiences.
 * Version: 0.35
 * Author: prsolans
 * License: GPL2
 *
 * Date: 1/5/15
 * Time: 9:40 PM
 */

require_once('admin/options.php');

require_once('post-types/Experiences.php');
require_once('post-types/Movies.php');
require_once('post-types/Restaurants.php');
require_once('post-types/Services.php');
require_once('post-types/Shops.php');

require_once('lib/OAuth.php');

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
function get_table_headings($posttype, $category)
{

    $headings = array();

    if ($posttype == 'restaurant') {
        if ($category == 'Bars') {
            $headings = array('Service', 'Crowd', 'Ambiance');
        } else {
            $headings = array('Food', 'Service', 'Ambiance');
        }
    } elseif ($posttype == 'experience') {
        $headings = array('Venue', 'Fun', 'Intangibles');
    } elseif ($posttype == 'service') {
        $headings = array('Ease', 'Quality', 'People');
    } elseif ($posttype == 'shop') {
        $headings = array('Ease', 'Quality', 'Ambiance');
    } elseif ($posttype == 'movie') {
        $headings = array('Rating');
    }

    return $headings;
}

/**
 * Get specific rating names for post type
 * @param $posttype
 * @return array
 */
function get_posttype_rating_types($posttype, $category)
{
    $ratings = array();

    if ($posttype == 'restaurant') {
        if ($category == 'Bars') {
            $ratings = array('serviceScore', 'crowdScore', 'ambianceScore');
        } else {
            $ratings = array('foodScore', 'serviceScore', 'ambianceScore');
        }
    } elseif ($posttype == 'experience') {
        $ratings = array('venueScore', 'funScore', 'intangiblesScore');
    } elseif ($posttype == 'service') {
        $ratings = array('easeScore', 'qualityScore', 'peopleScore');
    } elseif ($posttype == 'shop') {
        $ratings = array('easeScore', 'qualityScore', 'ambianceScore');
    } elseif ($posttype == 'movie') {
        $ratings = array('ratingScore');
    }

    return $ratings;
}

/**
 * Display table of overall ratings
 * @param string $posttype - Name of custom posttype being requested
 * @param string $category - Name of category name being requested
 */
function  display_category_ratings_table($posttype, $category)
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

    $heading = get_table_headings($posttype, $category);
    $ratings = get_posttype_rating_types($posttype, $category);

    if ($posts) {
        echo '<div class="rating-table overall-rating-table">
        <table id="overallScores-' . $cleanCategory . '" class="tablesorter">
            <thead>
                <th>' . $category . '</th>
                <th class="center">Overall</th>';
        if ($posttype != 'movie') {
            echo '<th class="center collapsible" > ' . $heading[0] . '</th><th class="center collapsible" > ' . $heading[1] . '</th><th class="center collapsible" > ' . $heading[2] . '</th>';
        }
        echo '<th class="center collapsible"> Date</th>
            </thead >
            <tbody > ';
        foreach ($posts as $post) {
            $scores = get_all_ratings($heading, $ratings, $posttype, $post->ID);


            $incomplete = '';
            if ($scores['incomplete'] == true) {
                $incomplete = ' * ';
            }

            echo '<tr><td class="name-cell" ><a href = "' . get_permalink($post->ID) . '" > ' . get_the_title($post->ID) . $incomplete . '</a ></td>';
            echo '<td class="center" > ' . $scores['overallScore'] . ' </td > ';
            if ($posttype != 'movie') {
                echo '<td class="center collapsible" > ' . $scores[$ratings[0]] . '</td > ';
                echo '<td class="center collapsible" > ' . $scores[$ratings[1]] . '</td > ';
                echo '<td class="center collapsible" > ' . $scores[$ratings[2]] . '</td > ';
            }
            echo '<td class="center collapsible" > ' . get_the_date('m / d / y', $post->ID) . ' </td ></tr>';
        }

        echo '</tbody ></table ><label >* -complete ratings to come </label ></div > ';
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

    if ($posttype != 'movie') {
        $allScores = calculate_post_ratings($scores, $ratings);
    } else {
        $allScores = calculate_movie_rating($scores, $ratings);
    }

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
        if ($posttype != 'movie') {
            array_push($fieldnames, $author . '_' . $posttype . '_' . strtolower($heading[1]));
            array_push($fieldnames, $author . '_' . $posttype . '_' . strtolower($heading[2]));
        }
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
        $calculatedScores[$ratings[0]] = ' * ';
        $calculatedScores[$ratings[1]] = ' * ';
        $calculatedScores[$ratings[2]] = ' * ';
        $calculatedScores['totalScore'] = ' * ';
        $calculatedScores['overallScore'] = ' * ';
        $calculatedScores['incomplete'] = true;
    }

    return $calculatedScores;
}

function calculate_movie_rating($scores, $ratings)
{
    $calculatedScores['overallScore'] = $scores[0];
    $calculatedScores['incomplete'] = false;
    if ($scores['count'] == 2) {
        $calculatedScores['overallScore'] = ($scores[0] + $scores[1]) / 2;
        $calculatedScores['incomplete'] = false;
    }
    return $calculatedScores;
}

/**
 * Display sortable table of ratings for a range of different post types
 * @param $posttype
 */
function display_ratings_table($posttype)
{

    echo "<h2>Ratings</h2>";

    $catID = get_category_by_slug(get_the_title());

    if ($catID->parent == 0) {

        $args = array(
            'parent' => $catID->term_id,
            'taxonomy' => 'category'
        );

        $category = get_categories($args);
        debug_to_console($category);
        if ($category) {

            foreach ($category AS $item) {
                display_category_ratings_table($posttype, $item->cat_name);
                ?>
                <script>
                    jQuery(document).ready(function () {
                            jQuery("#overallScores-<?php echo str_replace(' ', ' - ', strtolower($item->cat_name)); ?>").tablesorter({sortList: [[1, 1]]});
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

}

/**
 * Display Upcoming and Radar lists on right sidebar of "display" category pages
 * @param $posttype
 */
function display_rating_sidebar($posttype)
{

    echo '<div class="one-third-right" >';

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
            $yearToDisplay = date('Y', strtotime(' - 1 years'));
        }
        $monthToDisplay = date('F', strtotime(' - 1 months'));
        $numericalMonth = date('m', strtotime(' - 1 months'));
    }

    echo "<h2>Best of " . $monthToDisplay . "</h2>";

    //TODO: Include other post types than Restaurant on recent ratings list
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => array('restaurant'),
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
            if (get_overall_restaurant_ratings($item->ID) != false) {
                $ratings = get_overall_restaurant_ratings($item->ID);
                $list[$i]['link'] = get_permalink($item->ID);
                $list[$i]['title'] = $item->post_title;
                $list[$i]['overallScore'] = $ratings['overallScore'];
                $i++;
            }
        }

        // SORT list items by overallScore
        usort($list, function ($a, $b) {
            return $b['overallScore'] > $a['overallScore'];
        });

        $list = array_slice($list, 0, 5, true);

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
            $htmlAddress .= $lines[$i] . ' < br />';

        }

        for ($i; $i < ($count - 2); $i++) {
            $htmlAddress .= $lines[$i] . ', ';
        }

        $htmlAddress .= $lines[$i];
    }
    return $htmlAddress;
}

function get_location()
{

    global $post;

    $terms = get_the_terms($post->ID, 'location');

    if (!empty($terms)) {
        foreach ($terms AS $term) {
            if ($term->parent == 0) {
                $location = $term->name;
            }
        }

        return $location;
    }

    return false;
}

function get_posttype_category()
{
    global $post;

    $terms = get_the_terms($post->ID, 'category');

    if (!empty($terms)) {
        foreach ($terms AS $term) {
            if ($term->parent == 0) {
                $category = $term->name;
            }
        }

        return $category;
    }

    return false;
}

/**
 * Defines the function used to initial the cURL library.
 *
 * @param  string $url To URL to which the request is being made
 * @return string  $response   The response, if available; otherwise, null
 */
function curl($url)
{

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, '');
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($curl);
    if (0 !== curl_errno($curl) || 200 !== curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
        $response = null;
    } // end if
    curl_close($curl);

    return $response;

} // end curl

function get_foursquare_data($name, $location)
{
    $client_id = FOURSQUARE_CLIENT_ID;
    $client_secret = FOURSQUARE_CLIENT_SECRET;

    // Search query
    $query = 'http://api.foursquare.com/v2/venues/search?';
    $query .= 'v=20150115';
    $query .= '&intent=browse';
    $query .= '&client_id=' . $client_id;
    $query .= '&client_secret=' . $client_secret;
    $query .= '&near=' . rawurlencode($location);
    $query .= '&query=' . rawurlencode($name);
    $query .= '&limit=1';

    $data = get_external_data($query);
    // debug
//    echo "<pre>";
//    print_r($data);
//    echo "</pre>";
    // end debug

    if ($data['meta']['code'] == '200') {
        $venue = $data['response']['venues'][0];

        if (strcmp(html_entity_decode($venue['name']), html_entity_decode($name)) == 0) {

            $venueInfo = array();
            if (isset($venue['location']['lat'])) {
                $venueInfo['lat'] = $venue['location']['lat'];
                $venueInfo['lng'] = $venue['location']['lng'];
            }
            if (isset($venue['location']['formattedAddress'][0])) {
                $venueInfo['streetAddress0'] = $venue['location']['formattedAddress'][0];
            }
            if (isset($venue['location']['formattedAddress'][1])) {
                $venueInfo['streetAddress1'] = $venue['location']['formattedAddress'][1];
            }
            if (isset($venue['location']['formattedAddress'][2])) {
                $venueInfo['streetAddress2'] = $venue['location']['formattedAddress'][2];
            }
            if (isset($venue['url'])) {
                $venueInfo['url'] = $venue['url'];
            }
            if (isset($venue['reservations']['url'])) {
                $venueInfo['reservations'] = $venue['reservations']['url'];
            }
            if (isset($venue['id'])) {
                $q2 = 'http://api.foursquare.com/v2/venues/' . $venue['id'];
                $q2 .= '?v=20150115';
                $q2 .= '&client_id=' . $client_id;
                $q2 .= '&client_secret=' . $client_secret;
                $data = get_external_data($q2);

                if ($data['meta']['code'] == '200') {

                    $venueImages = $data['response']['venue']['photos']['groups'];

                    if ($venueImages[0]['count'] < 3) {
                        $picturesToShow = $venueImages[0]['count'];
                    } else {
                        $picturesToShow = 2;
                    }
                    for ($picturesToShow; $picturesToShow > -1; $picturesToShow--) {
                        $path = $venueImages[0]['items'][$picturesToShow]['prefix'] . '250x250' . $venueImages[0]['items'][$picturesToShow]['suffix'];
                        echo "<img class='fs-image' src='" . $path . "'/>";
                    }

                    if (isset($data['response']['venue']['rating'])) {
                        $venueInfo['rating'] = $data['response']['venue']['rating'];
                    }
                    if (isset($data['response']['venue']['ratingSignals'])) {
                        $venueInfo['ratingSignals'] = $data['response']['venue']['ratingSignals'];
                    }
                }
            }
            return $venueInfo;
        }
    }

    return false;
}

function randString($length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count - 1)];
    }
    return $str;
}

$CONSUMER_KEY = YELP_CONSUMER_KEY;
$CONSUMER_SECRET = YELP_CONSUMER_SECRET;
$TOKEN = YELP_TOKEN;
$TOKEN_SECRET = YELP_TOKEN_SECRET;


$API_HOST = 'api.yelp.com';
//$DEFAULT_TERM = 'dinner';
//$DEFAULT_LOCATION = 'San Francisco, CA';
$SEARCH_LIMIT = 1;
$SEARCH_PATH = '/v2/search';
$BUSINESS_PATH = '/v2/business';

/**
 * Makes a request to the Yelp API and returns the response
 *
 * @param    $host    The domain host of the API
 * @param    $path    The path of the APi after the domain
 * @return   The JSON response from the request
 */
function request($host, $path) {
    $unsigned_url = "http://" . $host . $path;
    // Token object built using the OAuth library
    $token = new OAuthToken($GLOBALS['TOKEN'], $GLOBALS['TOKEN_SECRET']);

    debug_to_console($token);
    // Consumer object built using the OAuth library
    $consumer = new OAuthConsumer($GLOBALS['CONSUMER_KEY'], $GLOBALS['CONSUMER_SECRET']);
    debug_to_console($consumer);
    // Yelp uses HMAC SHA1 encoding
    $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
    $oauthrequest = OAuthRequest::from_consumer_and_token(
        $consumer,
        $token,
        'GET',
        $unsigned_url
    );

    // Sign the request
    $oauthrequest->sign_request($signature_method, $consumer, $token);

    // Get the signed URL
    $signed_url = $oauthrequest->to_url();

    debug_to_console($signed_url);

    // Send Yelp API Call
    $ch = curl_init($signed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}
/**
 * Query the Search API by a search term and location
 *
 * @param    $term        The search term passed to the API
 * @param    $location    The search location passed to the API
 * @return   The JSON response from the request
 */
function search($term, $location) {
    $url_params = array();

    $url_params['term'] = $term ?: $GLOBALS['DEFAULT_TERM'];
    $url_params['location'] = $location?: $GLOBALS['DEFAULT_LOCATION'];
    $url_params['limit'] = $GLOBALS['SEARCH_LIMIT'];
    $search_path = $GLOBALS['SEARCH_PATH'] . "?" . http_build_query($url_params);

    debug_to_console($search_path);

    return request($GLOBALS['API_HOST'], $search_path);
}
/**
 * Query the Business API by business_id
 *
 * @param    $business_id    The ID of the business to query
 * @return   The JSON response from the request
 */
function get_business($business_id) {
    $business_path = $GLOBALS['BUSINESS_PATH'] . $business_id;

    return request($GLOBALS['API_HOST'], $business_path);
}
/**
 * Queries the API by the input values from the user
 *
 * @param    $term        The search term to query
 * @param    $location    The location of the business to query
 */
function query_api($term, $location) {
    $response = json_decode(search($term, $location));
    $business_id = $response->businesses[0]->id;

    print sprintf(
        "%d businesses found, querying business info for the top result \"%s\"\n\n",
        count($response->businesses),
        $business_id
    );

    $response = get_business($business_id);

    print sprintf("Result for business \"%s\" found:\n", $business_id);
    print "$response\n";
}

function get_yelp_data($name, $location)
{



    $location = rawurlencode($location);
    $term = rawurlencode($name);

    debug_to_console($location . '-' . $term);

//    $client_id = YELP_CONSUMER_KEY;
//    $client_secret = YELP_CONSUMER_SECRET;
//    $client_token = YELP_TOKEN;
//    $client_token_secret = YELP_TOKEN_SECRET;

//    $query_timestamp = time();
//    $signature_method = 'HMAC-SHA1';
//    $generated_nonce = randString('9');
//
//    // Search query
//    $query = 'http://api.yelp.com/v2/search?';
//    $query .= '&oauth_consumer_key=' . $client_id;
//    $query .= '&oauth_token=' . $client_token;
//    $query .= '&oauth_signature_method=' . $signature_method;
//    $query .= '&oauth_signature=' . $client_secret;
//    $query .= '&oauth_timestamp=' . $query_timestamp;
//    $query .= '&oauth_nonce=' . $generated_nonce;
//    $query .= '&location=' . rawurlencode($location);
//    $query .= '&term=' . rawurlencode($name);
//    $query .= '&limit=1';

    query_api($term, $location);

  //  debug_to_console($query);
}

function get_external_data($query)
{
    debug_to_console($query);

    debug_to_console("wp_remote_get attempt...");
    $result = wp_remote_get($query);

    if (is_wp_error($result)) {
        debug_to_console("file_get_contents attempt...");
        $result = file_get_contents($query);
        if ($result == false) {
            // And if that doesn't work, then we'll try curl
            debug_to_console("curl attempt...");
            $result = $this->curl($query);
            if (null == $result) {
                $result = 0;
            } // end if/else
        }
        $data = json_decode($result, true);
    } else {
        $response = wp_remote_retrieve_body($result);
        $data = json_decode($response, true);
    }

    return $data;
}
/**
 * UNUSED - Collect posts and send to appropriate display function
 * @param string $posttype - Post type for the ratings
 * @param string $username - Author name whose table will be displayes
 */
//function display_user_ratings_table($posttype, $username)
//{
//    $posts = get_posts(array(
//        'numberposts' => -1,
//        'post_type' => $posttype
//    ));
//
//    if ($posttype == 'restaurant') {
//        display_restaurant_table($posts, $username);
//    } elseif ($posttype == 'experience') {
//        display_experience_table($posts, $username);
//    } elseif ($posttype == 'shop') {
//        display_shop_table($posts, $username);
//    } elseif ($posttype == 'service') {
//        display_service_table($posts, $username);
//    }
//}

