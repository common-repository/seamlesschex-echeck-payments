<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Gateway_SeamlessChex')) {

    class WC_Gateway_SeamlessChex extends WC_Payment_Gateway {

        /** @var bool Whether or not logging is enabled */
        public static $log_enabled = false;

        /** @var WC_Logger Logger instance */
        public static $log = false;
        public static $deleted;
        public static $processed;
        public static $rejected;

        /**
         * @var string The last error to have occurred on the gateway
         */
        public $error = "";

        /**
         * @var array An array of options set by the gateway settings page recalled by get_option()
         */
        public $options = array();

        /**
         * @var string The current API endpoint URL to be called which will be appended by the method.
         */
        public $endpoint = SCX_ENDPOINT_LINK_LIVE;

        /**
         * @var string The current API endpoint to be called as far as eCheck, eCart, etc.
         */
        public $method = "WooCommerce.asmx";

        /**
         * @var string The merchant's API Client ID
         */
        public $client_id = false;

        /**
         * @var string The merchant's API Password
         */
        public $live_api = false;

        /**
         * @var string The woocommerce store REST API client ID
         */
        public $rest_client_id = false;

        /**
         * @var string The woocommerce store REST API client secret
         */
        public $rest_client_secret = false;

        /**
         * @var bool Whether or not the store allows the widget to be displayed on the front end
         */
        public $allow_widget = false;

        /**
         * @var string A human readable description of the payment method displayed on the checkout page
         */
        public $description = "";

        /**
         * @var string any extra text to add to the description of the payment method on the checkout page
         */
        public $extra = "";

        /**
         * @var string
         */
        public $cronPeriod = 0;

        /**
         * @var bool True if the plugin should be logging requests more verbosely to a log file found in wp-content/uploads/wc-logs/ by default
         */
        public $debug = false;

        /**
         * @var string Will either be set to the user input setting API URL or will be get_site_url()
         */
        public $useStoreURL = "";

        /**
         * Cloning is forbidden
         *
         * @since 1.1.0
         */
        public function __clone() {
            //do nothing
        }

        /**
         * Unserializing instances of this class is forbidden
         *
         * @since 1.1.0
         */
        public function __wakeup() {
            //do nothing
        }

        public $if_recurring;
        public $recurring_installments;
        public $recurring_cycle;
        public $is_subscription;
        public $recurring;
        public $recurring_start_date;
        public $verification_mode;

        //See parent class WC_Payment_Gateway for (id, has_fields, method_title, method_description, title, supports)
        public function __construct() {
            $this->error = '';
            $this->id = 'seamless';
            $this->has_fields = true;
            $this->method_title = __(esc_html('SeamlessChex'), 'woocommerce-gateway-seamlesschex');
            $new_settings_page = get_admin_url(null, 'admin.php?page=seamlesschex_gateway');
            $this->method_description = __("<a href=" . esc_attr($new_settings_page) . " target='_blank'>Take payments in eChecks with fast next day deposits directly to your bank account.</a>", 'woocommerce-gateway-seamlesschex');
            $this->supports = array(
                'products',
                'refunds',
                'tokenization'
            );

            //Get options we need and make them usable
            $this->options = get_option('seamlesschex_settings');
            //$this->endpoint = $this->options['seamlesschex_api_endpoint'];
            $this->endpoint = (isset($this->options['seamlesschex_api_endpoint'])) ? trailingslashit($this->options['seamlesschex_api_endpoint']) : "https://www.seamlesschex.com/";
            $this->method = "eCheck.asmx";
            $this->client_id = (isset($this->options['seamlesschex_test_api_key'])) ? $this->options['seamlesschex_test_api_key'] : false;
            $this->live_api = (isset($this->options['seamlesschex_live_api_key'])) ? $this->options['seamlesschex_live_api_key'] : false;

            $this->rest_client_id = (isset($this->options['seamlesschex_woo_rest_client_id'])) ? $this->options['seamlesschex_woo_rest_client_id'] : false;
            $this->rest_client_secret = (isset($this->options['seamlesschex_woo_rest_client_secret'])) ? $this->options['seamlesschex_woo_rest_client_secret'] : false;
            $this->allow_widget = (isset($this->options['seamlesschex_tokenization_use'])) ? ($this->options["seamlesschex_tokenization_use"] == 1) : false;

            $this->extra = (isset($this->options['seamlesschex_extra_message'])) ? $this->options['seamlesschex_extra_message'] : "";
            $this->if_recurring = (isset($this->options['seamlesschex_gateway_recurring'])) ? $this->options['seamlesschex_gateway_recurring'] : "month";
            $this->recurring_installments = (isset($this->options['schex_recurring_installments'])) ? $this->options['schex_recurring_installments'] : "0";
            $this->recurring_cycle = (isset($this->options['schex_recurring_cycle'])) ? $this->options['schex_recurring_cycle'] : "";
            $this->is_subscription = $this->if_recurring == 1 ? 1 : 0;
            $this->recurring = $this->if_recurring != 0 ? 1 : 0;
            $this->recurring_start_date = null;

            if ($this->is_subscription) {
                $this->recurring_installments = 0;
                $this->recurring_cycle = 'month';
            }

            $this->description = (isset($this->options['seamlesschex_gateway_description'])) ? $this->options['seamlesschex_gateway_description'] : "";
            $this->verification_mode = (isset($this->options['seamlesschex_override_risky_option'])) ? $this->options['seamlesschex_override_risky_option'] : "legacy";
            $this->cronPeriod = (isset($this->options['seamlesschex_settings_order_cron'])) ? $this->options['seamlesschex_settings_order_cron'] : 0;
            $this->title = (isset($this->options['seamlesschex_title'])) ? $this->options['seamlesschex_title'] : "";
            $this->debug = (isset($this->options['seamlesschex_debug_log'])) ? $this->options['seamlesschex_debug_log'] : 0;
            $this->useStoreURL = (isset($this->options['seamlesschex_site_url'])) ? $this->options['seamlesschex_site_url'] : get_site_url();

            //Set debug
            if ($this->debug === '1' || $this->debug === 1 || $this->debug === true) {
                $this->debug = true;
            } else {
                $this->debug = false;
            }

            self::$log_enabled = $this->debug;

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('admin_notices', array($this, 'do_ssl_check'), 999);

            // We may need to add in the custom JS for the widget. $this->payment_scripts will run on wp_enqueue_scripts to determine if that's the case and potentially inject the code
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_cancelled_order', 'order_cancelled_so_cancelcheck', 10, 1);

            // add_action( 'woocommerce_api_seamlesschex-success', array( $this, 'seamlesschex_success_webhook' ) );
            // add_action( 'woocommerce_api_seamlesschex-cancel', array( $this, 'seamlesschex_cancel_webhook' ) );
            
        }

        function __toString() {
            $str = "Gateway Type: POST\n";
            $str .= "Endpoint: " . $this->endpoint . "\n";
            $str .= "Client ID: " . $this->client_id . "\n";
            $str .= "ApiPassword: " . $this->live_api . "\n";

            return $str;
        }

        /**
         * Internal helper function returns the entire endpoint URL
         *
         * @return string The full unqualified URL an API call is targeted to for this Gateway
         */
        public function full_endpoint() {
            return trailingslashit(trailingslashit($this->endpoint) . $this->method);
        }

        function seamlesschex_toString($html = TRUE) {
            if ($html) {
                return nl2br($this->__toString());
            }

            return $this->__toString();
        }

        private function seamlesschex_setLastError($error) {
            $this->error = $error;
        }

        public function seamlesschex_getLastError() {
            return $this->error;
        }

        /**
         * Logging method.
         *
         * @param string $message
         */
        public static function log($message) {
            if (TRUE || self::$log_enabled) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('seamless', $message);
            }
        }

        // Check if we are forcing SSL on checkout pages
        public function do_ssl_check() {
            if (( function_exists('wc_site_is_https') && !wc_site_is_https() ) && ( 'no' === get_option('woocommerce_force_ssl_checkout') && !class_exists('WordPressHTTPS') )) {
                echo '<div class="error"><p>' . sprintf(__('<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', 'woocommerce-gateway-seamlesschex'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
            }
        }

        /**
         * The order was cancelled so we want to try and cancel the Check in SeamlessChex
         *
         * @param string $order_id
         */
        public function order_cancelled_so_cancelcheck($order_id) {
            $order = wc_get_order($order_id);
            if (!$this->via_seamless($order)) {
                return false;
            }

            $check = $this->check_by_order($order_id);

            if ($check) {                
                $response = $this->callAPI('check/' . $check->check_id, [], 'DELETE');
                if ($response->success) {
                    $this->log(__('Check canceled. ', 'woocommerce-gateway-seamlesschex'));
                    $order->add_order_note(__('Order Cancelled so Check was also Canceled', 'woocommerce-gateway-seamlesschex'));
                    if ('cancelled' != $order->get_status()) {
                        $order->update_status('cancelled');
                    }
                }else{
                    $check = $this->check_by_order($order_id);     
                    
                    $deleted   = in_array(strtolower($check->status), ['deleted', 'failed', 'void']);
                    $processed = in_array(strtolower($check->status), ["deposited", "printed"]);
                    $in_processed = in_array(strtolower($check->status), ["in_process"]);
                    
                    if($deleted){
                        $order->update_status('cancelled');
                        $order->add_order_note('Check has been cenceled by SeamlessChex Processing.');
                    }
                    if($processed){
                        $order->update_status('processing');
                          $order->add_order_note('Check has been processed by SeamlessChex Processing.');
                    }
                    if($in_processed){
                        $order->update_status('on-hold');
                        $order->add_order_note('Verification process completed by SeamlessChex and check is in queue to be processed. Once the check has processed at SeamlessChex, we will update your order status from On-Hold to Processing.');
                          
                    }

                    
                }
            }
        }

        /**
         * Check if this gateway is enabled
         */
        public function is_available() {
            if (!$this->client_id || !$this->live_api || !$this->endpoint || !$this->rest_client_id || !$this->rest_client_secret) {
                return false;
            }
            return true;
        }

        /**
         * Payment form on checkout page.
         *
         * See WC_Payment_Gateway::payment_fields()
         */
        public function payment_fields() {
            include('manual-enrollment.php');
        }

        /**
         * Safely get and trim data from $_POST
         *
         * @since 1.1.0
         * @param string $key array key to get from $_POST array
         * @return string value from $_POST or blank string if $_POST[ $key ] is not set
         */
        public static function get_post($key) {
            if (isset($_POST[$key])) {
                return sanitize_text_field(trim($_POST[$key]));
            }
            return '';
        }

        /**
         * Validate the payment fields when processing the checkout
         *
         * @since 1.1.0
         * @see WC_Payment_Gateway::validate_fields()
         * @return bool true if fields are valid, false otherwise
         */
        public function validate_fields() {
            $is_valid = parent::validate_fields(); //$is_valid = true in MOST cases
            return $this->validate_check_fields($is_valid);
        }

        /**
         * Returns true if the posted echeck fields are valid, false otherwise
         *
         * @since 1.1.0
         * @param bool $is_valid true if the fields are valid, false otherwise
         * @return bool
         */
        protected function validate_check_fields($is_valid) {
            if (!$is_valid) {
                //form was invalid before it got to us. Immediately error out.
                return false;
            }

            $this->account_number = $this->get_post($this->id . '_account_number');
            $this->routing_number = $this->get_post($this->id . '_routing_number');
            $this->pid = $this->get_post($this->id . '_pid');
            
        //     $this->recurring_installments = $this->get_post($this->id . '_recurring_installments');
        //     $getRecurringDate = $this->get_post($this->id . '_recurring_start_date');
        //     $this->recurring_start_date = $getRecurringDate ? date('Y-m-d', strtotime($getRecurringDate)) : null;
        //     $this->recurring_cycle = $this->get_post($this->id . '_recurring_cycle');
	    // $this->recurring = $this->get_post($this->id . '_recurring') || $this->get_post($this->id . '_subscription');
	    // $this->is_subscription = $this->get_post($this->id . '_subscription');
            //print_r($this->recurring);
            
            
             
            if($getRecurringDate != null && strtotime(date('Y-m-d')) > strtotime(date('Y-m-d', strtotime($getRecurringDate)))){
                 wc_add_notice(esc_html__('Payment error: The recurring start date cannot be in the past.', 'woocommerce-gateway-seamlesschex'), 'error');
                //$is_valid = false;
             }
            
            // recurring cycle exists?
            if(!empty($this->recurring) && empty($this->recurring_cycle)){
                wc_add_notice(esc_html__('Billing Period is missing, please select an option', 'woocommerce-gateway-seamlesschex'), 'error');
                $is_valid = false;
            }

            if (!empty($this->pid)) {
                return apply_filters('wc_payment_gateway_' . $this->id . '_validate_check_fields', $is_valid, $this);
            }

            // routing number exists?
            if (empty($this->routing_number)) {
                wc_add_notice(esc_html__('Routing Number is missing', 'woocommerce-gateway-seamlesschex'), 'error');
                $is_valid = false;
            } else {
                // routing number digit validation
                $message = "";
                if (!$this->routing_number_validate($this->routing_number, $message)) {
                    wc_add_notice(esc_html__('Routing Number is invalid: ' . $message, 'woocommerce-gateway-seamlesschex'), 'error');
                    $is_valid = false;
                }
            }

            // account number exists?
            if (empty($this->account_number)) {
                wc_add_notice(esc_html__('Account Number is missing', 'woocommerce-gateway-seamlesschex'), 'error');
                $is_valid = false;
            } else {
                // account number length validation
                if (strlen($this->account_number) < 5 || strlen($this->account_number) > 17) {
                    wc_add_notice(esc_html__('Account number is invalid (must be between 5 and 17 digits)', 'woocommerce-gateway-seamlesschex'), 'error');
                    $is_valid = false;
                }
            }

            return apply_filters('wc_payment_gateway_' . $this->id . '_validate_check_fields', $is_valid, $this);
        }

        function callAPI($messageName, $data, $method = null) {            
            
            $prev_messageName = $this->method;
            if ($messageName != null) {
                $this->method = $messageName;
            }
            
            $endpointId = $this->live_api;            
            if($this->endpoint == SCX_ENDPOINT_LINK_LIVE){
                $endpointId = $this->live_api;
            }
            if($this->endpoint == SCX_ENDPOINT_LINK_TEST){
                $endpointId = $this->client_id;
            }
            
            $endpoint = $this->endpoint . $messageName;
            $args = [
                'headers' => [
                    'Authorization' => $endpointId,
                    'blocking' => true
                ]
            ];

            $params = http_build_query($data);
            $this->log(__('Sending POST to SeamlessChex, this is what we are sending: ', 'woocommerce-gateway-seamlesschex') . $params);

            $response = NULL;

            if (strcasecmp(strtolower($method), "get") === 0) {
                $response = wp_remote_get( $endpoint . ($params ? '?' . $params : ''), $args );                
            } else if (strcasecmp(strtolower($method), "delete") === 0) {
                $args['method'] = 'DELETE';
                $response = wp_remote_request( $endpoint . ($params ? '?' . $params : ''), $args );
            } else {
                $args['body'] = $data;                
                $response = wp_remote_post( $endpoint, $args );
            }
                   
            if ($response === FALSE) {
                throw new \Exception('Empty response', 0);
            }

            $this->method = $prev_messageName;

            $body     = wp_remote_retrieve_body( $response );          
            

            $this->log(__('Preparing data, endpoint is ', 'woocommerce-gateway-seamlesschex') . $endpoint);
            $this->log(__('Raw Response: ', 'woocommerce-gateway-seamlesschex') . print_r($body, true));

            try {
                $loadedResponse = json_decode($body);
                $this->log(__('Loaded Response: ', 'woocommerce-gateway-seamlesschex') . print_r($loadedResponse, true));
                return $loadedResponse;
            } catch (\Exception $e) {
                $this->log("Unable to parse API results: " . $e->getMessage());
                $this->seamlesschex_setLastError("An error occurred while attempting to parse the API result: " . $e->getMessage());
                return false;
            }
        }

        /**
         *
         * @param int $order_id
         * @return array
         *
         */
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $orderdata = $this->get_order_info($order);
            if ('seamless' === $orderdata["payment_method"]) {
                $this->log(__('Started to process order:', 'woocommerce-gateway-seamlesschex') . $orderdata["id"]);
                $this->log(__('Setting order status to pending for order ', 'woocommerce-gateway-seamlesschex') . $orderdata["id"]);
                return $this->process_payment_echeck($order_id, $order, $orderdata);
            }
            return null;
        }

        /**
         * Call to the SeamlessChex API to generate the check and return the status of that to the front end
         *
         * @param int $order_id		The id of the WooCommerce Order instance we're checking out for.
         * @param mixed $order		The WooCommerce Order Instance we're checking out for.
         * @param array $orderdata	The result of self::get_order_info on the order
         *
         * @return array|void Returns an array with success info or null on error
         */
        private function process_payment_echeck($order_id, $order, $orderdata) {

                $data = [
                    'amount' => sanitize_text_field($order->get_total()),
                    'memo' => __('Order #', 'woocommerce-gateway-seamlesschex') . sanitize_text_field($order->get_order_number()),
                    'memo2' => __('Store :', 'woocommerce-gateway-seamlesschex') . sanitize_text_field($this->useStoreURL),
                    'name' => sanitize_text_field($orderdata["billing_company"]) ? sanitize_text_field($orderdata["billing_company"]) : sanitize_text_field($orderdata["billing_first_name"]) . " " . sanitize_text_field($orderdata["billing_last_name"]),
                    'email' => is_email($orderdata["billing_email"]),
                    'label' => sanitize_text_field($order->get_order_number()),
                    'phone' => sanitize_text_field($orderdata["billing_phone"]),
                    'address' => sanitize_text_field($orderdata["billing_address_1"]) . (isset($orderdata["billing_address_2"]) ? ', ' . sanitize_text_field($orderdata["billing_address_2"]) : ''),
                    'city' => sanitize_text_field($orderdata["billing_city"]),
                    'state' => sanitize_text_field($orderdata["billing_state"]),
                    'zip' => sanitize_text_field($orderdata["billing_postcode"]),
                    'country' => sanitize_text_field($orderdata["billing_country"]),
                    'bank_account' => sanitize_text_field($this->account_number),
                    'bank_routing' => sanitize_text_field($this->routing_number),
                    'recurring' => sanitize_text_field($this->recurring),
                    'recurring_cycle' => sanitize_text_field($this->recurring_cycle),
                    'recurring_start_date' => sanitize_text_field($this->recurring_start_date),
                    'recurring_installments' => sanitize_text_field($this->recurring_installments),
                    'verify_before_save' => false,
                    'fund_confirmation' => ($this->verification_mode === "permissive"),
                    'account_info_id' => $this->pid,
                    'type_info' => 'WooCommerce Pay v'.SCX_VERSION,
                    'is_subscription' => sanitize_text_field($this->is_subscription),
                ];    
                
                
                // Send this payload to SeamlessChex for processing
                $response = $this->callAPI('check/create', $data, 'POST');

                if ($response && $response->success) {
                    $check= $this->check_by_order($order_id);
                    
                    $resultCode              = $check->basic_verification->code_bv;
                    $resultDescription       = $check->basic_verification->description_bv;                
                    
                    if($resultCode === 'RT01' || $resultCode === 'RT03' || $resultCode === 'RT04'){
                        $order->add_order_note(sprintf(__('Check has been Pending payment (Check_ID: <b>%s</b>, CheckNumber: <b>%s</b>)', 'woocommerce-gateway-seamlesschex'), $response->check->check_id, $response->check->number));  
                        if ($check) {
                            $response = $this->callAPI('check/' . $check->check_id, [], 'DELETE');
                            if ($response->success) {
                                
                                $order->add_order_note(sprintf(__('<a href="https://developers.seamlesschex.com/seamlesschex/docs/#basicVerificatio" target="_blank">Basic Verification</a> Code: <b>%s</b>, Description: <b>%s</b>', 'woocommerce-gateway-seamlesschex'), $check->basic_verification->code_bv, $check->basic_verification->description_bv));  
                                $this->log(__('Check canceled. ', 'woocommerce-gateway-seamlesschex'));
                                $order->add_order_note(__('Order Cancelled so Check was also Canceled', 'woocommerce-gateway-seamlesschex'));
                                if ('cancelled' != $order->get_status()) {
                                    $order->update_status('cancelled');
                                }
                            }
                        }
                    } else {
                        $order->update_status('on-hold');
                        $order->add_order_note('Verification process completed by SeamlessChex and check is in queue to be processed. Once the check has processed at SeamlessChex, we will update your order status from On-Hold to Processing.');
                        $order->add_order_note(sprintf(__('<a href="https://developers.seamlesschex.com/seamlesschex/docs/#basicVerificatio" target="_blank">Basic Verification</a> Code: <b>%s</b>, Description: <b>%s</b>', 'woocommerce-gateway-seamlesschex'), $resultCode, $resultDescription));      
                    }
                    
                    //Check was accepted and either passed verification or was risky and store allowed it. We are all good to go here.
                    $this->log(sprintf(__('SeamlessChex check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-seamlesschex'), $response->check->check_id, $response->check->number));

                    if (!empty($response->basic_verification->response_code)) {
                        $order->add_order_note(sprintf(__('SeamlessChex check has a risky verification code (%s) that must be overridden manually.', 'woocommerce-gateway-seamlesschex'), $response->basic_verification->description_bv));
                    }
                    // Add post meta
                    add_post_meta($order_id, '_seamless_payment_check_id', (string) $response->check->check_id, true);
                    add_post_meta($order_id, '_seamless_payment_check_number', (string) $response->check->number, true);

                    // Empty cart
                    WC()->cart->empty_cart();
                    // Return thankyou redirect
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order),
                    );
                } else {
                    $this->log(__('Check is not accepted. Seamlesschex returned error description: ' . $response->message, 'woocommerce-gateway-seamlesschex'));
                    wc_add_notice("Payment error: " . $response->message, 'error');
                    return;
                }
            
        }

        /**
         * Return the order information in a version independent way
         *
         * @param WC_Order $order
         * @return array
         */
        public function get_order_info($order) {
            $data = array(
                "id" => '',
                "payment_method" => '',
                "billing_company" => '',
                "billing_first_name" => '',
                "billing_last_name" => '',
                "billing_email" => '',
                "billing_phone" => '',
                "billing_address_1" => '',
                "billing_address_2" => '',
                "billing_city" => '',
                "billing_state" => '',
                "billing_postcode" => '',
                "billing_country" => '',
                "order_total" => ''
            );
            if (version_compare(WC_VERSION, '3.0', '<')) {
                //Do it the old school way
                $data["id"] = sanitize_text_field($order->id);
                $data["payment_method"] = sanitize_text_field($order->payment_method);
                $data["billing_company"] = sanitize_text_field($order->billing_company);
                $data["billing_first_name"] = sanitize_text_field($order->billing_first_name);
                $data["billing_last_name"] = sanitize_text_field($order->billing_last_name);
                $data["billing_email"] = sanitize_text_field($order->billing_email);
                $data["billing_phone"] = sanitize_text_field($order->billing_phone);
                $data["billing_address_1"] = sanitize_text_field($order->billing_address_1);
                $data["billing_address_2"] = sanitize_text_field($order->billing_address_2);
                $data["billing_city"] = sanitize_text_field($order->billing_city);
                $data["billing_state"] = sanitize_text_field($order->billing_state);
                $data["billing_postcode"] = sanitize_text_field($order->billing_postcode);
                $data["billing_country"] = sanitize_text_field($order->billing_country);
                $data["order_total"] = sanitize_text_field($order->order_total);
            } else {
                //New school
                $data["id"] = sanitize_text_field($order->get_id());
                $data["payment_method"] = sanitize_text_field($order->get_payment_method());
                $data["billing_company"] = sanitize_text_field($order->get_billing_company());
                $data["billing_first_name"] = sanitize_text_field($order->get_billing_first_name());
                $data["billing_last_name"] = sanitize_text_field($order->get_billing_last_name());
                $data["billing_email"] = sanitize_text_field($order->get_billing_email());
                $data["billing_phone"] = sanitize_text_field($order->get_billing_phone());
                $data["billing_address_1"] = sanitize_text_field($order->get_billing_address_1());
                $data["billing_address_2"] = sanitize_text_field($order->get_billing_address_2());
                $data["billing_city"] = sanitize_text_field($order->get_billing_city());
                $data["billing_state"] = sanitize_text_field($order->get_billing_state());
                $data["billing_postcode"] = sanitize_text_field($order->get_billing_postcode());
                $data["billing_country"] = sanitize_text_field($order->get_billing_country());
                $data["order_total"] = sanitize_text_field($order->get_total());
            }
            return $data;
        }

        public function check_by_order($order_id) {
            $order = wc_get_order($order_id);
            $response = $this->callAPI("check/list/bylabel/" . $order_id, [], 'GET');
            if (!empty($response->list->data)) {
                if($response->list->total == 1){
                    return end($response->list->data);
                } else {
                    foreach($response->list->data as $item){
                        if($item->email == sanitize_text_field($order->get_billing_email())){
                            return $item;
                        }
                    }
                }                
                
            }       
            return false;           
        }

        /**         *
         * @param  WC_Order $order
         * @return bool
         */
        public function via_seamless($order) {
            return $order && get_post_meta($order->get_id(), '_seamless_payment_check_id', true);
        }

        /**
         * Process a refund if supported
         *
         * @param  int    $order_id
         * @param  float  $amount
         * @param  string $reason
         * @return  boolean True or false based on success, or a WP_Error object
         *
         * See WC_Payment_Gateway::process_refund()
         */
        public function process_refund($order_id, $amount = null, $reason = 'Refund') {
            $order = wc_get_order($order_id);

            if (!$this->via_seamless($order)) {
                $this->log(__('Cancel Failed: Missing SeamlessChex Payment Reference ', 'woocommerce-gateway-seamlesschex'));
                $order->add_order_note('Refund Failed: Missing SeamlessChex Payment Reference. You will have to use SeamlessChex portal to process this refund manually if there is a SeamlessChex Check ID associated with this order');
                return false;
            }

            if ($amount === null || $amount == 0) {
                $amount = number_format($order->get_total(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
            }

            $check = $this->check_by_order($order_id);

            if ($check) {

                $deleted = in_array(strtolower($check->status), ['deleted', 'failed', 'void']);
                $processed = in_array(strtolower($check->status), ["deposited"]);
                $useFullAmount = ($amount === number_format($order->get_total(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()));

                if ($deleted) {
                    $this->log(__('Failed: Check deleted from SeamlessChex system.', 'woocommerce-gateway-seamlesschex'));
                    return false;
                }

                if (!$processed && $useFullAmount) {
                    //Either it's not been processed OR we're trying to refund the full amount so just cancel the check
                    $response = $this->callAPI('check/' . $check->check_id, [], 'DELETE');

                    if ($response->success) {
                        $this->log(__('Cancel Accepted. SeamlessChex cancel Check_ID: ', 'woocommerce-gateway-seamlesschex') . $check->check_id);
                        $order->add_order_note(sprintf(__('Cancel Check ID: %s - Check Number: %s - Check amount %s', 'woocommerce-gateway-seamlesschex'), $check->check_id, $check->number, $amount));
                        return true;
                    } else {
                        $this->log(__('Cancel was declined', 'woocommerce-gateway-seamlesschex'));
                        $order->add_order_note(__('SeamlessChex cancel was declined', 'woocommerce-gateway-seamlesschex'));
                        wc_add_notice((string) $refundResponse->Result->ResultDescription, 'error');
                        return false;
                    }
                } else {
                    $this->log(__('Unable to process a cancel for this check.', 'woocommerce-gateway-seamlesschex'));
                    return false;
                }
            } else {
                $this->log(__('Unable to process a cancel for this check.', 'woocommerce-gateway-seamlesschex'));
                return false;
            }
        }

        /**
         * Call the Test API's TestAuthentication to validate the merchant's API credentials with their selected endpoint.
         *
         * @return boolean	True if the credentials validate, false otherwise.
         */
        public function test_authentication() {

            return true;
        }

        /**
         * A function to determine whether the current SeamlessChex client can use the tokenization widget
         *
         * @return boolean True if they can use the widget. False if not.
         */
        public function can_widget() {
            return FALSE;
        }

        /**
         * Will call to SeamlessChex to register the current store's WooCommerce REST API credentials
         *
         * @return boolean True if the store was/is saved, false if some other error occurred.
         */
        public function register_store($storeURL) {

            return true;
        }

        /**
         * Function will make an API call to our API that will register the session in our server
         *
         * @return boolean True if they can use the widget. False if not.
         */
        public function start_session($sessionId) {
            return true;
        }

        /**
         * Add payment JS script for tokenization if necessary
         */
        public function payment_scripts() {
            //only queue scripts on the checkout page
            if (!is_cart() && !is_checkout() && ! isset( $_GET['pay_for_order'])) {
                return;
            }
            //We need to make sure we have a valid configuration
            // if (empty($this->client_id) || empty($this->live_api)) {
            //     return;
            // }


            // wp_enqueue_script('custom', plugin_dir_url(__FILE__) .'js/seamlesschex_scripts.js', array('jquery'), false, true);
            // wp_enqueue_script('datepicker', plugin_dir_url(__FILE__) .'js/datepicker.min.js', array('jquery'), false, true);
            // wp_enqueue_script('seamlesschex-checkout-sdk', "https://developers.seamlesschex.com/seamlesschex/docs/checkoutjs/sdk-min.js", array('jquery'), false, false);

            // wp_enqueue_style('tooltip', plugin_dir_url(__FILE__) .'css/tooltip.css', false);
            // wp_enqueue_style('datepicker', plugin_dir_url(__FILE__) .'css/datepicker.min.css', false);
            // wp_enqueue_style('font-awesome-5', 'https://use.fontawesome.com/releases/v5.3.0/css/all.css', array(), '5.3.0');
            
        }

        /**
         * Internal helper function to determine whether or not a routing number appears to be validate
         *
         * @param string $routing_number	The string version of the routing number to validate
         * @param string $error 			A reference to a string which will contain the error if it returns false
         *
         * @return bool Whether the routing number validates as either a US or a CA routing number
         */
        private function routing_number_validate($routing_number, &$error) {
            if (strlen($routing_number) !== 9) {
                $error = "Must be 9 digits.";
                return false;
            }
            return true;
        }

    }

} 
