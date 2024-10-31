<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * @package SeamlessChex Payment Gateway
 * @version 1.3.4
 */
/**
 * Plugin Name: SeamlessChex eCheck Payments
 * Description: SeamlessChex is the easiest way to Accept & Verify eCheck Payments on your website with fast next day deposits directly to your bank account.
 * Author: SeamlessChex
 * Contributors: seamlesschex
 * Version: 1.3.4
 * Author URI: https://www.seamlesschex.com/
 * Copyright: © 2024 SeamlessChex eCheck Payments
 *
 * Tested up to: 6.4
 * Requires at least: 4.0.3
 * Copyright © 2024 SeamlessChex eCheck Payments
 */

define( 'SCX_VERSION', '1.3.4' );
define( 'SCX_ENDPOINT_LINK_LIVE', 'https://api.seamlesschex.com/v1/' );
define( 'SCX_ENDPOINT_LINK_TEST', 'https://sandbox.seamlesschex.com/v1/' );

if (!class_exists('Woocommerce_Gateway_SeamlessChex')) {
    include_once('includes/seamlesschex_extra_functions.php');
    include_once('includes/seamlesschex_settings.php');

    /**
     * class:   Woocommerce_Gateway_SeamlessChex
     * desc:    plugin class to Woocommerce Gateway SeamlessChex
     */
    class Woocommerce_Gateway_SeamlessChex {

        private static $instance;

        public static function instance() {            
            if (!self::$instance) {
                self::$instance = new Woocommerce_Gateway_SeamlessChex();
                $networkFlag = False;
                if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
                    $networkFlag = True;
                } else {
                    $active_plugins_var = (array) get_network_option(null, 'active_sitewide_plugins');
                    if (is_array($active_plugins_var) && count($active_plugins_var) != 0 && in_array('woocommerce/woocommerce.php', $active_plugins_var)) {
                        $networkFlag = True;
                    }
                }
                if ($networkFlag) {
                    self::$instance->setup_constants();
                    self::$instance->hooks();
                    self::$instance->includes();
                    self::$instance->load_textdomain();

                    add_filter('woocommerce_payment_gateways', array(self::$instance, 'add_wc_gateway'));
                }
            }
            return self::$instance;
        }

        private function setup_constants() {
            // Plugin path
            define('WOO_GSP_DIR', plugin_dir_path(__FILE__));
        }

        private function hooks() {
            register_activation_hook(__FILE__, array('Woocommerce_Gateway_SeamlessChex', 'activate'));
            register_deactivation_hook(__FILE__, array('Woocommerce_Gateway_SeamlessChex', 'deactivate'));
        }

        private function includes() {
            require_once WOO_GSP_DIR . 'includes/gateway.php';
        }

        /**
         * Add gateway to WooCommerce.
         *
         * @access public
         * @param  array  $methods
         * @return array
         */
        function add_wc_gateway($methods) {

            $methods[] = 'WC_Gateway_SeamlessChex';
            return $methods;
        }

        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = WOO_GSP_DIR . '/languages/';
            $lang_dir = apply_filters('woo_gateway_seamlesschex_lang_dir', $lang_dir);

            // Traditional WordPress plugin locale filter
            $locale = apply_filters('plugin_locale', get_locale(), '');
            $mofile = sprintf('%1$s-%2$s.mo', 'woocommerce-gateway-seamlesschex', $locale);

            // Setup paths to current locale file
            $mofile_local = $lang_dir . $mofile;
            $mofile_global = WP_LANG_DIR . '/woocommerce-gateway-seamlesschex/' . $mofile;

            if (file_exists($mofile_global)) {
                // Look in global /wp-content/languages/woocommerce-gateway-seamlesschex/ folder
                load_textdomain('woocommerce-gateway-seamlesschex', $mofile_global);
            } elseif (file_exists($mofile_local)) {
                // Look in local /wp-content/plugins/woocommerce-gateway-seamlesschex/languages/ folder
                load_textdomain('woocommerce-gateway-seamlesschex', $mofile_local);
            } else {
                // Load the default language files
                load_plugin_textdomain('woocommerce-gateway-seamlesschex', false, $lang_dir);
            }
        }

// END public function __construct()

        public static function activate() {
            flush_rewrite_rules();
        }

        public static function deactivate() {
            flush_rewrite_rules();
        }

    }

    // END class Woocommerce_Gateway_SeamlessChex
}// END if(!class_exists("Woocommerce_Gateway_SeamlessChex"))

