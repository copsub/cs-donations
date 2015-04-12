<?php

/* PayPal Website Payments Standard Module for Seamless Donations */
/* Copyright 2013 Allen Snook (email: allendav@allendav.com) */

define('DGXDONATEPAYPALSTD', 'DGXDONATEPAYPALSTD');

/******************************************************************************************************/
function dgx_donate_paypalstd_init()
{
	// The showing and saving of settings uses actions
	add_action('dgx_donate_show_settings_forms','dgx_donate_show_paypalstd_settings_form');
	add_action('dgx_donate_save_settings_forms','dgx_donate_save_paypalstd_settings_form');

	// The donation form content uses a filter since it must return the form to the caller
	add_filter('dgx_donate_donation_form','dgx_donate_show_paypalstd_donation_form');
}

add_action('init', 'dgx_donate_paypalstd_init');

/******************************************************************************************************/
function dgx_donate_paypalstd_enqueue_scripts() {
	$load_in_footer = ( 'true' == get_option( 'dgx_donate_scripts_in_footer' ) );
	wp_enqueue_script( 'jquery' );
	$script_url = plugins_url( '/js/paypalstd-script.js', __FILE__ ); 
	wp_enqueue_script( 'dgx_donate_paypalstd_script', $script_url, array( 'jquery' ), false, $load_in_footer );
}
add_action( 'wp_enqueue_scripts', 'dgx_donate_paypalstd_enqueue_scripts' );

/******************************************************************************************************/
function dgx_donate_show_paypalstd_settings_form()
{
	// First, show our radio button
	$paymentGateway = get_option('dgx_donate_payment_gateway');
	if ($paymentGateway == DGXDONATEPAYPALSTD)
	{
		$checked = "checked";
	}
	else
	{
		$checked = "";
	}
	
	echo "<p class=\"dgxdonategatewayname\"><input type=\"radio\" name=\"paymentgateway\" value=\"";
	echo DGXDONATEPAYPALSTD;
	echo "\" $checked /> <b>PayPal Standard</b></p>";

	// Now show our form content
	$checkSandbox = "";
	$checkLive = "";
	$payPalServer = get_option('dgx_donate_paypal_server');
	if (strcasecmp($payPalServer, "SANDBOX") == 0)
	{
		$checkSandbox = "checked";
	}
	else
	{
		$checkLive = "checked";
	}

	$payPalEmail = get_option('dgx_donate_paypal_email');

	echo "<div class=\"form-field\">\n";
	echo "<label for='paypalemail'>" . esc_html__( 'PayPal Email Address', 'dgx-donate' ) . "</label><br/>\n";
	echo "<input type=\"text\" name=\"paypalemail\" value=\"$payPalEmail\" />\n";
	echo "<p class='description'>" . esc_html__( 'The email address at which to receive payments.', 'dgx-donate' ) . "</p>\n";
	echo "</div> <!-- form-field --> \n";

	echo "<p>" . esc_html__( 'Mode:', 'dgx-donate' ) . " \n";
	echo "<input type='radio' name='paypalserver' value='SANDBOX' $checkSandbox /> ". esc_html__( 'Sandbox (Test Server)', 'dgx-donate' ) . " ";
	echo "<input type='radio' name='paypalserver' value='LIVE' $checkLive /> " . esc_html__( 'Live (Production Server)', 'dgx-donate' ) . "</p>";
	echo "<p>" . __( 'IPN URL', 'dgx-donate' ) . "<p>";
	$notify_url = plugins_url( '/dgx-donate-paypalstd-ipn.php', __FILE__ );
	echo "<pre>$notify_url</pre>";
}

