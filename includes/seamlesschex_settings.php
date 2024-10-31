<?php
/**
* Settings:
*
* Add main menu settings page for SeamlessChex
*/
function seamlesschex_add_admin_menu(  ) {
	add_menu_page( 'SeamlessChex Settings', 'SeamlessChex', 'manage_options', 'seamlesschex_gateway', 'seamlesschex_options_page', plugins_url('seamlesschex-echeck-payments/img/logo.png' ), 90);
}

/**
* Settings:
*
* Initialize SeamlessChex settings and values
*/
function seamlesschex_settings_init(  ) {
	register_setting( 'seamlesschex_register_setting', 'seamlesschex_settings', array(
		"sanitize_callback" => "seamlesschex_settings_validate"
	));
        
//       ********************************************************************************************** 

	add_settings_section(
		'seamlesschex_settings_main_page',
		__( "General Settings:", 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_settings_section_callback',
		'seamlesschex_register_setting'
	);

	add_settings_field(
		'seamlesschex_api_endpoint',
		__( 'Payment Mode', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_api_endpoint_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page'
	);

	add_settings_field(
		'seamlesschex_test_api_key',
		__( 'Test Secret Key*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_test_api_key_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);

	add_settings_field(
		'seamlesschex_test_api_public_key',
		__( 'Test Public Key*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_test_api_public_key_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);

	add_settings_field(
		'seamlesschex_live_api_key',
		__( 'Live Secret Key*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_live_api_key_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);

	add_settings_field(
		'seamlesschex_live_api_public_key',
		__( 'Live Public Key*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_live_api_public_key_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);

	add_settings_field(
		'seamlesschex_woo_rest_client_id',
		__( 'WooCommerce Consumer key*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_woo_rest_test_api_key_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);

	add_settings_field(
		'seamlesschex_woo_rest_client_secret',
		__( 'WooCommerce Consumer secret*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_woo_rest_client_secret_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_main_page',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);
        
//       ********************************************************************************************** 
        
        add_settings_section(
		'seamlesschex_settings_order',
		__( "Orders Settings:", 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_settings_section_callback_payment_order',
		'seamlesschex_register_setting'
	);
        
        add_settings_field(
		'seamlesschex_settings_order_cron',
		__( "Update Order statuses", 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_settings_order_cron_render',
		'seamlesschex_register_setting',
                'seamlesschex_settings_order'
	);
        
//       ********************************************************************************************** 

	add_settings_section(
		'seamlesschex_settings_payment_method',
		__( "Checkout Settings:", 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_settings_section_callback_payment',
		'seamlesschex_register_setting'
	);
        
	add_settings_field(
		'seamlesschex_title',
		__( 'Payment Method Name*', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_title_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method',
		array("class" => "seamlesschex_required seamlesschex_validation")
	);
        
	add_settings_field(
		'schex_recurring_cycle',
		'',
		'schex_recurring_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method',
		array("class" => "hidden")
	);

	add_settings_field(
		'schex_recurring_installments',
		'',
		'schex_recurring_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method',
		array("class" => "hidden")
	);

	add_settings_field(
		'seamlesschex_gateway_variant',
		__( 'Payment Method Variant', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_gateway_variant_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method'
	);

	add_settings_field(
		'seamlesschex_gateway_recurring',
		__( 'Payment Frequency', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_gateway_recurring_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method'
	);

	add_settings_field(
		'seamlesschex_gateway_description',
		__( 'Payment Method Description', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_gateway_description_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method'
	);

	add_settings_field(
		'seamlesschex_extra_message',
		__( 'Additional message', 'woocommerce-gateway-seamlesschex' ),
		'seamlesschex_extra_message_render',
		'seamlesschex_register_setting',
		'seamlesschex_settings_payment_method'
	);
}

function seamlesschex_tokenization_render(){}
function schex_recurring_render(){}


function seamlesschex_tokenization_use_render() {
    require_once('gateway.php');
    $gateway = new WC_Gateway_SeamlessChex();

    $options = get_option('seamlesschex_settings');
    try {
        $options['seamlesschex_tokenization_use'] = 0;
        update_option("seamlesschex_settings", $options);
    } catch (Exception $e) {
        
    }
}

/**
* Settings:
*
* Render the API select field in SeamlessChex settings page
*/
function seamlesschex_api_endpoint_render(  ) {
	$options = get_option( 'seamlesschex_settings' );

	if(isset($options['seamlesschex_api_endpoint'])){
		if($options['seamlesschex_api_endpoint'] == SCX_ENDPOINT_LINK_LIVE){
			?>
			<select name='seamlesschex_settings[seamlesschex_api_endpoint]'>
				<option value='<?php echo SCX_ENDPOINT_LINK_LIVE ?>' <?php selected( $options['seamlesschex_api_endpoint'], SCX_ENDPOINT_LINK_LIVE ); ?>>Live</option>
				<option value='<?php echo SCX_ENDPOINT_LINK_TEST ?>' <?php selected( $options['seamlesschex_api_endpoint'], SCX_ENDPOINT_LINK_TEST ); ?>>Test</option>
			</select>
			<?php
		}
		else{
			?>
			<select name='seamlesschex_settings[seamlesschex_api_endpoint]'>
				<option value='<?php echo SCX_ENDPOINT_LINK_TEST ?>' <?php selected( $options['seamlesschex_api_endpoint'], SCX_ENDPOINT_LINK_TEST ); ?>>Test</option>
				<option value='<?php echo SCX_ENDPOINT_LINK_LIVE ?>' <?php selected( $options['seamlesschex_api_endpoint'], SCX_ENDPOINT_LINK_LIVE ); ?>>Live</option>
			</select>
			<?php
		}
	}
	else{
		?>
		<select name='seamlesschex_settings[seamlesschex_api_endpoint]'>
			<option value='<?php echo SCX_ENDPOINT_LINK_LIVE ?>' selected="selected">Live</option>
			<option value='<?php echo SCX_ENDPOINT_LINK_TEST ?>'>Test</option>
		</select>
		<?php
	}
}

function seamlesschex_settings_order_cron_render() {
    $options = get_option('seamlesschex_settings');
        ?>
    		<select name='seamlesschex_settings[seamlesschex_settings_order_cron]'>
    			<option value='900' <?php selected( $options['seamlesschex_settings_order_cron'], 900); ?>>Every 15 min</option>
    			<option value='1800' <?php selected( $options['seamlesschex_settings_order_cron'], 1800); ?>>Every 30 min</option>
    			<option value='3600' <?php selected( $options['seamlesschex_settings_order_cron'], 3600); ?>>Every hours</option>
    			<option value='86400' <?php selected( $options['seamlesschex_settings_order_cron'], 86400); ?>>Every day</option>
    			<option value='0' <?php selected( $options['seamlesschex_settings_order_cron'], 0); ?>>Disabled</option>
    		</select>

    	
    <?php
}

function seamlesschex_gateway_variant_render() {
    $options = get_option('seamlesschex_settings');
    
        ?>
        <table>
        	<tr>
        		<td style="vertical-align:top;padding:0;">
		    		<select id="seamlesschex-gateway-variant" name='seamlesschex_settings[seamlesschex_gateway_variant]'>
		    			<option value='0' <?php selected( $options['seamlesschex_gateway_variant'], 0); ?>>Pay By eCheck</option>
		    			<option value='1' <?php selected( $options['seamlesschex_gateway_variant'], 1); ?>>Pay By eCheck & Pay with US Bank Account</option>
		    			<option value='2' <?php selected( $options['seamlesschex_gateway_variant'], 2); ?>>Pay with US Bank Account</option>
		    		</select>
	    		</td>    		
    		</tr>
    	</table>
    		<script type="text/javascript">
    			jQuery('#seamlesschex-gateway-variant').change(function() {

				});
    		</script>
    	
    <?php
}

function seamlesschex_gateway_recurring_render() {
    $options = get_option('seamlesschex_settings');
    $options_schex_recurring_cycle = isset($options['schex_recurring_cycle']) ? $options['schex_recurring_cycle'] : 'month';
    $options_schex_recurring_installments = isset($options['schex_recurring_installments']) ? $options['schex_recurring_installments'] : 0;
        ?>
        	<table><tr>
        		<td style="vertical-align:top;padding:0;">
    		<select id="seamlesschex-gateway-recurring" name='seamlesschex_settings[seamlesschex_gateway_recurring]'>
    			<option value='0' <?php selected( $options['seamlesschex_gateway_recurring'], 0); ?>>One Time</option>
    			<option value='1' <?php selected( $options['seamlesschex_gateway_recurring'], 1); ?>>Monthly Subscription</option>
    			<option value='2' <?php selected( $options['seamlesschex_gateway_recurring'], 2); ?>>Custom Recurring</option>
    		</select>
    		</td>
    		
    		<td style="vertical-align:top;padding:0 50px;">
    			<div id="schex-recurring-hidden-block" class="form-row" style="display: <?= $options['seamlesschex_gateway_recurring'] == 2 ? 'block' : 'none';?>">
    			<label style="margin-right: 10px;" for="schex-recurring-cycle"><strong>Billing Period</strong>
    			<span class="tooltip" title="The recurring payment will occur with a selected frequency. The options available for recurring payments are daily, weekly, bi-weekly or monthly."><i class="fas fa-info-circle"></i>
    		    </span>
    		    </label>

    		<select id="schex-recurring-cycle" 
    		class="woocommerce-form__select" 
    		autocomplete="off"
    		name="seamlesschex_settings[schex_recurring_cycle]"> 
<!--     		data-placeholder="Select an option…">
    			<option value>Select an option…</option> -->
    			<option value="day" <?php selected( $options_schex_recurring_cycle, 'day'); ?>>Day</option>
    			<option value="week" <?php selected( $options_schex_recurring_cycle, 'week'); ?>>Week</option>
    			<option value="bi-weekly" <?php selected( $options_schex_recurring_cycle, 'bi-weekly'); ?>>Bi-weekly</option>
    			<option value="month" <?php selected( $options_schex_recurring_cycle, 'month'); ?>>Month</option>
    			<option value="month3" <?php selected( $options_schex_recurring_cycle, 'month3'); ?>>Every 3 months</option>
    			<option value="month6" <?php selected( $options_schex_recurring_cycle, 'month6'); ?>>Every 6 months</option>
    			<option value="year" <?php selected( $options_schex_recurring_cycle, 'year'); ?>>Every year</option>
    		</select>
    		<br><br>
    	    
    <label style="margin-right: 40px;" for="schex-recurring-installments"><strong>Duration</strong>
    	<span class="tooltip" title="Submit if you require the recurring payments to occur a specific number of times or to be indefinite.<br><b>0</b> - indefinite (ongoing until cancelled).<br><b>1, 2, 3...N</b> - number of recurring payments."><i class="fas fa-info-circle"></i></span></label>
    <input style="max-width: 100px;" min="0" id="schex-recurring-installments" class="input-text" type="number"  autocomplete="off" 
    name="seamlesschex_settings[schex_recurring_installments]" value="<?=$options_schex_recurring_installments;?>"/> 
    	    </div>   			
    		</td></tr></table>
    		<script type="text/javascript">
    			jQuery('#seamlesschex-gateway-recurring').change(function() {

    				var sel = jQuery(this).find(":selected").val();
  					console.log( sel );

  					if (sel == 1) {
  						jQuery('#schex-recurring-cycle').val('Month');
  						jQuery('#schex-recurring-installments').val('0');
  						jQuery('#schex-recurring-hidden-block').hide();
  					} else if (sel == 2) {

  						jQuery('#schex-recurring-hidden-block').show();
  					} else {
  						jQuery(this).val('0');
  						jQuery('#schex-recurring-cycle').val('');
  						jQuery('#schex-recurring-installments').val('0');
  						jQuery('#schex-recurring-hidden-block').hide();
  					}
				});
    		</script>
    	
    <?php
}

/**
* Settings:
*
* Render the Client ID field in SeamlessChex settings page
*/
function seamlesschex_test_api_key_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_test_api_key']) ? $options['seamlesschex_test_api_key'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_test_api_key]' value='".esc_attr($value)."' required='required'/>";
}

function seamlesschex_test_api_public_key_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_test_api_public_key']) ? $options['seamlesschex_test_api_public_key'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_test_api_public_key]' value='".esc_attr($value)."' required='required'/>";
}

/**
* Settings:
*
* Render the API password field in SeamlessChex settings page
*/
function seamlesschex_live_api_key_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_live_api_key']) ? $options['seamlesschex_live_api_key'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_live_api_key]' value='".esc_attr($value)."' required='required'/>";
}

function seamlesschex_live_api_public_key_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_live_api_public_key']) ? $options['seamlesschex_live_api_public_key'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_live_api_public_key]' value='".esc_attr($value)."' required='required'/>";
}

function seamlesschex_woo_rest_test_api_key_render(){
	$options = get_option("seamlesschex_settings");
	$value = isset($options['seamlesschex_woo_rest_client_id']) ? $options['seamlesschex_woo_rest_client_id'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_woo_rest_client_id]' value='".esc_attr($value)."' required='required' /><p></p>";
}

function seamlesschex_woo_rest_client_secret_render(){
	$options = get_option("seamlesschex_settings");
	$value = isset($options['seamlesschex_woo_rest_client_secret']) ? $options['seamlesschex_woo_rest_client_secret'] : "";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_woo_rest_client_secret]' value='".esc_attr($value)."' required='required'/><p></p>";
}

/**
* Settings:
*
* Render the API URL field in SeamlessChex settings page
*/
function seamlesschex_site_url_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_site_url']) ? $options['seamlesschex_site_url'] : get_site_url();
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_site_url]' value='".esc_attr($value)."' size='60' />";
}

/**
* Settings:
*
* Render the Title field in SeamlessChex settings page
*/
function seamlesschex_title_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_title']) ? $options['seamlesschex_title'] : "Pay with US Bank Account";
	echo "<input type='text' name='seamlesschex_settings[seamlesschex_title]' value='".esc_attr($value)."' required='required'/>";
}

/**
* Settings:
*
* Render the Description field in SeamlessChex settings page
*/
function seamlesschex_gateway_description_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_gateway_description']) ? trim($options['seamlesschex_gateway_description']) : "";
	echo "<textarea cols='40' rows='5' name='seamlesschex_settings[seamlesschex_gateway_description]'>$value</textarea>";
}

/**
* Settings:
*
* Render the Debug log checkbox in SeamlessChex settings page
*/
function seamlesschex_debug_log_render(  ) {
	$options = get_option( 'seamlesschex_settings' );
	?>
	<input type='checkbox' name='seamlesschex_settings[seamlesschex_debug_log]' <?php if(isset($options['seamlesschex_debug_log'])){
		checked( $options['seamlesschex_debug_log'], 1 );
	} else {
		checked( 0, 1 );
	}; ?> value='1'>

	<?php
}


/**
* Settings:
*
* Render the Extra message field in SeamlessChex settings page
*/
function seamlesschex_extra_message_render(  ) {

	$options = get_option( 'seamlesschex_settings' );
	$value = isset($options['seamlesschex_extra_message']) ? trim($options['seamlesschex_extra_message']) : "";
	echo "<textarea cols='40' rows='5' name='seamlesschex_settings[seamlesschex_extra_message]'>$value</textarea>";
}

/**
* Settings:
*
* Insert extra text into options page
* Left blank for aesthetic reasons
*/
function seamlesschex_settings_section_callback() {
	$options = get_option( 'seamlesschex_settings' );
	echo "<hr/>";
}

function seamlesschex_settings_section_callback_payment() {
	echo "<hr/>";
	echo "<p>These settings control how the SeamlessChex Payment Option will display on your store's checkout page.</p>";
}

function seamlesschex_settings_section_callback_payment_order() {
	echo "<hr/>";
        echo "These settings allow you to run the automatic order status update task, or disable it and run it manually only when you click the “Update eCheck statuses” button on the WooCommerce orders page.";
}

function seamlesschex_settings_validate($settings){
	require_once("gateway.php");

	$oldOptions = get_option('seamlesschex_settings');
	if(!isset($settings['seamlesschex_test_api_key']) || strlen($settings['seamlesschex_test_api_key']) === 0){
		add_settings_error("seamlesschex_settings", "seamlesschex_test_api_key", "SeamlessChexPay API Client ID can't be empty. Settings have been reverted.");
		$settings["seamlesschex_test_api_key"] = $oldOptions["seamlesschex_test_api_key"];
		return $settings;
	}

	if(!isset($settings['seamlesschex_live_api_key']) || strlen($settings['seamlesschex_live_api_key']) === 0){
		add_settings_error("seamlesschex_settings", "seamlesschex_live_api_key", "SeamlessChexPay API Password can't be empty. Settings have been reverted.");
		$settings["seamlesschex_live_api_key"] = $oldOptions["seamlesschex_live_api_key"];
		return $settings;
	}

	$gateway = new WC_Gateway_SeamlessChex();
	$gateway->endpoint = trailingslashit($settings["seamlesschex_api_endpoint"]);
	$gateway->client_id = $settings["seamlesschex_test_api_key"];
	$gateway->live_api = $settings["seamlesschex_live_api_key"];

	if(!$gateway->test_authentication()){
		add_settings_error("seamlesschex_settings", "seamlesschex_live_api_key", "Authentication of the SeamlessChex API credentials failed for your selected mode. Settings have been reverted. Error returned: " . $gateway->seamlesschex_getLastError());
		$settings["seamlesschex_live_api_key"] = $oldOptions["seamlesschex_live_api_key"];
		$settings["seamlesschex_test_api_key"] = $oldOptions["seamlesschex_test_api_key"];
		return $settings;
	}

	if(!isset($settings['seamlesschex_woo_rest_client_id']) || strlen($settings['seamlesschex_woo_rest_client_id']) === 0){
		add_settings_error("seamlesschex_settings", "seamlesschex_woo_rest_client_id", "Use of the tokenization widget requires WooCommerce REST API access. Your Rest Client ID is empty. Settings have been reverted.");
		$settings["seamlesschex_woo_rest_client_id"] = $oldOptions["seamlesschex_woo_rest_client_id"];
		return $settings;
	}

	if(!isset($settings['seamlesschex_woo_rest_client_secret']) || strlen($settings['seamlesschex_woo_rest_client_secret']) === 0){
		add_settings_error("seamlesschex_settings", "seamlesschex_woo_rest_client_secret", "Use of the tokenization widget requires WooCommerce REST API access. Your Rest Client Secret is empty. Settings have been reverted.");
		$settings["seamlesschex_woo_rest_client_secret"] = $oldOptions["seamlesschex_woo_rest_client_secret"];
		return $settings;
	}

	$gateway->rest_client_id = $settings['seamlesschex_woo_rest_client_id'];
	$gateway->rest_client_secret = $settings['seamlesschex_woo_rest_client_secret'];
  
	if(!$gateway->register_store($settings["seamlesschex_site_url"] = get_site_url())) {
		add_settings_error("seamlesschex_settings", "seamlesschex_woo_rest_failed", "SeamlessChex was unable to contact your store's REST API using the given REST Client ID and Secret. Settings have been reverted. Error returned: " . $gateway->seamlesschex_getLastError());
		$settings["seamlesschex_woo_rest_client_id"] = $oldOptions["seamlesschex_woo_rest_client_id"];
		$settings["seamlesschex_woo_rest_client_secret"] = $oldOptions["seamlesschex_woo_rest_client_secret"];
		$settings["seamlesschex_site_url"] = $oldOptions["seamlesschex_site_url"];
		return $settings;
	}

	return $settings;
}

function seamlesschex_getPublicKey($options){
    if($options["seamlesschex_api_endpoint"] == SCX_ENDPOINT_LINK_LIVE){
       echo $options["seamlesschex_live_api_public_key"];
    }else{
       echo $options["seamlesschex_test_api_public_key"];
    }
}

function seamlesschex_getChechoutItem(){
	$count=count(WC()->cart->get_cart());
    $collect = "'[";  
    $i=0;
    foreach ( WC()->cart->get_cart() as $key => $cart_item ) { 
	    $i++;    
	    $title = wc_get_product( $cart_item["data"]->get_id())->get_title();
	    $quantity = $cart_item['quantity'];
	    $price = (int) get_post_meta($cart_item["product_id"] , "_price", true);
	    $total = $price*$quantity;
	    $collect .= '{"title":"'. $title.' x'.$quantity.'","price":"'.$total.'"}';
	    if($i != $count){
	        $collect .= ",";
	    }         
    }
    $collect.="]'";
    
    return $collect;
}

/**
* Settings:
*
* Main function to inject settings into wordpress admin menu
*/
function seamlesschex_options_page(  ) {
    ?>
<style type="text/css">
.seamlesschex_validation input:invalid {
    border-color: #900;
    background-color: #FDD;
}
</style>
	<form action='options.php' method='post'>
            <h1>SeamlessChex Pay</h1><br>
            <div style="font-size: 14px;">Plugin About: <a href="https://seamlesschex.zendesk.com/hc/en-us/articles/360048631111" target="_blank"> https://seamlesschex.zendesk.com/hc/en-us/articles/360048631111</a></div>
            <div style="font-size: 14px;">Plugin Help: <a href="https://seamlesschex.zendesk.com" target="_blank">https://seamlesschex.zendesk.com</a></div>
            <br>
		<?php
		settings_errors();
		settings_fields( 'seamlesschex_register_setting' );
		do_settings_sections( 'seamlesschex_register_setting' );
		submit_button('Save Changes', 'primary', 'seamlesschex_submit');
                ?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$("#seamlesschex_submit").click(function () {
                                    <?php // scx_cron_starter();?>
					var required = $(".seamlesschex_required").find("input, textarea, select");
					for (var i = 0; i < required.length; i++) {
						var input = $(required[i]);
						if (!seamlesschex_checkvalid(input, true)) {
							return false;
						}
					}
				});

				$(".seamlesschex_validation input, g.seamlesschex_validation textarea").blur(function () {
					var input = $(this);
					seamlesschex_checkvalid(input, false);
				});
			});

			function seamlesschex_checkvalid(input, scrollTo) {
				if (scrollTo !== true) scrollTo = false;

				if (jQuery(input).val().trim().length === 0) {
					jQuery(input)[0].setCustomValidity("Required");
					if (scrollTo) {
						jQuery(input)[0].scrollIntoView({ behavior: "smooth", block: "center" });
					}
					return false;
				} else {
					jQuery(input)[0].setCustomValidity("");
					return true;
				}
			}
		</script>
	</form>
	<?php
}
add_action( 'admin_menu', 'seamlesschex_add_admin_menu' );
add_action( 'admin_init', 'seamlesschex_settings_init' );

?>