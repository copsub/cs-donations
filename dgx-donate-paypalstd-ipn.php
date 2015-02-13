<?php
/**
  * Seamless Donations (Dgx-Donate) IPN Handler class
  * Copyright 2013 Allen Snook (email: allendav@allendav.com)
  * GPL2
  */

// Load WordPress   Edit RA
include "../../../wp-config.php";

// Load Seamless Donations Core
include_once "./dgx-donate.php";

class Dgx_Donate_IPN_Handler {

  var $chat_back_url  = "ssl://www.paypal.com";
  var $host_header    = "Host: www.paypal.com\r\n";
  var $post_data      = array();
  var $session_id     = '';
  var $transaction_id = '';

  public function __construct() {
    $debug_log = get_option( 'dgx_donate_log' ); // Clean up old debug log before starting
    $debug_log = '';
    update_option( 'dgx_donate_log', $debug_log );
    dgx_donate_debug_log( '----------------------------------------' );
    dgx_donate_debug_log( 'IPN processing start' );
    dgx_donate_debug_log( '----------------------------------------' );
    dgx_donate_debug_log( '- RAW IPN INFO -------------------------' );
    dgx_donate_debug_log( print_r($_POST,true) );
    dgx_donate_debug_log( '----------------------------------------' );

    // Grab all the post data
    $this->post_data = $_POST;

    // Set up for production or test
    $this->configure_for_production_or_test();

    // Extract the session and transaction IDs from the POST
    $this->get_ids_from_post();

    // Reply to Paypal to be sure that this IPN is valid
    $response = $this->reply_to_paypal();
    if ( "VERIFIED" == $response ) {
      $this->handle_verified_ipn();
    } else if ( "INVALID" == $response ) {
      $this->handle_invalid_ipn();
    } else {
      $this->handle_unrecognized_ipn( $response );
    }

    dgx_donate_debug_log( 'IPN processing complete' );
    $debug_log = get_option( 'dgx_donate_log' );
    $body = '';
    foreach ($debug_log as $debug_log_line) {
      $body .= $debug_log_line . "\n";
    }
    $mail_sent = wp_mail( get_option('dgx_donate_evernote_email'), 'Seamless donation event @CopSub_Log', $body, '' );
    dgx_donate_debug_log('Evernote Mail Status: ' . $mail_sent);
  }

  function configure_for_production_or_test() {
    if ( "SANDBOX" == get_option( 'dgx_donate_paypal_server' ) ) {
      $this->chat_back_url = "ssl://www.sandbox.paypal.com";
      $this->host_header   = "Host: www.sandbox.paypal.com\r\n";
    }
  }

  function get_ids_from_post() {
    $this->session_id = isset( $_POST[ "custom" ] ) ? $_POST[ "custom" ] : '';
    $this->transaction_id = isset( $_POST[ "txn_id" ] ) ? $_POST[ "txn_id" ] : '';
  }