/******************************************************************************************************/
function dgx_donate_save_paypalstd_settings_form()
{
	$paymentGateway = "";
	$payPalServer = "";
	$payPalEmail = "";

	if ( isset( $_POST['paymentgateway'] ) ) {
	    $paymentGateway = $_POST['paymentgateway'];
	}	
	if ( isset( $_POST['paypalserver'] ) ) {
	    $payPalServer = $_POST['paypalserver'];
	}
	if ( isset( $_POST['paypalemail'] ) ) {
	    $payPalEmail = $_POST['paypalemail'];
	}

    // If they set the paymentGateway, record the setting
    // It is OK for all gateways to do this (so at least one does)
    if (!empty($paymentGateway))
    {
    	update_option('dgx_donate_payment_gateway', $paymentGateway);
    }

	// If they set the paypalemail, record the setting
	if ( ! empty( $payPalEmail ) )
	{
		$payPalEmail = trim( $payPalEmail );
		if ( is_email( $payPalEmail ) ) {
			update_option( 'dgx_donate_paypal_email', $payPalEmail );
		}
	}

    // If they set the paypal server type (sandbox or live), record the setting
    if (!empty($payPalServer))
    {
    	update_option('dgx_donate_paypal_server', $payPalServer);
    }
}

/******************************************************************************************************/
function dgx_donate_show_paypalstd_donation_form($content) //RAEDIT
{
	// If we are the actively selected gateway
	$paymentGateway = get_option('dgx_donate_payment_gateway');
	if ($paymentGateway == DGXDONATEPAYPALSTD)
	{
		// Open the form
		$content .= "<form id=\"dgx-donate-form\" method=\"post\" onsubmit=\"return DgxDonateDoCheckout();\" >";
	
		// Save the session ID as a hidden input
		$session_id = 'dgxdonate_' . substr( session_id(), 0, 10 ) . '_' . time();
		$content .= "<input type=\"hidden\" name=\"_dgx_donate_session_id\" value=\"$session_id\" />";

		// Start the outermost container
		$content .= "<div id=\"dgx-donate-container\">\n";

		// Pick and choose the built in sections this gateway supports
		$content = dgx_donate_paypalstd_warning_section($content);
		$content = dgx_donate_get_donation_section($content);

		$show_tribute_section = get_option( 'dgx_donate_show_tribute_section' );
		if ( "true" == $show_tribute_section ) {
			$content = dgx_donate_get_tribute_section($content);
		}

		$show_employer_section = get_option( 'dgx_donate_show_employer_section' );
		if ( "true" == $show_employer_section ) {
			$content = dgx_donate_get_employer_section( $content );
		}

		$content = dgx_donate_get_donor_section($content);
		//$content = dgx_donate_get_billing_section($content);
		$content = dgx_donate_paypalstd_payment_section($content);

		// Close the outermost container
		$content .= "</div>\n";

		// Close the form
		$content .= "</form>\n";

		$content .= dgx_donate_paypalstd_get_hidden_form();
	}

	return $content;
}

/******************************************************************************************************/
function dgx_donate_paypalstd_get_current_url()
{
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
	{
		$http = 'https';
	}
	else
	{
		$http = 'http';
	}

	$currentUrl = $http . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	return $currentUrl;
}

