<?php
/*
Plugin Name: Super Plugin
Description: This is a super plugin
Version: 1.0
Author: John Doe
Author URI: http://www.example.com
Text Domain: wcpdomain
Domain Path: /languages
*/


// custom function to help out in syntax and repetition
function custom_getoption($optionName)
{
    $default = '';
    switch ($optionName) {
        case 'wcp_wordcount':
            $default = '1';
            break;
        case 'wcp_charactercount':
            $default = '1';
            break;
        case 'wcp_readtime':
            $default = '1';
            break;
        case 'wcp_location':
            $default = '0';
            break;
        case 'wcp_headline':
            $default = 'Post statistics';
            break;
        default:
            break;
    }
    return get_option($optionName, $default);
}
class WordCountAndTimePlugin
{
    function __construct()
    {
        // Add an action to hook into the admin menu
        add_action('admin_menu', array($this, 'adminPage'));

        // Add an action to hook into the admin initialization
        add_action('admin_init', array($this, 'settings'));

        // Add a filter to hook into the content
        add_filter('the_content', array($this, 'ifWrap'));

        add_action('init', array($this, 'languages'));
    }
    function languages()
    {
        load_plugin_textdomain('wcpdomain', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    function ifWrap($content)
    {
        if (
            is_main_query() and is_single() and
            (
                // Check if any of these options are enabled
                custom_getoption('wcp_wordcount') or
                custom_getoption('wcp_charactercount') or
                custom_getoption('wcp_readtime')
            )
        ) {
            return $this->createHTML($content);
        }
        return $content;
    }

    // Function to create the HTML for the content
    function createHTML($content)
    {
        $html = '<h3>' . esc_html(custom_getoption('wcp_headline')) . '</h3><p>';

        //  get word count once because both word count and read time use it
        if (custom_getoption('wcp_wordcount') or custom_getoption('wcp_readtime')) {
            $wordCount = str_word_count(strip_tags($content));
        }
        if (custom_getoption('wcp_wordcount')) {
            $html .= __('This post has', 'wcpdomain') . ' ' . $wordCount . ' words. <br>';
        }

        if (custom_getoption('wcp_charactercount')) {
            $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters. <br>';
        }
        $html .= '</p>';


        if (custom_getoption('wcp_readtime')) {
            $html .= 'This post will take about ' . round($wordCount / 225) . ' minute(s) to read.';
        }


        if (custom_getoption('wcp_location') == '0') {
            return $html . $content;
        }

        return $content . $html;
    }




    // Function to sanitize the location option
    function sanitizeLocation($input)
    {
        if ($input != '0' and $input != '1') {
            add_settings_error('wcp_location', 'wcp_location_error', 'Display location must be one of the options');
            return get_option('wcp_location');
        }
        return $input;
    }

    // Function to define plugin settings
    function settings()
    {
        add_settings_section('wcp_first_section', null, null, 'word-count-settings-page');

        // Add a setting for display location
        add_settings_field(
            'wcp_location',
            'Display Location',
            array($this, 'locationHTML'),
            'word-count-settings-page',
            'wcp_first_section'
        );
        register_setting(
            'wordcountplugin',
            'wcp_location',
            array(
                'sanitize_callback' => array($this, 'sanitizeLocation'),
                'default' => '0'
            )
        );

        // Add a setting for headline text
        add_settings_field(
            'wcp_headline',
            'Headline Text',
            array($this, 'headlineHTML'),
            'word-count-settings-page',
            'wcp_first_section'
        );
        register_setting(
            'wordcountplugin',
            'wcp_headline',
            array('sanitize_callback' => 'sanitize_text_field', 'default' => 'Post statistics')
        );

        // Add a setting for word count
        add_settings_field(
            'wcp_wordcount',
            'Word Count',
            array($this, 'checkboxHTML'),
            'word-count-settings-page',
            'wcp_first_section',
            array('theName' => 'wcp_wordcount')
        );
        register_setting(
            'wordcountplugin',
            'wcp_wordcount',
            array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')
        );

        // Add a setting for character count
        add_settings_field(
            'wcp_charactercount',
            'Character count',
            array($this, 'checkboxHTML'),
            'word-count-settings-page',
            'wcp_first_section',
            array('theName' => 'wcp_charactercount')
        );
        register_setting(
            'wordcountplugin',
            'wcp_charactercount',
            array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')
        );

        // Add a setting for read time
        add_settings_field(
            'wcp_readtime',
            'Read time',
            array($this, 'checkboxHTML'),
            'word-count-settings-page',
            'wcp_first_section',
            array('theName' => 'wcp_readtime')
        );
        register_setting(
            'wordcountplugin',
            'wcp_readtime',
            array('sanitize_callback' => 'sanitize_text_field', 'default' => '1')
        );
    }

    // Function to render the checkbox input for settings
    function checkboxHTML($args)
    { ?>
        <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?>>
    <?php }

    // Function to render the headline text input for settings
    function headlineHTML()
    { ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')) ?>">
    <?php }

    // Function to render the select input for display location setting
    function locationHTML()
    { ?>
        <select name="wcp_location">
            <option value="0" <?php selected(get_option('wcp_location'), '0') ?>>Beginning of post</option>
            <option value="1" <?php selected(get_option('wcp_location'), '1') ?>>End of post</option>
        </select>
    <?php }

    // Function to create the admin page
    function adminPage()
    {
        add_options_page('Word Count Settings', __('Word Count', 'wcpdomain'), 'manage_options', 'word-count-settings-page', array($this, 'ourHTML'));
    }

    // Function to render the HTML for the admin page
    function ourHTML()
    { ?>
        <div class="wrap">
            <h1>Word Count Settings</h1>
            <form action="options.php" method="POST">
                <?php
                settings_fields('wordcountplugin');
                do_settings_sections('word-count-settings-page');
                submit_button();
                ?>
            </form>
        </div>
<?php }
}

$wordCountAndTimePlugin = new WordCountAndTimePlugin();
