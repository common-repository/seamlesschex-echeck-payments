<?php

/*
  * This serves to build the manual enrollment only portion of the checkout page and outputs the routing and account number fields.
 */
if ($this->description) {
    echo wpautop(wptexturize(trim($this->description)));
}
if ($this->extra) {
    echo "<small>" . wptexturize(trim($this->extra)) . "</small>";
}
global $woocommerce, $post;

$options = get_option('seamlesschex_settings');
$options_schex_recurring_cycle = isset($options['schex_recurring_cycle']) ? $options['schex_recurring_cycle'] : 'month';

$options_schex_recurring_cycle = str_replace(['day','week','month3','month6','month','year','lyly','Monthlys'], ['Daily','Weekly','Every 3 months','Every 6 months','Monthly','Every year','ly', 'months'], $options_schex_recurring_cycle);


$options_schex_recurring_installments = isset($options['schex_recurring_installments']) ? $options['schex_recurring_installments'] : 0;

$options_schex_recurring_installments = $options_schex_recurring_installments ? ('for '.$options_schex_recurring_installments.' billing cycles.') : ('until canceled.'); 

$fields = array();
if($options['seamlesschex_gateway_variant'] == 0){    

    $default_fields = array( 
        'routing-number' => '<p class="form-row form-row-first validate-required ">
        <label for="' . esc_attr($this->id) . '-routing-number">' . __('Routing Number', 'woocommerce-gateway-seamlesschex') . ' <span class="required">*</span></label>
        <input id="' . esc_attr($this->id) . '-routing-number" class="input-text" type="text"  autocomplete="off" name="' . esc_attr($this->id) . '_routing_number" maxlength="9" />
        </p>',
        'account-number' => '<p class="form-row form-row-last validate-required ">
        <label for="' . esc_attr($this->id) . '-account-number">' . __('Account Number', 'woocommerce-gateway-seamlesschex') . ' <span class="required">*</span></label>
        <input id="' . esc_attr($this->id) . '-account-number" class="input-text" type="text" name="' . esc_attr($this->id) . '_account_number"  autocomplete="off"  maxlength="17" />
        </p>',


        'recurring' => '<p class="form-row form-row-wide"><br>'.ucfirst($options_schex_recurring_cycle).' '.$options_schex_recurring_installments.'</p>',
        'subscription' => '<p class="form-row form-row-wide"><br>Every monthly until canceled.</p>',
    );
}elseif ($options['seamlesschex_gateway_variant'] == 1 ) {
    $default_fields = array(
        'pay-variant' =>'<div class="schex-accordion">
                <div class="schex-accordion-heading">
                    <label for="paybyecheck" style="display:block;padding:10px">
                      <input style="cursor: pointer;color: lightgrey;border-color: #cccccc;height: 15px;width: 15px;transition: all 0.2s ease-in-out;border: 1px solid;appearance: none;-moz-appearance: none;-webkit-appearance: none;border-radius: 50%;vertical-align: -2px;margin-right: 2px;" type="radio" id="paybyecheck" name="occupation" value="Pay By eCheck" required /> Pay By eCheck
                      
                    </label>
                </div>
                <div class="schex-accordion-contents">
                    <p class="form-row form-row-first validate-required ">
                    <label for="' . esc_attr($this->id) . '-routing-number">' . __('Routing Number', 'woocommerce-gateway-seamlesschex') . ' <span class="required">*</span></label>
                    <input id="' . esc_attr($this->id) . '-routing-number" class="input-text" type="text"  autocomplete="off" name="' . esc_attr($this->id) . '_routing_number" maxlength="9" />
                    </p>
                    <p class="form-row form-row-last validate-required ">
                    <label for="' . esc_attr($this->id) . '-account-number">' . __('Account Number', 'woocommerce-gateway-seamlesschex') . ' <span class="required">*</span></label>
                    <input id="' . esc_attr($this->id) . '-account-number" class="input-text" type="text" name="' . esc_attr($this->id) . '_account_number"  autocomplete="off"  maxlength="17" />
                    </p>
                </div>
            
                <div class="schex-accordion-heading">
                    <label for="payusbankaccount" style="display:block;padding:10px">
                      <input style="cursor: pointer;color: lightgrey;border-color: #cccccc;height: 15px;width: 15px;transition: all 0.2s ease-in-out;border: 1px solid;appearance: none;-moz-appearance: none;-webkit-appearance: none;border-radius: 50%;vertical-align: -2px;margin-right: 2px;" type="radio" id="payusbankaccount" name="occupation" value="Pay with US Bank Account" required /> Pay with US Bank Account
                      
                    </label>
                </div>
                <div class="schex-accordion-contents">
                    <p>
                    <div class="account-to-account-redirect-box" style="">
                        <div class="account-to-account-redirect-icon" style="width: 200px; margin: 20px auto;">
                            <svg viewBox="-252.3 356.1 163 80.9" class="Quizd" style="height: 5.785714285714286em;">
                            <path fill="none" stroke="#939393" stroke-miterlimit="10" stroke-width="2" d="M-108.9 404.1v30c0 1.1-.9 2-2 2H-231c-1.1 0-2-.9-2-2v-75c0-1.1.9-2 2-2h120.1c1.1 0 2 .9 2 2v37m-124.1-29h124.1"></path>
                            <circle cx="-227.8" cy="361.9" r="1.8" fill="#939393"></circle>
                            <circle cx="-222.2" cy="361.9" r="1.8" fill="#939393"></circle>
                            <circle cx="-216.6" cy="361.9" r="1.8" fill="#939393"></circle>
                            <path fill="none" stroke="#939393" stroke-miterlimit="10" stroke-width="2" d="M-128.7 400.1H-92m-3.6-4.1l4 4.1-4 4.1"></path>
                            </svg>
                        </div>
                        <div class="account-to-account-redirect-message" style="width: 75%; margin: 0 auto; font-size: 14px; color: #737373; text-align: center;">
                            <p class="message">Click “Pay now” and you will be redirected to your bank to complete your purchase securely.</p>
                        </div>
                    </div>
                  </p>
                  <input id="seamless-pid" type="hidden" name="seamless_pid" />
                </div>
            </div>',

         'recurring' => '<p class="form-row form-row-wide"><br>'.ucfirst($options_schex_recurring_cycle).' '.$options_schex_recurring_installments.'</p>',
         'subscription' => '<p class="form-row form-row-wide"><br>Every monthly until canceled.</p>',
        
        
    );
    
}else if ($options['seamlesschex_gateway_variant'] == 2) {
   $default_fields = array( 
    'pay-variant' =>'<p>
                    <div class="account-to-account-redirect-box" style="">
                        <div class="account-to-account-redirect-icon" style="width: 200px; margin: 20px auto;">
                            <svg viewBox="-252.3 356.1 163 80.9" class="Quizd" style="height: 5.785714285714286em;">
                            <path fill="none" stroke="#939393" stroke-miterlimit="10" stroke-width="2" d="M-108.9 404.1v30c0 1.1-.9 2-2 2H-231c-1.1 0-2-.9-2-2v-75c0-1.1.9-2 2-2h120.1c1.1 0 2 .9 2 2v37m-124.1-29h124.1"></path>
                            <circle cx="-227.8" cy="361.9" r="1.8" fill="#939393"></circle>
                            <circle cx="-222.2" cy="361.9" r="1.8" fill="#939393"></circle>
                            <circle cx="-216.6" cy="361.9" r="1.8" fill="#939393"></circle>
                            <path fill="none" stroke="#939393" stroke-miterlimit="10" stroke-width="2" d="M-128.7 400.1H-92m-3.6-4.1l4 4.1-4 4.1"></path>
                            </svg>
                        </div>
                        <div class="account-to-account-redirect-message" style="width: 75%; margin: 0 auto; font-size: 14px; color: #737373; text-align: center;">
                            <p class="message">Click “Pay now” and you will be redirected to your bank to complete your purchase securely.</p>
                        </div>
                        <input id="seamless-routing-number" type="hidden" name="seamless_routing_number" />
                        <input id="seamless-account-number" type="hidden" name="seamless_account_number" />
                        <input id="seamless-pid" type="hidden" name="seamless_pid" />
                    </div>
                  </p>',
   ); 
}
$fields = wp_parse_args($fields, apply_filters('woocommerce_gateway_seamlesschex_checkout_fields', $default_fields, $this->id));
$outputHTML = "<fieldset id='wc-" . esc_attr($this->id) . "-check-form' class='wc-credit-card-form wc-payment-form'>";