/******************************************************************************************************/
function dgx_donate_paypalstd_get_hidden_form()
{
	$paypalEmail = get_option('dgx_donate_paypal_email');

	$payPalServer = get_option('dgx_donate_paypal_server');
	if ($payPalServer == "SANDBOX")
	{
		$formAction = "https://www.sandbox.paypal.com/cgi-bin/webscr";
	}
	else
	{
		$formAction = "https://www.paypal.com/cgi-bin/webscr";
	}

	$notifyUrl = plugins_url('/dgx-donate-paypalstd-ipn.php', __FILE__);

	$sessionID = session_id();
	$successUrl = dgx_donate_paypalstd_get_current_url();

	if (strpos($successUrl, "?") === false)
	{
		$successUrl .= "?";
	}
	else
	{
		$successUrl .= "&";
	}

	$successUrl .= "thanks=1&sessionid=";
	$successUrl .= "$sessionID";

	$currency_code = get_option( 'dgx_donate_currency' );

	$item_name = apply_filters( 'dgx_donate_item_name', __( 'Donation', 'dgx-donate' ) );

	$output = "";
	$output .= "<form id='dgx-donate-hidden-form' action='" . esc_attr( $formAction ) . "' method='post' target='_top' >";
	$output .= "<input type='hidden' name='cmd' value='_donations' />";
	$output .= "<input type='hidden' name='business' value='" . esc_attr( $paypalEmail ) . "' />";
	$output .= "<input type='hidden' name='return' value='" . esc_attr( $successUrl ) ."' />";

	$output .= "<input type='hidden' name='first_name' value='' /> ";
	$output .= "<input type='hidden' name='last_name' value='' />";
	$output .= "<input type='hidden' name='address1' value='' />";
	$output .= "<input type='hidden' name='address2' value='' />";
	$output .= "<input type='hidden' name='city' value='' />";

	$output .= "<input type='hidden' name='state' value='' />"; // removed if country not US or Canada
	$output .= "<input type='hidden' name='zip' value='' />";
	$output .= "<input type='hidden' name='country' value='' />";
	$output .= "<input type='hidden' name='email' value='' />";
	
	$output .= "<input type='hidden' name='custom' value='' />";
	$output .= "<input type='hidden' name='notify_url' value='" . esc_attr( $notifyUrl ) . "' />";

	$output .= "<input type='hidden' name='item_name' value='" . esc_attr( $item_name ) . "' />";
	$output .= "<input type='hidden' name='amount' value='1.00' />";
	$output .= "<input type='hidden' name='quantity' value='1' />";

	$output .= "<input type='hidden' name='currency_code' value='" . esc_attr( $currency_code ) . "' />";

	$output .= "<input type='hidden' name='no_note' value='1' />";

	$output .= "<input type='hidden' name='src' value='1' />"; // removed when not repeating
	$output .= "<input type='hidden' name='p3' value='1' />";  // removed when not repeating
	$output .= "<input type='hidden' name='t3' value='1' />";  // removed when not repeating
	$output .= "<input type='hidden' name='a3' value='1' />";  // removed when not repeating

	$output .= "</form>";

	return $output;
}

/******************************************************************************************************/
function dgx_donate_paypalstd_warning_section($formContent)
{
	// Display any setup warnings we need to display here (e.g. running in test mode)
	$payPalServer = get_option('dgx_donate_paypal_server');
	if ($payPalServer == "SANDBOX")
	{
		$formContent .= "<div class='dgx-donate-form-section' id='dgx-donate-form-sandbox-warning'>";
		$formContent .= "<p>";
		$formContent .= esc_html__( "Warning - Seamless Donations is currently configured to use the Sandbox (Test Server).", "dgx-donate" );
		$formContent .= "</p>";
		$formContent .= "</div>";
	}

	// Echo a NOSCRIPT warning
	$formContent .= "<noscript>";
	$formContent .= "<div class='dgx-donate-form-section' id='dgx-donate-form-noscript-warning'>";
	$formContent .= "<p>" . esc_html__( "Warning:  To make a donation, you must first enable JavaScript.", "dgx-donate" ) . "</p>";
	$formContent .= "</div>";
	$formContent .= "</noscript>";

	return $formContent;
}

/******************************************************************************************************/
function dgx_donate_paypalstd_payment_section( $form_content ) {
	// Show the button that kicks it all off

	$processing_image_url = plugins_url( '/images/ajax-loader.gif', __FILE__ );
	$button_image_url = plugins_url( '/images/paypal_btn_donate_lg.gif', __FILE__ );
	$disabled_button_image_url = plugins_url( '/images/paypal_btn_donate_lg_disabled.gif', __FILE__ );

	$section = "<div class='dgx-donate-form-section' id='dgx-donate-form-payment-section'>"
		. "<p>"
		. "<input class='dgx-donate-pay-enabled' type='image' src='" . esc_url( $button_image_url ) . "' value='" . esc_attr__( 'Donate Now', 'dgx-donate' ) . "'/>"
		. "<img class='dgx-donate-pay-disabled' src='" . esc_url( $disabled_button_image_url ) . "' />"
		. "<img class='dgx-donate-busy' src='" . esc_url( $processing_image_url ) . "' />"
		. "</p>"
		. "<p class='dgx-donate-error-msg'></p>"
		. "</div>\n";

	$form_content .= $section;

	return $form_content;
}

/******************************************************************************************************/
function dgx_donate_paypalstd_detail()
{
	echo "<p>TODO: dgx_donate_paypalstd_detail</p>";
}

/******************************************************************************************************/


