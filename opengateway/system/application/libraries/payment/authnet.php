<?php

class authnet
{
	var $settings;
	
	function authnet() {
		$this->settings = $this->Settings();
	}

	function Settings()
	{
		$settings = array();
		
		$settings['name'] = 'Authorize.net';
		$settings['class_name'] = 'authnet';
		$settings['description'] = 'Authorize.net is the USA\'s premier gateway.  Coupled with the powerful Customer Information Manager (CIM), this gateway is an affordable and powerful gateway for any American merchant.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$99.00';
		$settings['monthly_fee'] = '$40.00';
		$settings['transaction_fee'] = '$0.10';
		$settings['purchase_link'] = 'http://www.authorize.net';
		$settings['allows_updates'] = 1;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 0;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'login_id',
										'transaction_key',
										'accept_visa',
										'accept_mc',
										'accept_discover',
										'accept_dc',
										'accept_amex'
										);
										
		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode',
																		'test' => 'Test Mode',
																		'dev' => 'Development Server'
																		)
														),
										'login_id' => array(
														'text' => 'Login ID',
														'type' => 'text'
														),
										'transaction_key' => array(
														'text' => 'Transaction Key',
														'type' => 'text'
														),
										'accept_visa' => array(
														'text' => 'Accept VISA?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_mc' => array(
														'text' => 'Accept MasterCard?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_discover' => array(
														'text' => 'Accept Discover?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_dc' => array(
														'text' => 'Accept Diner\'s Club?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														),
										'accept_amex' => array(
														'text' => 'Accept American Express?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Yes',
																		'0' => 'No'
																	)
														)
											);
		
		return $settings;
	}
	
	function TestConnection($client_id, $gateway) 
	{
		$post_url = $this->GetAPIUrl($gateway);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_num"		=> '4222222222222',
			"x_exp_date"		=> '1099',
			"x_amount"			=> 1,
			"x_test_request"    => TRUE
		);
		
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$order_id = 0;
		$response = $this->Process($order_id, $post_url, $post_string, TRUE);
		
		$CI =& get_instance();
		
		if($response['success']){
			return TRUE;
		} else {
			return FALSE;
		}
		
		return $response;
		
	}
	
	function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card)
	{	
		$CI =& get_instance();
		
		$post_url = $this->GetAPIUrl($gateway);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_num"		=> $credit_card['card_num'],
			"x_exp_date"		=> $credit_card['exp_month'] . '/' . substr($credit_card['exp_year'],-2,2),
			"x_amount"			=> $amount
		);
			
		if ($gateway['mode'] == 'test') {
			$post_values['x_test_request'] = 'TRUE';
		}

		if(isset($credit_card['cvv'])) {
			$post_values['x_card_code'] = $credit_card['cvv'];
		}	
		
		if (isset($customer['customer_id'])) {
			$post_values['x_first_name'] = $customer['first_name'];
			$post_values['x_last_name'] = $customer['last_name'];
			$post_values['x_address'] = $customer['address_1'];
			if (isset($customer['address_2']) and !empty($customer['address_2'])) {
				$post_values['x_address'] .= ' - '.$customer['address_2'];
			}
			$post_values['x_city'] = $customer['city'];
			$post_values['x_state'] = $customer['state'];
			$post_values['x_zip'] = $customer['postal_code'];
			$post_values['x_country'] = $customer['country'];
		}
			
		// build NVP post string
		$post_string = "";
		foreach($post_values as $key => $value) {
			$post_string .= "$key=" . urlencode( $value ) . "&";
		}
		$post_string = rtrim($post_string, "& ");
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	function Recur ($client_id, $gateway, $customer, $amount, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences = FALSE)
	{		
		$CI =& get_instance();
		
		// Create a new authnet profile if one doesn't exist
		$CI->db->select('api_customer_reference');
		$CI->db->join('client_gateways', 'subscriptions.gateway_id = client_gateways.client_gateway_id', 'inner');
		$CI->db->join('external_apis', 'client_gateways.external_api_id = external_apis.external_api_id', 'inner');
		$CI->db->where('api_customer_reference !=','');
		$CI->db->where('subscriptions.gateway_id',$gateway['gateway_id']);
		$CI->db->where('subscriptions.active', 1);
		$CI->db->where('subscriptions.customer_id',$customer['customer_id']);
		$current_profile = $CI->db->get('subscriptions');
				
		if ($current_profile->num_rows() > 0) {
			// save the profile ID
			$current_profile = $current_profile->row_array();
			$profile_id = $current_profile['api_customer_reference'];
			
			// get payment profile to see if a matching one exists
			$payment_profiles = $this->GetCustomerProfile($profile_id, $gateway);
			
			if (isset($payment_profiles->profile->paymentProfiles)) {
				foreach ($payment_profiles->profile->paymentProfiles as $payment_profile) {
					$card_number = (string)$payment_profile->payment->creditCard->cardNumber;
					
					if (substr($card_number, -4, 4) == substr($credit_card['card_num'],-4,4)) {
						$payment_profile_id = (string)$payment_profile->customerPaymentProfileId;
					}
				}
			}
		}
		else {
			$response = $this->CreateProfile($gateway, $subscription_id);
			
			if(isset($response) and !empty($response['success'])) {
				$profile_id = $response['profile_id'];	
			}
		}
		
		if (empty($profile_id)) {
			$add_text = (isset($response['reason'])) ? $response['reason'] : FALSE;
			die($CI->response->Error(5005, $add_text));
		}

		// save the api_customer_reference
		$CI->load->model('subscription_model');
		$CI->subscription_model->SaveApiCustomerReference($subscription_id, $profile_id);
		
		if (!isset($payment_profile_id) or empty($payment_profile_id)) {
			// Create the payment profile
			$response = $this->CreatePaymentProfile($profile_id, $gateway, $credit_card, $customer);
			if(isset($response) and is_array($response) and isset($response['payment_profile_id'])) {
				$payment_profile_id = $response['payment_profile_id'];	
			}
		}
		
		if (empty($payment_profile_id)) {
			$add_text = (isset($response['reason'])) ? $response['reason'] : FALSE;
			die($CI->response->Error(5006, $add_text));
		}
		
		// Save the api_payment_reference
		$CI->subscription_model->SaveApiPaymentReference($subscription_id, $payment_profile_id);
		
		// If a payment is to be made today, process it.
		if (date('Y-m-d', strtotime($start_date)) == date('Y-m-d')) {
			// Create an order for today's payment
			$CI->load->model('order_model');
			$order_id = $CI->order_model->CreateNewOrder($client_id, $gateway['gateway_id'], $amount, $credit_card, $subscription_id, $customer['customer_id'], $customer['ip_address']);
			
			$response = $this->ChargeRecurring($client_id, $gateway, $order_id, $profile_id, $payment_profile_id, $amount);
			
			if($response['success'] == TRUE){
				$CI->order_model->SetStatus($order_id, 1);
				$response_array = array('charge_id' => $order_id, 'recurring_id' => $subscription_id);
				$response = $CI->response->TransactionResponse(100, $response_array);
			} else {
				// Make the subscription inactive
				$CI->subscription_model->MakeInactive($subscription_id);
				
				$response_array = array('reason' => $response['reason']);
				$response = $CI->response->TransactionResponse(2, $response_array);
			}
		} else {
			$response = $CI->response->TransactionResponse(100, array('recurring_id' => $subscription_id));
		}
		
		return $response;
	}
	
	function CancelRecurring($client_id, $subscription)
	{	
		return TRUE;
	}
	
	function CreateProfile($gateway, $subscription_id)
	{
		$CI =& get_instance();
		
		$post_url = $this->GetAPIUrl($gateway, 'arb');
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
		"<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>".$gateway['transaction_key']."</transactionKey>
	    </merchantAuthentication>
		".
		"<profile>".
		"<merchantCustomerId>".$subscription_id."</merchantCustomerId>".
		"</profile>".
		"</createCustomerProfileRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		$return = array();
		
		if($response->messages->resultCode == 'Ok') {
			$return['success'] = TRUE;
			$return['profile_id'] = (string)$response->customerProfileId;
		} else {
			$return['success'] = FALSE;
			$return['reason'] = (string)$response->messages->message->text;
		}
		
		return $return;		
	}
	
	function CreatePaymentProfile($profile_id, $gateway, $credit_card, $customer)
	{
		$CI =& get_instance();
		
		$post_url = $this->GetAPIUrl($gateway, 'arb');
		
		$first_name = $customer['first_name'];
		$last_name = $customer['last_name'];
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
		"<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>".$gateway['transaction_key']."</transactionKey>
	    </merchantAuthentication>
		".
		"<customerProfileId>" . $profile_id . "</customerProfileId>".
		"<paymentProfile>".
		"<billTo>".
		 "<firstName>".$first_name."</firstName>".
		 "<lastName>".$last_name."</lastName>".
		"</billTo>".
		"<payment>".
		 "<creditCard>".
		  "<cardNumber>".$credit_card['card_num']."</cardNumber>".
		  "<expirationDate>".$credit_card['exp_year']."-".str_pad($credit_card['exp_month'], 2, "0", STR_PAD_LEFT)."</expirationDate>". // required format for API is YYYY-MM
		 "</creditCard>".
		"</payment>".
		"</paymentProfile>\n";
		if ($gateway['mode'] == 'test') {
			$content .= "<validationMode>liveMode</validationMode>\n";
		}
		$content .= "</createCustomerPaymentProfileRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		$return = array();
		
		if($response->messages->resultCode == 'Ok') {
			$return['success'] = TRUE;
			$return['payment_profile_id'] = (string)$response->customerPaymentProfileId;
		} else {
			$return['success'] = FALSE;
			$return['reason'] = (string)$response->messages->message->text;
		}
		
		return $return;
	}
	
	function GetCustomerProfile ($profile_id, $gateway) {
		$CI =& get_instance();
		
		$post_url = $this->GetAPIUrl($gateway, 'arb');
		
		$content = '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerProfileRequest
					xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
					<merchantAuthentication>
						<name>' . $gateway['login_id'] . '</name>
						<transactionKey>' . $gateway['transaction_key'] . '</transactionKey>
					</merchantAuthentication>
					<customerProfileId>' . $profile_id . '</customerProfileId>
					</getCustomerProfileRequest>';
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		return $response;
	}
	
	function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $order_id, $params['api_customer_reference'], $params['api_payment_reference'], $params['amount']);
	}
	
	function ChargeRecurring($client_id, $gateway, $order_id, $profile_id, $payment_profile_id, $amount)
	{		
		$CI =& get_instance();
		
		$post_url = $this->GetAPIUrl($gateway, 'arb');
		
		$content =
		"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		"<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
		 "<merchantAuthentication>
	        <name>".$gateway['login_id']."</name>
	        <transactionKey>" . $gateway['transaction_key'] . "</transactionKey>
	    </merchantAuthentication>".
		"<transaction>".
		"<profileTransAuthCapture>".
		"<amount>" . $amount . "</amount>". 
		"<customerProfileId>" . $profile_id . "</customerProfileId>".
		"<customerPaymentProfileId>" . $payment_profile_id . "</customerPaymentProfileId>".
		"<order>".
		"<invoiceNumber>".$order_id."</invoiceNumber>".
		"</order>".
		"</profileTransAuthCapture>".
		"</transaction>
		</createCustomerProfileTransactionRequest>";
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($request, CURLOPT_POSTFIELDS, $content); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close($request); // close curl object
		
		@$response = simplexml_load_string($post_response);
		
		if($response->messages->resultCode == 'Ok') {
			// Send a notification to the notification URL
			// $CI->load->library('notify');
			// $CI->notify->SendNotification($subscription_id);
			
			// Get the auth code
			$post_response = explode(',', $response->directResponse);
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $post_response[6], $post_response[4]);
			$response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['reason'] = (string)$response->messages->message->text;
		}
		
		return $response;	
	}
	
	function UpdateRecurring()
	{
		return TRUE;
	}
	
	function Process($order_id, $post_url, $post_string, $test = FALSE)
	{
		$CI =& get_instance();
		
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
		$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close ($request); // close curl object
		
		$response = explode('|',$post_response);

		if(!isset($response[1])) {
			$response['success'] = FALSE;
			return $response;
		}
		
		if($test) {
			if($response[0] == 1) {
				$response['success'] = TRUE;
			} else {
				$response['success'] = FALSE;
			}
	
			return $response;
		}
		// Get the response.  1 for the first part meant that it was successful.  Anything else and it failed
		if($response[0] == 1) {
			$CI->load->model('order_authorization_model');
			$CI->order_authorization_model->SaveAuthorization($order_id, $response[6], $response[4]);
			$CI->order_model->SetStatus($order_id, 1);
			
			$response['success'] = TRUE;
		} else {
			$CI->load->model('order_model');
			$CI->order_model->SetStatus($order_id, 0);
			
			$response['success'] = FALSE;
			$response['reason'] = $response[3];
		}

		return $response;

	}
	
	function GetAPIUrl ($gateway, $mode = FALSE) {
		if ($mode == FALSE) {
			// Get the proper URL
			switch($gateway['mode'])
			{
				case 'live':
					$post_url = $gateway['url_live'];
				break;
				case 'test':
					$post_url = $gateway['url_test'];
				break;
				case 'dev':
					$post_url = $gateway['url_dev'];
				break;
			}
		}
		elseif ($mode == 'arb') {
			// Get the proper URL
			switch($gateway['mode'])
			{
				case 'live':
					$post_url = $gateway['arb_url_live'];
				break;
				case 'test':
					$post_url = $gateway['arb_url_test'];
				break;
				case 'dev':
					$post_url = $gateway['arb_url_dev'];
				break;
			}
		}
		
		return $post_url;
	}
	
	/*
	function Auth($client_id, $order_id, $gateway, $customer, $params, $credit_card)
	{	
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",		
			"x_type"			=> "AUTH_ONLY",
			"x_method"			=> "CC",
			"x_card_num"		=> $credit_card['card_num'],
			"x_exp_date"		=> $credit_card['exp_month'].$credit_card['exp_year'],
			"x_amount"			=> $params['amount']
			);

		if(isset($credit_card->cvv)) {
			$post_values['x_card_code'] = $credit_card['cvv'];
		}	
		
		if(isset($params['customer_id'])) {
			$post_values['x_first_name'] = $customer['first_name'];
			$post_values['x_last_name'] = $customer['last_name'];
			$post_values['x_address'] = $customer['address_1'].'-'.$customer['address_2'];
			$post_values['x_state'] = $customer['state'];
			$post_values['x_zip'] = $customer['postal_code'];
		}
		
		if(isset($params['description'])) {
			$post_values['x_description'] = $params['description'];
		}
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		$CI =& get_instance();
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	function Capture($client_id, $order_id, $gateway, $customer, $params)
	{	
		$CI =& get_instance();
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_authorization_model');
		$order = $CI->order_authorization_model->GetAuthorization($order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "PRIOR_AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order->tran_id
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	
	function Refund($client_id, $order_id, $gateway, $customer, $params)
	{	
		$CI =& get_instance();
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_model');
		$order = $CI->order_model->GetCharge($client_id, $order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "CREDIT",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order['id'],
			"x_amount"			=> $params['amount'],
			"x_card_num"		=> $params['card_num'],
			"x_exp_date"		=> $params['exp_month'].$params['exp_year']
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	
	
	function Void($client_id, $order_id, $gateway, $customer, $params)
	{	
		$CI =& get_instance();
		
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		// Get the tran id
		$CI->load->model('order_model');
		$order = $CI->order_model->GetOrder($client_id, $order_id);
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "VOID",
			"x_method"			=> "CC",
			"x_tran_id"			=> $order->tran_id,
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$response = $this->Process($order_id, $post_url, $post_string);
		
		if($response['success']){
			$response_array = array('charge_id' => $order_id);
			$response = $CI->response->TransactionResponse(1, $response_array);
		} else {
			$response_array = array('reason' => $response['reason']);
			$response = $CI->response->TransactionResponse(2, $response_array);
		}
		
		return $response;
	}
	*/
	
}