<?php

/* Copyright 2013 Allen Snook (email: allendav@allendav.com) */

class Dgx_Donate_Admin_Donor_Detail_View {
	static function show( $donor_id ) {
		echo "<div class='wrap'>\n";
		echo "<div id='icon-edit-pages' class='icon32'></div>\n";
		echo "<h2>" . esc_html__( 'Donor Detail', 'dgx-donate' ) . "</h2>\n";
	
		$donor_email = strtolower( $donor_id );
	
		$args = array(
			'numberposts'     => '-1',
			'post_type'       => 'dgx-donation',
			'meta_key'		  => '_dgx_donate_donor_email',
			'meta_value'	  => $donor_email,
			'order'           => 'ASC'
	); 

		$my_donations = get_posts( $args );
	
		$args = array(
			'numberposts'     => '1',
			'post_type'       => 'dgx-donation',
			'meta_key'		  => '_dgx_donate_donor_email',
			'meta_value'	  => $donor_email,
			'order'           => 'DESC'
		); 	
	
		$last_donation = get_posts( $args );
	
		if ( count( $my_donations ) < 1 ) {
			echo "<p>" . esc_html__( 'No donations found.', 'dgx-donate' ) . "</p>";
		} else {
			echo "<div id='col-container'>\n";
			echo "<div id='col-right'>\n";
			echo "<div class='col-wrap'>\n";
	
			echo "<h3>" . esc_html__( 'Donations by This Donor', 'dgx-donate' ) . "</h3>\n";
			echo "<table class='widefat'><tbody>\n";
			echo "<tr>";
			echo "<th>" . esc_html__( 'Date', 'dgx-donate' ) . "</th>";
			echo "<th>" . esc_html__( 'Fund', 'dgx-donate' ) . "</th>";
			echo "<th>" . esc_html__( 'Amount', 'dgx-donate' ) . "</th>";
			echo "</tr>\n";
			
			$donor_total = 0;
			
			foreach ( (array) $my_donations as $my_donation ) {
				$donation_id = $my_donation->ID;
			
				$year = get_post_meta( $donation_id, '_dgx_donate_year', true );
				$month = get_post_meta( $donation_id, '_dgx_donate_month', true );
				$day = get_post_meta( $donation_id, '_dgx_donate_day', true );
				$time = get_post_meta( $donation_id, '_dgx_donate_time', true );
				$fund_name = __( 'Undesignated', 'dgx-donate' );
				$designated = get_post_meta( $donation_id, '_dgx_donate_designated', true );
				if ( ! empty( $designated ) ) {
					$fund_name = get_post_meta( $donation_id, '_dgx_donate_designated_fund', true );
				}
				$amount = get_post_meta( $donation_id, '_dgx_donate_amount', true );
				$donor_total = $donor_total + floatval( $amount );
				$formatted_amount = "$" . number_format( $amount, 2 );

				$donation_detail = dgx_donate_get_donation_detail_link( $donation_id );
				echo "<tr><td><a href='" . esc_url( $donation_detail ) . "'>" . esc_html( $year . "-" . $month . "- " . $day . " " . $time ) . "</a></td>";
				echo "<td>" . esc_html( $fund_name ) . "</td>";
				echo "<td>" . esc_html( $formatted_amount ) . "</td>";
				echo "</tr>\n";
			}
			$formatted_donor_total = "$" . number_format( $donor_total, 2 );
			echo "<tr>";
			echo "<th>&nbsp</th><th>" . esc_html__( 'Donor Total', 'dgx-donate' ) . "</th>";
			echo "<td>" . esc_html( $formatted_donor_total ) . "</td></tr>\n";
		
			echo "</tbody></table>\n";

			do_action( 'dgx_donate_donor_detail_right', $donor_id );
			do_action( 'dgx_donate_admin_footer' );
	
			echo "</div> <!-- col-wrap -->\n";
			echo "</div> <!-- col-right -->\n";
	
			echo "<div id=\"col-left\">\n";
			echo "<div class=\"col-wrap\">\n";
	
			$donation_id = $last_donation[0]->ID;
	
			self::echo_donor_information( $donation_id );

			do_action( 'dgx_donate_donor_detail_left', $donor_id );
	
			echo "</div> <!-- col-wrap -->\n";
			echo "</div> <!-- col-left -->\n";
			echo "</div> <!-- col-container -->\n";
		}
	
		echo "</div> <!-- wrap -->\n"; 
	}

	static function echo_donor_information( $donation_id ) {
		$first_name = get_post_meta( $donation_id, '_dgx_donate_donor_first_name', true );
		$last_name = get_post_meta( $donation_id, '_dgx_donate_donor_last_name', true );
		$company = get_post_meta( $donation_id, '_dgx_donate_donor_company_name', true );
		$address1 = get_post_meta( $donation_id, '_dgx_donate_donor_address', true );
		$address2 = get_post_meta( $donation_id, '_dgx_donate_donor_address2', true );
		$city = get_post_meta( $donation_id, '_dgx_donate_donor_city', true );
		$state =  get_post_meta( $donation_id, '_dgx_donate_donor_state', true );
		$zip = get_post_meta( $donation_id, '_dgx_donate_donor_zip', true );
		$phone =  get_post_meta( $donation_id, '_dgx_donate_donor_phone', true );
		$email = get_post_meta( $donation_id, '_dgx_donate_donor_email', true );
	
		echo "<h3>" . esc_html__( 'Donor Information', 'dgx-donate' ) . "</h3>\n";
		echo "<table class='widefat'><tbody>\n";
		echo "<tr>";
		echo "<td>" . esc_html( $first_name . " " . $last_name ) . "<br/>";
		if ( ! empty( $company ) ) {
			echo esc_html( $company ) . "<br/>";
		}
		if ( ! empty( $address1 ) ) {
			echo esc_html( $address1 ) . "<br/>";
		}
		if ( ! empty( $address2 ) ) {
			echo esc_html( $address2 ) . "<br/>";;
		}
		if ( ! empty( $city ) ) {
			echo esc_html( $city . " " . $state . " " . $zip ) . "<br/>";
		}
		if ( ! empty( $phone ) ) {
			echo esc_html( $phone ) . "<br/>";
		}
		if ( ! empty( $email ) ) {
			echo esc_html( $email );
		}
		echo "</td>";
		echo "</tr>";
		echo "</tbody></table>\n";
	}
}