function dgx_donate_bank_transfer(){
	$postData = prepare_post_data_for_bank_transfer();

  // Save the donation into the Seamless Donations table
	$donation_id = dgx_donate_create_donation_from_transient_data( $postData );
	delete_transient( $postData['SESSIONID'] );

  // Send a welcome email
	$email_subject = 'Bank Transfer instructions for Copenhagen Suborbitals Donation';

	// We only create a user for users making recurring donations
	if($postData['REPEATING'] == '1'){
		$email_template = 'template_bank_donation_email_recurring.html';
		$email_content = file_get_contents(plugin_dir_path(__FILE__).$email_template);
		$user = find_or_create_user($postData);
  	$userdata = get_userdata( $user->ID );
		$email_content = str_replace("%email%", $user->user_email, $email_content);
		$email_content = str_replace("%username%", $user->user_login, $email_content);
		$email_content = str_replace("%paymentid%", "SUPPORT".$user->ID, $email_content);
	}else{
		$email_template = 'template_bank_donation_email_one_time.html';
		$email_content = file_get_contents(plugin_dir_path(__FILE__).$email_template);
		$email_content = str_replace("%email%", $postData['EMAIL'], $email_content);
		$email_content = str_replace("%paymentid%", "DONATION".$donation_id, $email_content);
	}

	$headers[] = 'Content-type: text/html';

  wp_mail( $postData['EMAIL'], $email_subject, $email_content, $headers );

  wp_mail( get_option('dgx_donate_notify_emails'), 'Copenhagen Suborbitals: A user registered for bank donation', $email_content, $headers );

	wp_die('Emails sent');
}


add_action('wp_ajax_dgx_donate_bank_transfer', 'dgx_donate_bank_transfer');
add_action('wp_ajax_nopriv_dgx_donate_bank_transfer', 'dgx_donate_bank_transfer');



function prepare_post_data_for_bank_transfer(){
	$postData = array();
	$postData['AMOUNT'] = $_POST['amount'];
	$postData['CURRENCY_CODE'] = $_POST['currency'];
	$postData['REPEATING'] = ($_POST['monthly'] == 'true') ? '1' : '0';
	$postData['LASTNAME'] = $_POST['email'];
	$postData['EMAIL'] = $_POST['email'];
	$postData['PAYMENTMETHOD'] = 'bank';

	return $postData;
}



# private key file to use
$MY_KEY_FILE = "/home/web/paypal_certificates/cs-prvkey.pem";

# public certificate file to use
$MY_CERT_FILE = "/home/web/paypal_certificates/cs-pubcert.pem";

# Paypal's public certificate
$PAYPAL_CERT_FILE = "/home/web/paypal_certificates/paypal_cert.pem";

# path to the openssl binary
$OPENSSL = "/usr/bin/openssl";

function dgx_donate_paypal(){
	if ($_POST['monthly'] == 'true'){
		$cmd = '_xclick-subscriptions';
		$item_name = 'Copenhagen Suborbitals Monthly Donation';
	}else{
		$cmd = '_donations';
		$item_name = 'Copenhagen Suborbitals Single Donation';
	}
	$paypal_options = array(
		'cmd' => $cmd,
    'business' => 'donate@copsub.com',
    'cert_id' => 'BD3LE43A5BWPJ',
    'lc' => 'US',
    'invoice' => '',
    'currency_code' => $_POST['currency'],
    'no_shipping' => '1',
    'item_name' => $item_name,
    'item_number' => '1',
		'amount' => $_POST['amount'],
		'a3' => $_POST['amount'],
		'p3' => '1',
		't3' => 'M',
		'src' => '1',
		'return' => 'http://copenhagensuborbitals.com/ext/fbtab/fbthankyou.php',
		'cancel_return' => 'http://copenhagensuborbitals.com/support-us/'
	);

	wp_die(paypal_encrypt($paypal_options));
}