  function reply_to_paypal() {
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
      $keyval = explode ('=', $keyval);
      if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
    // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
    $req = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) {
      $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
      if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
        $value = urlencode(stripslashes($value));
      } else {
        $value = urlencode($value);
      }
      $req .= "&$key=$value";
    }

    $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= $this->host_header;
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen( $req ) . "\r\n\r\n";

    $response = '';

    $fp = fsockopen( $this->chat_back_url, 443, $errno, $errstr, 30 );
    if ( $fp ) {
      fputs( $fp, $header . $req );

      $done = false;
      do {
        if ( feof( $fp ) ) {
          $done = true;
        } else {
          $response = fgets( $fp, 1024 );
          $done = in_array( $response, array( "VERIFIED", "INVALID" ) );
        }
      } while ( ! $done );
    } else {
      dgx_donate_debug_log( "IPN failed ( unable to open chatbackurl, url = {$this->chat_back_url}, errno = $errno, errstr = $errstr )" );
    }
    fclose ($fp);

    return $response;
  }

  function handle_verified_ipn() {
    $payment_status = $_POST["payment_status"];

    dgx_donate_debug_log( "IPN VERIFIED for session ID {$this->session_id}" );
    dgx_donate_debug_log( "Payment status = {$payment_status}" );

    if ( "Completed" != $payment_status ) {
      // If payment_status is not "Completed", then something must have gone wrong.
      // Send an email to check this IPN response manually
      // We exclude notifications about subscribers signing up
      if ($_POST["txn_type"] == 'subscr_signup'){
        // The user has just signed up, we do nothing
      }else if(($_POST["txn_type"] == 'subscr_eot') || ($_POST["txn_type"] == 'subscr_cancel')){
        // The user has cancelled his subscription
        $this->record_latest_ipn_status('3', $_POST["payer_email"]);
      }else if(($_POST["txn_type"] == 'subscr_failed') || ($_POST["txn_type"] == 'recurring_payment_suspended_due_to_max_failed_payment')){
        // The payment has failed, we should talk to the user
        $this->record_latest_ipn_status('2', $_POST["payer_email"]);
      }else{
        // Other cases!
        wp_mail( get_option('dgx_donate_notify_emails'), 'Seamless Donations: Please check this IPN manually, because the payment_status variable != Completed', print_r($_POST,true), '' );
        dgx_donate_debug_log('Email sent about a donation whose payment_status was not Completed');
      }
      return;
    }

    // If the payment was completed, update the user field "latest_ipn_status" to the value "0"
    $this->record_latest_ipn_status('0', $_POST["payer_email"]);

    // Check if we've already logged a transaction with this same transaction id
    $donation_id = get_donations_by_meta( '_dgx_donate_transaction_id', $this->transaction_id, 1 );

    // Save the donation into the database
    if ( 0 == count( $donation_id ) ) {
      // We haven't seen this transaction ID already

      // See if a donation for this session ID already exists
      $donation_id = get_donations_by_meta( '_dgx_donate_session_id', $this->session_id, 1 );

      if ( 0 == count( $donation_id ) ) {

        // We haven't seen this session ID already

        // Retrieve the data from transient
        $donation_form_data = get_transient( $this->session_id );

        if ( ! empty( $donation_form_data ) ) {
          // Create a donation record
          $donation_id = dgx_donate_create_donation_from_transient_data( $donation_form_data );
          dgx_donate_debug_log( "Created donation {$donation_id} from form data in transient for sessionID {$this->session_id}" );

          // Clear the transient
          delete_transient( $this->session_id );
        } else {
          // We have a session_id but no transient (the admin might have
          // deleted all previous donations in a recurring donation for
          // some reason) - so we will have to create a donation record
          // from the data supplied by PayPal

          $donation_id = dgx_donate_create_donation_from_paypal_data( $_POST );
          dgx_donate_debug_log( "Created donation {$donation_id} from PayPal data (no transient data found)" );
        }
      } else {
        // We have seen this session ID already, create a new donation record for this new transaction

        // But first, flatten the array returned by get_donations_by_meta for _dgx_donate_session_id
        $donation_id = $donation_id[0];

        $old_donation_id = $donation_id;
        $donation_id = dgx_donate_create_donation_from_donation( $old_donation_id );
        dgx_donate_debug_log( "Created donation {$donation_id} (recurring donation, donor data copied from donation {$old_donation_id}" );
      }
    } else {
      // We've seen this transaction ID already - ignore it
      $donation_id = '';
      dgx_donate_debug_log( "Transaction ID {$this->transaction_id} already handled - ignoring" );
    }

    if (!empty($donation_id)){
      // Update the raw paypal data
      update_post_meta( $donation_id, '_dgx_donate_transaction_id', $this->transaction_id );
      update_post_meta( $donation_id, '_dgx_donate_payment_processor', 'PAYPALSTD' );
      update_post_meta( $donation_id, '_dgx_donate_payment_processor_data', $this->post_data );
      // save the currency of the transaction
      $currency_code = $_POST['mc_currency'];
      dgx_donate_debug_log( "Payment currency = {$currency_code}" );
      update_post_meta( $donation_id, '_dgx_donate_donation_currency', $currency_code );

      // Call to Copenhagen Suborbitals Custom code
      $this->copsub_handle_verified_ipn($donation_id);
    }

  }

  function handle_invalid_ipn() {
    dgx_donate_debug_log( "IPN failed (INVALID) for sessionID {$this->session_id}" );
  }

  function handle_unrecognized_ipn( $paypal_response ) {
    dgx_donate_debug_log( "IPN failed (unrecognized response) for sessionID {$this->session_id}" );
    dgx_donate_debug_log( $paypal_response );
  }

  function copsub_handle_verified_ipn($donation_id){
    // Load donation data into $donation variable
    $donation = get_post_custom($donation_id);

    if(!empty($donation['_dgx_donate_repeating'][0])){
      // Handle recurring donations created in the new website
      $new_user = $this->copsub_create_member_if_necessary($donation);
      $user_id = $new_user[0];
      $user_password = $new_user[1];

    } else if (isset( $_POST[ "subscr_id" ] )){
      // Handle recurring donations coming from the old website
      $new_user = $this->copsub_create_member_if_necessary($donation);
      $user_id = $new_user[0];
      $this->copsub_handle_recurring_donation_old_website($donation_id);

    } else {
      // Handle one time donations
      dgx_donate_debug_log('One-time donation. User not created or updated ');
    }

    $this->copsub_send_email_notifications($donation_id, $user_password);
  }

  function copsub_create_member_if_necessary($donation){
    //create member info
    $member_info = array();
    foreach($donation as $key => $val){
      if(strpos($key,'_dgx_donate_donor_') === 0){
        $member_info[strtr($key,array('_dgx_donate_donor_' => ''))] =   $donation[$key][0];
      }
      if(strpos($key,'_dgx_donate_add_to_mailing_list') === 0){
        $member_info[strtr($key,array('_dgx_donate_add_to_mailing_list' => 'mailing_list'))] =   $donation[$key][0];
      }
    }
    /* Does the user already exist in the db? (check using email) */
    $existingUser = get_user_by( 'email', $member_info['email'] );

    if ( $existingUser === false ) {

      /* Setup new user profile */
      $member_info['user_login'] = $member_info['email'];
      $member_info['user_pass'] = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
      $member_info['user_email'] = $member_info['email'];
      $member_info['user_nicename'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
      $member_info['display_name'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
      $member_info['nickname'] = $member_info['first_name'] . ' ' . $member_info['last_name'];
      $member_info['role'] = 'supporter';
      $member_info['address'] = $member_info['address'] .', '. $member_info['address2'];
      if ($member_info['mailing_list'] == 'on') {
        $member_info['mailinglist'] = 'Yes';
      }

      dgx_donate_debug_log('About to create new user in the database: ' . print_r($member_info,true));

      /* If user doesn't exist, create new user */
      $countries = dgx_donate_get_countries(); // Used to convert countrycodes
      $member_info['country'] = $countries[$member_info['country']];

      $user_id = wp_insert_user( $member_info );

      // added by KB: additional fields to member
      update_user_meta( $user_id, 'user_phone',   $member_info['phone'] );
      update_user_meta( $user_id, 'user_adress',  $member_info['address'] );
      update_user_meta( $user_id, 'user_zip',     $member_info['zip'] );
      update_user_meta( $user_id, 'country', $member_info['country'] );
      update_user_meta( $user_id, 'city', $member_info['city'] );
      update_user_meta( $user_id, 'mailinglist', $member_info['mailinglist'] );

      $new_user = get_user_by( 'email', $member_info['email'] );
      dgx_donate_debug_log('New user created: ' . print_r($new_user, true));

    }else{
      /* If User exist update to supporter if only subscriber now */
      dgx_donate_debug_log('Existing user found: ' . print_r($existingUser, true));

      $user_id = $existingUser -> ID;
      $user_role = $existingUser->roles[0];

      if ( $user_role == 'subscriber') {
        $user_result = wp_update_user( array( 'ID' => $user_id, 'role' => 'supporter' ) );

        $updated_user = get_user_by( 'id' , $user_id );

        if ( is_wp_error( $user_result ) ) {
          dgx_donate_debug_log('User role update error: ' . print_r($user_result, true));
        } else {
          dgx_donate_debug_log('User role updated to supporter: ' . print_r($updated_user, true));
        }
      } else {
        dgx_donate_debug_log('User role not updated. Current role is: ' . $user_role);
      }
      $user_id = $existingUser->ID;
    }

    update_user_meta( $user_id, 'donation_method', 'Paypal' );

    return array($user_id, $member_info['user_pass']);
  }

  function copsub_handle_recurring_donation_old_website($donation_id){
    dgx_donate_debug_log('This is a recurring donation coming from the old website. Saving the subscr id in the session_id field, as a unique identifier of the subscriber:');
    dgx_donate_debug_log($_POST[ "subscr_id" ]);

    update_post_meta( $donation_id, '_dgx_donate_repeating', 1 );
    update_post_meta( $donation_id, '_dgx_donate_session_id', $_POST[ "subscr_id" ] );
  }

  function copsub_send_email_notifications($donation_id, $user_password){
  	// @todo - send different notification for recurring?
    dgx_donate_send_donation_notification( $donation_id );
    $donation_from_old_site = $one_time_donation = $first_time_recurring = false;

    if (!isset( $_POST[ "subscr_id" ] )){
      $one_time_donation = true;
    } else if (count(get_donations_by_meta( '_dgx_donate_session_id', $this->session_id, 100 )) == 1){
      $first_time_recurring = true;
    }
    if (empty( $this->session_id )){
      $donation_from_old_site = true;
    }

    dgx_donate_debug_log("Sending email notifications: some debug information:");
    dgx_donate_debug_log("  - one_time_donation: ".var_export($one_time_donation, true));
    dgx_donate_debug_log("  - first_time_recurring: ".var_export($first_time_recurring, true));
    dgx_donate_debug_log("  - donation_from_old_site: ".var_export($donation_from_old_site, true));

    // Send donor notification when:
    //   A. It's a one time donations
    //   B. It's the first recurring donation (when the user subscribes). We exclude donations coming from old website.
    if ($one_time_donation == true || $donation_from_old_site == false && $first_time_recurring == true){
      dgx_donate_send_thank_you_email( $donation_id,"",(!empty($user_password)?$user_password:"") );
    }
  }

  function record_latest_ipn_status($status, $user_email){
    $existingUser = get_user_by( 'email', $user_email );
    if ( $existingUser === false ) {
      dgx_donate_debug_log("Could not record latest IPN status, user ".$user_email." not found.");
    }else{
      update_user_meta( $existingUser->ID, 'latest_ipn_status', $status );
    }
  }
}

$dgx_donate_ipn_responder = new Dgx_Donate_IPN_Handler();

/**
  * We cannot send nothing, so send back just a simple content-type message
  */

echo "content-type: text/plain\n\n";
