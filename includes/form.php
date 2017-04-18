<?php

	/**
	 * MailChimp form shortcode
	 * @return string Shortcode markup
	 */
	function gmt_edd_free_form( $atts ) {

		// Get shortcode atts
		$gmt_edd_free = shortcode_atts( array(
			'id' => null,
			'label' => 'Get It Now',
			'placeholder' => '',
			'honeypot' => 'off',
		), $atts );

		// Prevent this content from caching
		define('DONOTCACHEPAGE', TRUE);

		// Options
		$saved = get_post_meta( $gmt_edd_free['id'], 'gmt_edd_free_details', true );
		$defaults = gmt_edd_free_metabox_defaults();
		$details = wp_parse_args( $saved, $defaults );

		if ( $details['enabled'] !== 'on' ) return;

		// Status
		$status = gmt_edd_free_get_session( 'gmt_edd_free_status', true );
		$success = gmt_edd_free_get_session( 'gmt_edd_free_success', true );
		$email = gmt_edd_free_get_session( 'gmt_edd_free_email', true );
		if ( is_user_logged_in() && empty( $email ) ) {
			$current_user = wp_get_current_user();
			$email = $current_user->user_email;
		}

		// Make sure ID is provided
		if ( is_null( $gmt_edd_free['id'] ) || $gmt_edd_free['id'] === '' ) return;

		// Honeypot
		$tarpit = $details['honeypot'] === 'on' ? '<div class="row gmt-edd-free-tarpit"><div class="grid-third"><label for="gmt_edd_free_email_confirm">If you are human, leave this blank</label></div><div class="grid-two-thirds"><input type="text" id="gmt_edd_free_email_confirm" name="gmt_edd_free_email_confirm" value="" autofill="off"></div></div>' : '';

		if ( $success ) {
			return '<p id="gmt-edd-free-form-' . esc_attr( $gmt_edd_free['id'] ) . '"><em>' . stripslashes( $status ) . '</em></p>';
		}

		return
			'<form class="gmt-edd-free-form" id="gmt-edd-free-form-' . esc_attr( $gmt_edd_free['id'] ) . '" name="gmt_edd_free_form" action="" method="post">' .
				'<input type="hidden" name="gmt_edd_free_id" value="' . esc_attr( $gmt_edd_free['id'] ) . '">' .
				'<input type="hidden" id="gmt_edd_free_tarpit_time" name="gmt_edd_free_tarpit_time" value="' . esc_attr( current_time( 'timestamp' ) ) . '">' .
				$tarpit .
				wp_nonce_field( 'gmt_edd_free_form_nonce', 'gmt_edd_free_form_process', true, false ) .
				'<label class="gmt-edd-free-label" for="gmt_edd_free_email">' . __( 'Email Address', 'gmt_edd_free' ) . '</label>' .
				'<div class="row">' .
					'<div class="grid-two-thirds">' .
						'<input type="email" id="gmt_edd_free_email" name="gmt_edd_free_email" value="' . esc_attr( $email ) . '" placeholder="' . esc_attr( $gmt_edd_free['placeholder'] ) . '" required>' .
					'</div>' .
					'<div class="grid-third">' .
						'<button class="gmt-edd-free-btn">' . $gmt_edd_free['label'] . '</button>' .
					'</div>' .
				'</div>' .
				( empty( $status ) ? '' : '<p><em>' . esc_html( stripslashes( $status ) ) . '</em></p>' ) .
			'</form>';

	}
	add_shortcode( 'edd_free_purchase', 'gmt_edd_free_form' );



	/**
	 * Complete purchase on form submit
	 */
	function gmt_edd_free_complete_purchase() {

		// Check that form was submitted
		if ( !isset( $_POST['gmt_edd_free_form_process'] ) ) return;

		// Verify data came from proper screen
		if ( !wp_verify_nonce( $_POST['gmt_edd_free_form_process'], 'gmt_edd_free_form_nonce' ) ) {
			die( 'Security check' );
		}

		// Variables
		$saved = get_post_meta( $_POST['gmt_edd_free_id'], 'gmt_edd_free_details', true );
		$defaults = gmt_edd_free_metabox_defaults();
		$details = wp_parse_args( $saved, $defaults );
		$referrer = gmt_edd_free_get_url();
		$status = $referrer . '#gmt-edd-free-form-' . $_POST['gmt_edd_free_id'];
		$email = filter_var( $_POST['gmt_edd_free_email'], FILTER_VALIDATE_EMAIL );

		// Make sure form has an ID
		if ( !isset( $_POST['gmt_edd_free_id'] ) ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// Sanity check
		if ( empty( $_POST['gmt_edd_free_email'] ) ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// Empty field honeypot
		if ( isset( $_POST['gmt_edd_free_email_confirm'] ) && !empty( $_POST['gmt_edd_free_email_confirm'] )  ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// Timestamp honeypot
		if ( !isset( $_POST['gmt_edd_free_tarpit_time'] ) || current_time( 'timestamp' ) - $_POST['gmt_edd_free_tarpit_time'] < 1 ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// If form is disabled
		if ( $details['enabled'] !== 'on' ) {
			gmt_edd_free_set_session( 'gmt_edd_free_status', $details['alert_failed'], 'post' );
			wp_safe_redirect( $status, 302 );
			exit;
		}

		// If email is invalid
		if ( empty( $email ) ) {
			gmt_edd_free_set_session( 'gmt_edd_free_status', $details['alert_bad_email'], 'post' );
			gmt_edd_free_set_session( 'gmt_edd_free_email', $_POST['gmt_edd_free_email'], 'post' );
			wp_safe_redirect( $status, 302 );
			exit;
		}

		// Complete purchase
		$payment = new EDD_Payment();
		$payment->add_download( $_POST['gmt_edd_free_id'] );
		$payment->email = $_POST['gmt_edd_free_email'];
		$payment->status = 'pending';
		$payment->save();
		$payment->status = 'complete';
		$payment->save();

		// die( keel_print_a($payment) );

		// If purchase fails
		if ( empty( $payment->ID ) ) {
			gmt_edd_free_set_session( 'gmt_edd_free_status', $details['alert_failed'], 'post' );
			gmt_edd_free_set_session( 'gmt_edd_free_email', $_POST['gmt_edd_free_email'], 'post' );
			wp_safe_redirect( $status, 302 );
			exit;
		}

		// If purchase success
		gmt_edd_free_set_session( 'gmt_edd_free_status', $details['alert_success'], 'post' );
		gmt_edd_free_set_session( 'gmt_edd_free_success', true );
		wp_safe_redirect( $status, 302 );
		exit;

	}
	add_action( 'init', 'gmt_edd_free_complete_purchase' );