function paypal_encrypt($hash)
{
	//Sample PayPal Button Encryption: Copyright 2006-2010 StellarWebSolutions.com
	//Not for resale - license agreement at
	//http://www.stellarwebsolutions.com/en/eula.php
	global $MY_KEY_FILE;
	global $MY_CERT_FILE;
	global $PAYPAL_CERT_FILE;
	global $OPENSSL;


	if (!file_exists($MY_KEY_FILE)) {
		echo "ERROR: MY_KEY_FILE $MY_KEY_FILE not found\n";
	}
	if (!file_exists($MY_CERT_FILE)) {
		echo "ERROR: MY_CERT_FILE $MY_CERT_FILE not found\n";
	}
	if (!file_exists($PAYPAL_CERT_FILE)) {
		echo "ERROR: PAYPAL_CERT_FILE $PAYPAL_CERT_FILE not found\n";
	}


	//Assign Build Notation for PayPal Support
	$hash['bn']= 'StellarWebSolutions.PHP_EWP2';

	$data = "";
	foreach ($hash as $key => $value) {
		if ($value != "") {
			//echo "Adding to blob: $key=$value\n";
			$data .= "$key=$value\n";
		}
	}

	$openssl_cmd = "($OPENSSL smime -sign -signer $MY_CERT_FILE -inkey $MY_KEY_FILE " .
						"-outform der -nodetach -binary <<_EOF_\n$data\n_EOF_\n) | " .
						"$OPENSSL smime -encrypt -des3 -binary -outform pem $PAYPAL_CERT_FILE";

	exec($openssl_cmd, $output, $error);

	if (!$error) {
		return implode("\n",$output);
	} else {
		return "ERROR: encryption failed";
	}
};


add_action('wp_ajax_dgx_donate_paypal', 'dgx_donate_paypal');
add_action('wp_ajax_nopriv_dgx_donate_paypal', 'dgx_donate_paypal');













function generate_post_data(){
	$referringUrl = $_POST['referringUrl'];
	$sessionID = $_POST['sessionID'];
	$donationAmount = $_POST['donationAmount'];
	$userAmount = $_POST['userAmount'];
	$repeating = $_POST['repeating'];
	$designated = $_POST['designated'];
	$designatedFund = $_POST['designatedFund'];
	$tributeGift = $_POST['tributeGift'];
	$memorialGift = $_POST['memorialGift'];
	$honoreeName = $_POST['honoreeName'];
	$honorByEmail = $_POST['honorByEmail'];
	$honoreeEmail = $_POST['honoreeEmail'];
	$honoreeAddress = $_POST['honoreeAddress'];
	$honoreeCity = $_POST['honoreeCity'];
	$honoreeState = $_POST['honoreeState'];
	$honoreeProvince = $_POST['honoreeProvince'];
	$honoreeCountry = $_POST['honoreeCountry'];
	$paymentMethod = $_POST['paymentMethod'];

	if ( 'US' == $honoreeCountry ) {
		$honoreeProvince = '';
	} else if ( 'CA' == $honoreeCountry ) {
		$honoreeState = '';
	} else {
		$honoreeState = '';
		$honoreeProvince = '';
	}

	$honoreeZip = $_POST['honoreeZip'];
	$honoreeEmailName = $_POST['honoreeEmailName'];
	$honoreePostName = $_POST['honoreePostName'];
	$firstName = $_POST['firstName'];
	$lastName = $_POST['lastName'];
	$phone = $_POST['phone'];
	$email = $_POST['email'];
	$addToMailingList = $_POST['addToMailingList'];
	$address = $_POST['address'];
	$address2 = $_POST['address2'];
	$city = $_POST['city'];
	$state = $_POST['state'];
	$province = $_POST['province'];
	$country = $_POST['country'];

	if ( 'US' == $country ) {
		$province = '';
	} else if ( 'CA' == $country ) {
		$state = '';
	} else {
		$state = '';
		$province = '';
	}

	$zip = $_POST['zip'];
	$increaseToCover = $_POST['increaseToCover'];
	$anonymous = $_POST['anonymous'];
	$employerMatch = $_POST['employerMatch'];
	$employerName = $_POST['employerName'];
	$ukGiftAid = $_POST['ukGiftAid'];

	// Resolve the donation amount
	if (strcasecmp($donationAmount, "OTHER") == 0)
	{
		$amount = floatval($userAmount);
	}
	else
	{
		$amount = floatval($donationAmount);
	}
	if ($amount < 1.00)
	{
		$amount = 1.00;
	}
	
	// Repack the POST
	$postData = array();
	$postData['REFERRINGURL'] = $referringUrl;
	$postData['SESSIONID'] = $sessionID;
	$postData['AMOUNT'] = $amount;
	$postData['REPEATING'] = $repeating;
	$postData['DESIGNATED'] = $designated;
	$postData['DESIGNATEDFUND'] = $designatedFund;
	$postData['TRIBUTEGIFT'] = $tributeGift;
	$postData['MEMORIALGIFT'] = $memorialGift;
	$postData['HONOREENAME'] = $honoreeName;
	$postData['HONORBYEMAIL'] = $honorByEmail;
	$postData['HONOREEEMAIL'] = $honoreeEmail;
	$postData['HONOREEADDRESS'] = $honoreeAddress;
	$postData['HONOREECITY'] = $honoreeCity;
	$postData['HONOREESTATE'] = $honoreeState;
	$postData['HONOREEPROVINCE'] = $honoreeProvince;
	$postData['HONOREECOUNTRY'] = $honoreeCountry;
	$postData['HONOREEZIP'] = $honoreeZip;
	$postData['HONOREEEMAILNAME'] = $honoreeEmailName;
	$postData['HONOREEPOSTNAME'] = $honoreePostName;
	$postData['FIRSTNAME'] = $firstName;
	$postData['LASTNAME'] = $lastName;
	$postData['PHONE'] = $phone;
	$postData['EMAIL'] = $email;
	$postData['ADDTOMAILINGLIST'] = $addToMailingList;
	$postData['ADDRESS'] = $address;
	$postData['ADDRESS2'] = $address2;
	$postData['CITY'] = $city;
	$postData['STATE'] = $state;
	$postData['PROVINCE'] = $province;
	$postData['COUNTRY'] = $country;
	$postData['ZIP'] = $zip;
	$postData['INCREASETOCOVER'] = $increaseToCover;
	$postData['ANONYMOUS'] = $anonymous;
	$postData['PAYMENTMETHOD'] = $paymentMethod;
	$postData['EMPLOYERMATCH'] = $employerMatch;
	$postData['EMPLOYERNAME'] = $employerName;
	$postData['UKGIFTAID'] = $ukGiftAid;
	
	// Sanitize the data (remove leading, trailing spaces quotes, brackets)
	foreach ($postData as $key => $value)
	{
		$temp = trim($value);
		$temp = str_replace("\"", "", $temp);
		$temp = strip_tags($temp);
		$postData[$key] = $temp;
	}
	return $postData;
}




