<?php
/* 
Plugin Name: Verotel Flexpay Gateway for Woocommerce
Plugin URI:
Description: Allows for the use of Verotel Flexpay as a payment gateway for Woocommerce 
Author: Robert Lopez
Version: 1.1
Author URI: http://www.aboutrobertlopez.com 
*/ 

// Create class
add_action( 'plugins_loaded', 'init_verotel_gateway_class' );

//Update order status after successful payment
function update_order_on_success($order_id){
	if (isset ($order_id)){
		$order_id = str_replace('orderNo_','', $order_id);
		global $woocommerce;
		$order = new WC_Order($order_id);
		
		//Empty cart
		$woocommerce -> cart -> empty_cart();
		
		//Place order as Processing and add note
		//$order -> update_status('processing');
		
		// Payment complete
		$order->payment_complete();
		
	}
}

function thank_you_page()
{
	if(isset($_REQUEST['custom1'])){
		update_order_on_success($_REQUEST['custom1']);
	}
}

add_action('get_footer','thank_you_page');

// Extend WC’s base gateway class so that you have access to important methods and the settings API
function init_verotel_gateway_class() {
	if(!class_exists('WC_Payment_Gateway')) return;
	
	class WC_Verotel_Gateway extends WC_Payment_Gateway {
	  public function __construct(){
      $this -> id = 'verotel_flexpay';
      $this -> method_title = 'Verotel Flexpay';
      $this -> has_fields = false;
 
      $this -> init_form_fields();
      $this -> init_settings();
 
      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> shop_id = $this -> settings['shop_id'];
      $this -> signature = $this -> settings['signature'];
 
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
 
      //add_action('init', array(&$this, 'check_verotel_response'));
	  
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
             } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
			
      //add_action('woocommerce_receipt_verotel', array(&$this, 'receipt_page'));
	  }
	  
	  function init_form_fields(){
		  $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'mrova'),
                    'type' => 'checkbox',
                    'label' => __('Enable Verotel Flexpay Payment Module.', 'mrova'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:', 'mrova'),
                    'type'=> 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'mrova'),
                    'default' => __('Credit or Debit Card', 'mrova')),
                'description' => array(
                    'title' => __('Description:', 'mrova'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'mrova'),
                    'default' => __('Pay securely by Credit or Debit card through Verotel Secure Servers.', 'mrova')),
                'shop_id' => array(
                    'title' => __('Shop ID', 'mrova'),
                    'type' => 'text',
                    'description' => __('Enter the Shop ID provided to you by Verotel')),
                'signature' => array(
                    'title' => __('Signature Key', 'mrova'),
                    'type' => 'text',
                    'description' =>  __('Enter the signature key provided to you by Verotel', 'mrova'),
                )
            );
	  }
	  
	  public function admin_options(){
      echo '<h3>'.__('Verotel Flexpay Payment Gateway', 'mrova').'</h3>';
      echo '<table class="form-table">';
      // Generate the HTML For the settings form.
      $this -> generate_settings_html();
      echo '</table>';
		
	  }
 
    /**
     *  There are no payment fields for payu, but we want to show the description if set.
     **/
    function payment_fields(){
        if($this -> description) echo wpautop(wptexturize($this -> description));
    }

    //Process payment and return the result
    function process_payment($order_id){
		global $woocommerce;
        $order = new WC_Order($order_id);
		
		//Create Verotel Link
		$productInfo = 'Order_'. $order_id;
		$str = $this->signature .':custom1=orderNo_'. $order_id .':description=' . $productInfo . ':priceAmount='. $order->order_total .':priceCurrency=USD:shopID='. $this->shop_id .':version=1';
		$hash = sha1($str);
		$productInfo = str_replace(" ","+",$productInfo);
		$link = 'https://secure.verotel.com/order/purchase?priceAmount='. $order->order_total .'&priceCurrency=USD&signature='. $hash .'&version=1&shopID='. $this->shop_id .'&description=' . $productInfo . '&custom1=orderNo_' . $order_id;
        return array('result' => 'success', 'redirect' => $link);
    }
 
}
	//Add the Gateway to WooCommerce
	function add_verotel_flexpay_gateway_class( $methods ) {
		$methods[] = 'WC_Verotel_Gateway'; 
		return $methods;
	}
 
    add_filter( 'woocommerce_payment_gateways', 'add_verotel_flexpay_gateway_class' );
}