if (!$this->if_recurring) {
    unset(
            $fields['recurring'], 
            $fields['subscription']
    );
} else if ($this->if_recurring == 1) {
     unset(
            $fields['recurring']);
    } else if ($this->if_recurring == 2) {
     unset(
            $fields['subscription']);
    }

foreach ($fields as $key => $field) {
    $outputHTML .= $field;
}
$outputHTML .= "<div class='clear'></div></fieldset>";

echo $outputHTML;

?>
<script>
  jQuery(document).ready(function() {
    jQuery("#place_order").after('<div class="seamlesschex-wrapper-pay-buttons" style="display:none"></div>');
  
    <?php
    if ($options['seamlesschex_gateway_variant'] == 2) {
      ?>
      jQuery("#place_order").hide();
      jQuery(".seamlesschex-wrapper-pay-buttons").show();
      <?php 
    } else if ($options['seamlesschex_gateway_variant'] == 0) {
      ?>
        jQuery("#place_order").show();
        jQuery(".seamlesschex-wrapper-pay-buttons").hide();
      <?php 
    }
    ?>

    jQuery('#paybyecheck').on('click', function(){
        jQuery("#seamless-routing-number").val('');
        jQuery("#seamless-account-number").val('');
        jQuery("#place_order").show();
        jQuery(".seamlesschex-wrapper-pay-buttons").hide();  
    });

    jQuery('#payusbankaccount').on('click', function(){
      jQuery("#place_order").hide();
      jQuery(".seamlesschex-wrapper-pay-buttons").show();
    });


   
          var objRequestRedirect = {
            publicKey: '<?php echo seamlesschex_getPublicKey($options);?>',         
            sandbox: <?php echo $options['seamlesschex_api_endpoint'] == SCX_ENDPOINT_LINK_LIVE ? 'false' : 'true';?>,
            displayMethod: 'iframe',
            widgetContainerSelector: 'seamlesschex-wrapper-pay-buttons',
            storeName: '<?php echo get_bloginfo( 'name' ); ?>',
            style: {
              buttonClass: 'button seamlesschex-pay-button',
              // buttonColor: '#00b660',
              // buttonLabelColor: '#ffffff',
              buttonLabel: 'Place order'
            },
            iframe: {
                width: '570px',
                height: '650px',
                background: '#f0f0f0'
            },
            lightBox: {
              title: 'Your order',
              subtitle: '',
              logoUrl: "<?php echo esc_url( wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ) ); ?>",
              formButtonLabel: 'PAY NOW'
            },
            checkout: {
              totalValue: '<?php echo $woocommerce->cart->total; ?>',
              currency: '<?php echo get_woocommerce_currency() ? get_woocommerce_currency() : 'USD'; ?>',
              memo: ' ',
              items: jQuery.parseJSON(<?php echo seamlesschex_getChechoutItem()?>),
              customerEmail: 'noreply@seamlesschex.com',
              customerFirstName: 'FirstName',
              customerLastName: 'LastName',
              label: 'verify only'
            },
            onSuccess: function(data) {
              jQuery("#seamless-routing-number").val(data.routing_number);
              jQuery("#seamless-account-number").val(data.account_number);
              jQuery("#seamless-pid").val(data.pid);
              jQuery("#place_order").submit()
            },
            onExit: function() {
              console.log('Exit');
            },
            onError: function(error) {
              console.log(error);
            },
            onCancel: function() {
              console.log('Cancel');
            }
          };

          var seamlesschexRedirect = new SCHEX(objRequestRedirect);
        seamlesschexRedirect.render();  
            
      

    jQuery(".schex-accordion").on("click", ".schex-accordion-heading", function() {
       jQuery(this).addClass("active").next().css('display','inline-block');
       jQuery(".schex-accordion-heading").find('input').attr('checked', false); 
       jQuery(this).find('input').attr('checked', true);   
       jQuery(".schex-accordion-contents").not(jQuery(this).next()).slideUp(300);
       //jQuery(".schex-accordion-heading").not(jQuery(this).next()).find('input').attr('checked', false); 
       jQuery(this).siblings().removeClass("active");
    });

    
  });
</script>

<style>
  .seamlesschex-pay-button {
    width: 100%;
  }
.accordion {
    max-width: 300px;
    background: linear-gradient(to bottom right, #FFF, #f7f7f7);
    background: #0097a7;
    margin: 0 auto;
    border-radius: 3px;
    box-shadow: 0 10px 15px -20px rgba(0, 0, 0, 0.3), 0 30px 45px -30px rgba(0, 0, 0, 0.3), 0 80px 55px -30px rgba(0, 0, 0, 0.1);
}
.schex-accordion-heading {
    border-bottom: 1px solid #d1d1d1;
    background: #fff;  
}

.schex-accordion-heading input[type="radio"]:checked {
  
  background: #d1d1d1;  
}

.schex-accordion-contents {
    display: none;
    background: #FFFAFA;
    padding: 15px;
    color: #7f8fa4;
    font-size: 13px;
    line-height: 1.5;
}
.active .schex-accordion-contents {
    display: inline-block;
}
</style>   