function woocommerce_gateway_seamlesschex_load() {
    if (!class_exists('WooCommerce')) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $is_woo = false;         
        foreach (get_plugins() as $plugin_path => $plugin) {
            if ('WooCommerce' === $plugin['Name']) {
                $is_woo = true;
                break;
            }
        }
        define('HAS_WOO', $is_woo);
        add_action('admin_notices', 'woocommerce_gateway_seamlesschex_notice');
    } else { //else WooCommerce class exists
        return Woocommerce_Gateway_SeamlessChex::instance();
    }    
}

add_action('plugins_loaded', 'woocommerce_gateway_seamlesschex_load');

function woocommerce_gateway_seamlesschex_notice() {
    if (HAS_WOO) {
        echo '<div class="error"><p>' . wp_kses(__('SeamlessChex by SeamlessChex Payment Processing add-on requires WooCommerce! Please activate it to continue!', 'woocommerce-gateway-seamlesschex'), $allowed_html_array) . '</p></div>';
    } else {
        echo '<div class="error"><p>' . wp_kses(__('SeamlessChex by SeamlessChex Payment Processing add-on requires WooCommerce! Please install it to continue!', 'woocommerce-gateway-seamlesschex'), $allowed_html_array) . '</p></div>';
    }
}

add_action('woocommerce_order_status_cancelled', 'order_cancelled_so_cancelcheck', 10, 1);


function order_cancelled_so_cancelcheck($order_id) {
    $gateway = new WC_Gateway_SeamlessChex();
    $order = wc_get_order($order_id);
    if (!$gateway->via_seamless($order)) {
        return false;
    }
    $check = $gateway->check_by_order($order_id);
    if ($check) {
        $response = $gateway->callAPI('check/' . $check->check_id, [], 'DELETE');
        if ($response->success) {
            $gateway->log(__('Check canceled. ', 'woocommerce-gateway-seamlesschex'));
            $order->add_order_note(__('Order Cancelled so Check was also Canceled', 'woocommerce-gateway-seamlesschex'));
            if ('cancelled' != $order->get_status()) {
                $order->update_status('cancelled');
            }
        } else {
            $check = $gateway->check_by_order($order_id);
            $deleted = in_array(strtolower($check->status), ['deleted', 'failed', 'void']);
            $processed = in_array(strtolower($check->status), ["deposited", "printed"]);
            $in_processed = in_array(strtolower($check->status), ["in_process"]);

            if ($deleted) { 
                $order->add_order_note(__('Check has been cenceled by SeamlessChex Processing.', 'woocommerce-gateway-seamlesschex'));
                $order->update_status('cancelled');
            }
            if ($processed) {
                $order->add_order_note(__('Check has been processed by SeamlessChex Processing', 'woocommerce-gateway-seamlesschex'));
                $order->update_status('processing');                
            }
            if ($in_processed) {                
                $order->add_order_note(__('Verification process completed by SeamlessChex and check is in queue to be processed. Once the check has processed at SeamlessChex, we will update your order status from On-Hold to Processing', 'woocommerce-gateway-seamlesschex'));
                $order->update_status('on-hold');                
            }
        }
    }
}

add_action('wp_enqueue_scripts', 'seamless_check_custom_js');
function seamless_check_custom_js() {
    wp_enqueue_script('custom', plugin_dir_url(__FILE__) .'js/seamlesschex_scripts.js', array('jquery'), false, true);
    wp_enqueue_script('datepicker', plugin_dir_url(__FILE__) .'js/datepicker.min.js', array('jquery'), false, true);
    wp_enqueue_script('seamlesschex-checkout-sdk', "https://developers.seamlesschex.com/seamlesschex/docs/checkoutjs/sdk-min.js", array('jquery'), false, false);
}

add_action( 'wp_enqueue_scripts', 'seamless_check_custom_css' );
function seamless_check_custom_css() {
    wp_enqueue_style('tooltip', plugin_dir_url(__FILE__) .'css/tooltip.css', false);
    wp_enqueue_style('datepicker', plugin_dir_url(__FILE__) .'css/datepicker.min.css', false);
    wp_enqueue_style('font-awesome-5', 'https://use.fontawesome.com/releases/v5.3.0/css/all.css', array(), '5.3.0');
}