function find_or_create_user($postData){
	$user = get_user_by( 'email', $postData['EMAIL'] );

	if ( $user === false ) {
		// Prepare user data
		$member_info = array();
		$member_info['user_login'] = $postData['EMAIL'];
		$member_info['user_pass'] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
		$member_info['user_email'] = $postData['EMAIL'];
		$member_info['first_name'] = $postData['FIRSTNAME'];
		$member_info['last_name'] = $postData['LASTNAME'];
		$member_info['user_nicename'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
		$member_info['display_name'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
		$member_info['nickname'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
		$member_info['role'] = 'supporter';
		$member_info['address'] = $postData['ADDRESS'] .', '. $postData['ADDRESS2'];
		if ($postData['ADDTOMAILINGLIST'] == 'on') {
			$member_info['mailinglist'] = 'Yes';
		}
		$countries = dgx_donate_get_countries(); // Used to convert countrycodes
		$member_info['country'] = $countries[$postData['COUNTRY']];

		// Create user in DB
		$user_id = wp_insert_user( $member_info );

		// Save some additional fields along with the user
		update_user_meta( $user_id, 'user_phone', $postData['PHONE'] );
		update_user_meta( $user_id, 'user_adress', $member_info['address'] );
		update_user_meta( $user_id, 'user_zip', $postData['ZIP'] );
		update_user_meta( $user_id, 'country', $member_info['country'] );
		update_user_meta( $user_id, 'city', $postData['CITY'] );
		update_user_meta( $user_id, 'mailinglist', $member_info['mailinglist'] );
		update_user_meta( $user_id, 'donation_method', 'Bank' );

		$user = get_user_by( 'email', $postData['EMAIL'] );
	}

	return $user;
}