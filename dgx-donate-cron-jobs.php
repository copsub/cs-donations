<?php

  // This cron jobs are based on this documentation: http://codex.wordpress.org/Function_Reference/wp_schedule_event

  // First, we setup a hook that will be triggered every month
  add_action( 'wp', 'prefix_setup_schedule' );
  function prefix_setup_schedule() {
    if ( ! wp_next_scheduled( 'seamless_donation_monthly_jobs' ) ) {
      wp_schedule_event( time(), 'monthly', 'seamless_donation_monthly_jobs');
    }
    if ( ! wp_next_scheduled( 'seamless_donation_weekly_jobs' ) ) {
      wp_schedule_event( time(), 'weekly', 'seamless_donation_weekly_jobs');
    }
  }

  // When the hooks are executed, some functions will be executed
  add_action( 'seamless_donation_monthly_jobs', 'minimum_donations_notification' );
  add_action( 'seamless_donation_weekly_jobs', 'check_incomplete_subscriptions' );


  // This function checks for supporters who have donated less than 100DKK last year
  function minimum_donations_notification() {
    global $wpdb;

    $wpdb->get_results("SET SQL_BIG_SELECTS=1;");
    $supporters_to_be_downgraded = $wpdb->get_results(
      "
      SELECT display_name, email, amount FROM (

        SELECT
          users.display_name,
          users.user_email AS email,
          # This is a small hack to convert USD to DKK (approximately)
          IFNULL(SUM(IF(m4.meta_value = 'USD', m3.meta_value*5.5, m3.meta_value)), 0) AS amount

        # We start with all users
        FROM ".$wpdb->prefix."users users

        # We load the usermeta in order to check the role
        LEFT JOIN ".$wpdb->prefix."usermeta m1 ON users.ID = m1.user_id
        # Using the email address, we find the donations made by the user
        LEFT JOIN ".$wpdb->prefix."postmeta m2 ON users.user_email = m2.meta_value
        # We only want to take into consideration donations made during the last year
        LEFT JOIN ".$wpdb->prefix."posts posts ON m2.post_id = posts.id AND posts.post_date > DATE_SUB(NOW(),INTERVAL 1 YEAR)
        # We need to load the donation amount
        LEFT JOIN ".$wpdb->prefix."postmeta m3 ON posts.id = m3.post_id AND m3.meta_key = '_dgx_donate_amount'
        # And the currency
        LEFT JOIN ".$wpdb->prefix."postmeta m4 ON posts.id = m4.post_id AND m4.meta_key = '_dgx_donate_donation_currency'
        # Join the usermeta again to check the donation method
        LEFT JOIN sbc1_usermeta m5 ON users.ID = m5.user_id AND m5.meta_key = 'donation_method'

        # Only users with role 'supporter'
        WHERE m1.meta_key = '".$wpdb->prefix."capabilities' AND m1.meta_value LIKE \"%upporter%\"

        # And exclude users who pay through the bank
        AND (m5.meta_value IS NULL || m5.meta_value != 'Bank')

        GROUP BY users.id

      ) subquery

      WHERE (amount IS NULL || amount < 100); "
    );

    $email_subject = 'Seamless Donations: List of supporters who have donated less than 100 DKK last year';

    if($wpdb->num_rows > 0){
      $table = '<table cellpadding="5" border="1"><tr><th>Name</th><th>Email</th><th>Amount donated last year</th></tr>';
      foreach($supporters_to_be_downgraded as $supporter){
        $table .= '<tr>';
        $table .= '<td>' . $supporter->display_name . '</td>';
        $table .= '<td>' . $supporter->email . '</td>';
        $table .= '<td>' . $supporter->amount . '</td>';
        $table .= '</tr>';
      }
      $table .= '</table>';

      $email_content = "<h4>This is the list of supporters who should be downgraded to subscribers because they have donated less than 100DKK during the last year</h4><br/><br/> $table";
      $headers[] = 'Content-type: text/html';

      // Send an email to the administrator with the results
      wp_mail( get_option('dgx_donate_notify_emails'), $email_subject, $email_content, $headers );

    } else {
      // Nothing to do. No supporters have been found who have donated less than 100 DKK last year
      wp_mail( get_option('dgx_donate_notify_emails'), $email_subject, "No supporters have been found who have donated less than 100 DKK last year" );
    }

  }

  // This function checks for users who have tried to subscribe in Paypal but have not completed the subscription
  function check_incomplete_subscriptions() {
    error_log("Pending check_incomplete_subscriptions");
  }
?>