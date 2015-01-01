<?php
/**
 * Created by PhpStorm.
 * User: prsolans
 * Date: 12/15/14
 * Time: 3:54 PM
 */


add_action('admin_menu', 'prs_ratings_admin_add_page');

function prs_ratings_admin_add_page()
{
    add_options_page('Custom Ratings Page', 'Custom Ratings', 'manage_options', 'prs-custom-ratings', 'prs_ratings_admin_page');
}

function prs_ratings_admin_page()
{
    ?>
    <div>
        <h2>PRS Custom Ratings Home</h2>

        <form action="options.php" method="post">
            <?php settings_fields('prs_ratings_options'); ?>
            <?php do_settings_fields('prs_ratings_options', 'prs_ratings_general'); ?>

            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>"/>
        </form>
    </div>
<?php
}

add_action('admin_init', 'prs_ratings_admin_init');

function prs_ratings_admin_init()
{
    register_setting('prs_ratings_options', 'prs_ratings_options', 'prs_ratings_options_validate');
    add_settings_section('prs_ratings_general', 'General Settings', 'prs_general_settings_text', 'prs_ratings_options');
    add_settings_field('prs_ratings_option_A', 'Option A', 'prs_general_setting_string', 'prs_ratings_options', 'prs_ratings_general');
}


function prs_general_settings_text()
{
    echo "<p>Ratings configuration options to come.</p>";
}

function prs_general_setting_string()
{
    $options = get_option('prs_rating_options');


}