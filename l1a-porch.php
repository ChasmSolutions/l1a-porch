<?php
/**
 * Plugin Name: LoveOneAnother - Porch
 * Plugin URI: https://github.com/ChrisChasm/l1a-porch
 * Description: This plugin adds a front porch to a Disciple Tools system.
 * Text Domain: l1a-porch
 * Domain Path: /languages
 * Version:  0.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/ChrisChasm/l1a-porch
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `L1a_Porch` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function l1a_porch() {
    $l1a_porch_required_dt_theme_version = '1.19';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $l1a_porch_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'l1a_porch_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return L1a_Porch::instance();
}
add_action( 'after_setup_theme', 'l1a_porch', 20 );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class L1a_Porch {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {

        require_once( 'home-2/loader.php' ); /* Simple, Big images, White and Image */

        if ( is_admin() ) {
            require_once( 'admin/admin-menu-and-tabs.php' );
        }

        if ( is_admin() ){
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 ); // admin plugin page description
        }
        $this->i18n();
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {

            // You can still use `array_unshift()` to add links at the beginning.
            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
        // add elements here that need to fire on activation
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        // add functions here that need to happen on deactivation
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        $domain = 'l1a-porch';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'support/languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'l1a-porch';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "l1a_porch::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


// Register activation hook.
register_activation_hook( __FILE__, [ 'L1a_Porch', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'L1a_Porch', 'deactivation' ] );


if ( ! function_exists( 'l1a_porch_hook_admin_notice' ) ) {
    function l1a_porch_hook_admin_notice() {
        global $l1a_porch_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple Tools -CityChurch Tools (English)' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $l1a_porch_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-l1a-porch', false ) ) { ?>
            <div class="notice notice-error notice-l1a-porch is-dismissible" data-notice="l1a-porch">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-l1a-porch .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'l1a-porch',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

if ( ! function_exists( 'l1a_porch_fields' ) ) {
    function l1a_porch_fields() {
        /**
        'key' => [
            'label' => 'Label',
            'value' => '', // empty. This is where current value us stored.
            'type' => 'text', // text, textarea, url, select
        ],
        'select' => [
            'label' => 'Select',
            'value' => '',
            'default' => [
                'key1' => 'Label1',
                'key2' => 'Label2',
             ],
            'type' => 'select',
        ]
         */
        $defaults = [
            [
                'label' => 'General',
                'type' => 'break',
            ],
            'city' => [
                'label' => 'City',
                'value' => '',
                'type' => 'text',
            ],
            'site_title' => [
                'label' => 'Site Title',
                'value' => '',
                'type' => 'text',
            ],
            'description' => [
                'label' => 'Description',
                'value' => '',
                'type' => 'textarea',
            ],
            [
                'label' => 'Nav Section',
                'type' => 'break',
            ],
            'hero_background' => [
                'label' => 'Hero Background',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/hero.jpg',
                'type' => 'url',
            ],
            [
                'label' => 'Why Section',
                'type' => 'break',
            ],
            [
                'label' => 'Testimonies Section',
                'type' => 'break',
            ],
            'testimony_1_title' => [
                'label' => 'Testimony #1 - Title',
                'value' => '',
                'type' => 'text',
            ],
            'testimony_1_story' => [
                'label' => 'Testimony #1 - Story',
                'value' => '',
                'type' => 'textarea',
            ],
            'testimony_1_url' => [
                'label' => 'Testimony #1 - Image URL',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/map-socal.png',
                'type' => 'url',
            ],
            [
                'label' => 'Get Started',
                'type' => 'break',
            ],
            'slider_1' => [
                'label' => 'Slider 1',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/1a.jpg',
                'type' => 'url',
            ],
            'slider_2' => [
                'label' => 'Slider 2',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/2a.jpg',
                'type' => 'url',
            ],
            'slider_3' => [
                'label' => 'Slider 3',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/3a.jpg',
                'type' => 'url',
            ],
            'slider_4' => [
                'label' => 'Slider 4',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/4a.jpg',
                'type' => 'url',
            ],
            'slider_5' => [
                'label' => 'Slider 5',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/3a.jpg',
                'type' => 'url',
            ],
            'slider_6' => [
                'label' => 'Slider 6',
                'value' => trailingslashit( plugin_dir_url(__FILE__) ) . 'home-2/assets/temp_images/2a.jpg',
                'type' => 'url',
            ],
            [
                'label' => 'Footer Section',
                'type' => 'break',
            ],
            'facebook_url' => [
                'label' => 'Facebook URL',
                'value' => '',
                'type' => 'text',
            ],
            'twitter_url' => [
                'label' => 'Twitter URL',
                'value' => '',
                'type' => 'text',
            ],
            'instagram_url' => [
                'label' => 'Instagram URL',
                'value' => '',
                'type' => 'text',
            ],

        ];

        $defaults = apply_filters( 'l1a_porch_fields', $defaults );

        $saved_fields = get_option( 'l1a_porch_fields', [] );

        return l1a_recursive_parse_args( $saved_fields, $defaults );
    }
}
if ( ! function_exists( 'l1a_recursive_parse_args' ) ) {
    function l1a_recursive_parse_args( $args, $defaults ) {
        $new_args = (array) $defaults;

        foreach ( $args ?: [] as $key => $value ) {
            if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
                $new_args[ $key ] = l1a_recursive_parse_args( $value, $new_args[ $key ] );
            }
            elseif ( $key !== "default" ){
                $new_args[ $key ] = $value;
            }
        }

        return $new_args;
    }
}

add_action( 'plugins_loaded', function (){
    if ( is_admin() ){
        // Check for plugin updates
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' )){
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ){
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/ChrisChasm/l1a-porch/master/version-control.json',
                __FILE__,
                'l1a-porch'
            );

        }
    }
} );
