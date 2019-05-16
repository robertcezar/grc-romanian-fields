<?php
	/*
		Plugin Name: Check CIF/CUI RO
		Description: Checks CIF/CUI through API from Anaf - https://static.anaf.ro/static/10/Anaf/Informatii_R/documentatie_SW_01112017.txt
		Version:     1.0
		Author: Gheorghiu Robert Cezar -  robertviruss[@]gmail.com
		Author URI: //
	*/	
	
	/**
		* Check if WooCommerce is active
	**/
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		
		/* WooCommerce - add Romanian checkout fields - CIF, Nr.Reg.Com., Bank account, Bank number */
		
		//global array to reposition the elements to display as you want (e.g. kept 'cif' after 'company' )
		$grc_address_fields = array(
		'first_name',
		'last_name',
		'company',
		'b_cif',
		'b_nrregcom',
		'b_cont',
		'b_banca',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country');
		
		//global array only for extra fields
		$grc_ext_fields = array('b_cif','b_nrregcom','b_cont','b_banca');
		
		
		//overide default fields
		add_filter( 'woocommerce_default_address_fields' , 'grc_override_default_address_fields' );
		
		function grc_override_default_address_fields( $address_fields ){
			
			$temp_fields = array();
			
			$address_fields['b_cif'] = array(
			'label'     => __('CIF:', 'woocommerce'),
			'required'  => false,
			'class'     => array('form-row-first'),
			'type'  => 'text'
			);
			
			$address_fields['b_nrregcom'] = array(
			'label'     => __('Nr.Reg.Com.:', 'woocommerce'),
			'required'  => false,
			'class'     => array('form-row-last'),
			'type'  => 'text'
			);
			$address_fields['b_cont'] = array(
			'label'     => __('Cont:', 'woocommerce'),
			'required'  => false,
			'class'     => array('form-row-first'),
			'type'  => 'text'
			);
			$address_fields['b_banca'] = array(
			'label'     => __('Banca:', 'woocommerce'),
			'required'  => false,
			'class'     => array('form-row-last'),
			'type'  => 'text'
			);
			
			$address_fields['company']['required'] = true;
			
			global $grc_address_fields;
			
			foreach($grc_address_fields as $fky){       
				$temp_fields[$fky] = $address_fields[$fky];
			}
			
			$address_fields = $temp_fields;
			
			return $address_fields;
		}
		
		
		//concatenate the orher custom fields with company 
		add_filter('woocommerce_formatted_address_replacements', 'grc_formatted_address_replacements', 99, 2);
		
		function grc_formatted_address_replacements( $address, $args ){
			
			$address['{company}'] = $args['company']."\n".$args['b_cif']."\n".$args['b_nrregcom']."\n".$args['b_cont']."\n".$args['b_banca']; //show title along with name
			
			return $address;
		} 
		
		
		add_filter( 'woocommerce_order_formatted_billing_address', 'grc_update_formatted_billing_address', 99, 2);
		
		function grc_update_formatted_billing_address( $address, $obj ){
			
			global $grc_address_fields;
			
			if(is_array($grc_address_fields)){
				
				foreach($grc_address_fields as $waf){
					$address[$waf] = $obj->{'billing_'.$waf};
				}
			}
			
			return $address;    
		}
		
		
		
		//if you want to add also  fields to shipping adress uncomment this
		/* 	
			add_filter( 'woocommerce_order_formatted_shipping_address', 'grc_update_formatted_shipping_address', 99, 2);
			
			function grc_update_formatted_shipping_address( $address, $obj ){
			
			global $grc_address_fields;
			
			if(is_array($grc_address_fields)){
			
			foreach($grc_address_fields as $waf){
			$address[$waf] = $obj->{'shipping_'.$waf};
			}
			}   
			
			return $address;    
		} */
		
		
		add_filter('woocommerce_my_account_my_address_formatted_address', 'grc_my_account_address_formatted_address', 99, 3);
		
		function grc_my_account_address_formatted_address( $address, $customer_id, $name ){
			global $grc_address_fields;
			if(is_array($grc_address_fields)){
				
				foreach($grc_address_fields as $waf){
					$address[$waf] = get_user_meta( $customer_id, $name.'_'.$waf, true );
				}
			}
			return $address;
		}	
		
		
		add_filter('woocommerce_admin_billing_fields', 'grc_add_extra_customer_field');
		
		//if you want to add fields also to shipping adress uncomment this
		/* add_filter('woocommerce_admin_shipping_fields', 'grc_add_extra_customer_field'); */
		
		function grc_add_extra_customer_field( $fields ){
			
			//take back up of email and phone fields as they will be lost after repositioning
			$email = $fields['email']; 
			$phone = $fields['phone'];
			
			$fields = grc_override_default_address_fields( $fields );
			
			//reassign email and phone fields
			$fields['email'] = $email;
			$fields['phone'] = $phone;
			
			global $grc_ext_fields;
			
			if(is_array($grc_ext_fields)){
				
				foreach($grc_ext_fields as $wef){
					$fields[$wef]['show'] = false; //hide the way they are display by default as we have now merged them within the address field
				}
			}
			
			return $fields;
		}
		
		//remove from shipping fields
		function grc_custom_billing_fields( $fields = array() ) {
			unset($fields['shipping_b_cif']);
			unset($fields['shipping_b_nrregcom']);
			unset($fields['shipping_b_cont']);
			unset($fields['shipping_b_banca']);
			return $fields;
		}
		add_filter('woocommerce_shipping_fields','grc_custom_billing_fields');
		
		
		
		
		//Check CIF through Anaf API on checkout page
		add_action('woocommerce_checkout_process', 'is_cif', 10,2);
		
		//Check CIF through Anaf API on edit address page
		add_action( 'woocommerce_after_save_address_validation', 'is_cif', 10,2);

		function is_cif() {
			
			//check if post is done
			if (!empty($_POST["billing_b_cif"])){
				
				
				//API Url
				$url = 'https://webservicesp.anaf.ro/PlatitorTvaRest/api/v3/ws/tva';
				
				//Initiate cURL.
				$ch = curl_init($url);
				
				//The JSON data.
				$jsonData = array(
				'cui' => preg_replace("/[^0-9]/", "", $_POST['billing_b_cif']),
				'data' => date('Y-m-d')
				);
				
				//Encode the array into JSON.
				$jsonDataEncoded = json_encode([$jsonData]);
				
				//Tell cURL that we want to send a POST request.
				curl_setopt($ch, CURLOPT_POST, 1);
				
				//Attach our encoded JSON string to the POST fields.
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
				
				//Set the content type to application/json
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
				
				//Return JSON content
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
				
				//Execute the request
				$result = curl_exec($ch);
				
				//close curl
				curl_close($ch);
				
				//decode result
				$json = json_decode($result, true);
				
				//check if CIF exists
				if( !isset( $json['found'][0] ) ) 
				wc_add_notice( __( '<b>CIF/CUI</b> nu exista sau nu este corect' ), 'error' );	
			}
		};
		
	}	
	
	
?>
