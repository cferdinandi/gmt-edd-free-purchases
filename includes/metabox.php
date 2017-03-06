<?php

	/**
	 * Create the metabox
	 */
	function gmt_edd_free_create_metabox() {
		add_meta_box( 'gmt_edd_free_metabox', 'Free Purchase', 'gmt_edd_free_render_metabox', 'download', 'normal', 'default');
	}
	add_action( 'add_meta_boxes', 'gmt_edd_free_create_metabox' );



	/**
	 * Create the metabox default values
	 */
	function gmt_edd_free_metabox_defaults() {
		return array(
			'enabled' => 'off',
			'honeypot' => 'off',
			'alert_bad_email' => 'Please use a valid email address.',
			'alert_failed' => 'Well this is embarrassing... something went wrong. Please try again.',
			'alert_success' => 'Congrats! An email has been sent to you with your purchase details.',
		);
	}



	/**
	 * Render the metabox
	 */
	function gmt_edd_free_render_metabox() {

		// Variables
		global $post;
		$saved = get_post_meta( $post->ID, 'gmt_edd_free_details', true );
		$defaults = gmt_edd_free_metabox_defaults();
		$details = wp_parse_args( $saved, $defaults );

		?>

			<fieldset>

				<div>
					<label for="gmt_edd_free_shortcode"><?php _e( 'Shortcode', 'gmt_edd_free' ); ?></label>
					<input type="text" class="large-text" id="gmt_edd_free_shortcode" name="gmt_edd_free_shortcode" value="<?php echo esc_attr( '[edd_free_purchase id="' . $post->ID . '" label="Get It Now" placeholder=""]' ); ?>" readonly="readonly">
				</div>
				<br>

				<div>
					<label>
						<input type="checkbox" id="gmt_edd_free_enabled" name="gmt_edd_free[enabled]" value="on" <?php checked( $details['enabled'], 'on' ); ?>>
						<?php _e( 'Allow people to purchase this for free with just their email address.', 'gmt_edd_free' ); ?>
					</label>
				</div>
				<br>

				<div>
					<label>
						<input type="checkbox" id="gmt_edd_free_honeypot" name="gmt_edd_free[honeypot]" value="on" <?php checked( $details['honeypot'], 'on' ); ?>>
						<?php _e( 'Include a honeypot (you will need to add CSS to hide it).', 'gmt_edd_free' ); ?>
					</label>
				</div>
				<br>

				<div>
					<label for="gmt_edd_free_alert_bad_email"><?php _e( 'Alert: Bad Email', 'gmt_edd_free' ); ?></label>
					<input type="text" class="large-text" id="gmt_edd_free_alert_bad_email" name="gmt_edd_free[alert_bad_email]" value="<?php echo esc_attr( $details['alert_bad_email'] ); ?>">
				</div>
				<br>

				<div>
					<label for="gmt_edd_free_alert_failed"><?php _e( 'Alert: Failed', 'gmt_edd_free' ); ?></label>
					<input type="text" class="large-text" id="gmt_edd_free_alert_failed" name="gmt_edd_free[alert_failed]" value="<?php echo esc_attr( $details['alert_failed'] ); ?>">
				</div>
				<br>

				<div>
					<label for="gmt_edd_free_alert_success"><?php _e( 'Alert: Success', 'gmt_edd_free' ); ?></label>
					<input type="text" class="large-text" id="gmt_edd_free_alert_success" name="gmt_edd_free[alert_success]" value="<?php echo esc_attr( $details['alert_success'] ); ?>">
				</div>
				<br>

			</fieldset>

		<?php

		// Security field
		wp_nonce_field( 'gmt_edd_free_form_metabox_nonce', 'gmt_edd_free_form_metabox_process' );

	}



	/**
	 * Save the metabox
	 * @param  Number $post_id The post ID
	 * @param  Array  $post    The post data
	 */
	function gmt_edd_free_save_metabox( $post_id, $post ) {

		if ( !isset( $_POST['gmt_edd_free_form_metabox_process'] ) ) return;

		// Verify data came from edit screen
		if ( !wp_verify_nonce( $_POST['gmt_edd_free_form_metabox_process'], 'gmt_edd_free_form_metabox_nonce' ) ) {
			return $post->ID;
		}

		// Verify user has permission to edit post
		if ( !current_user_can( 'edit_post', $post->ID )) {
			return $post->ID;
		}

		// Check that events details are being passed along
		if ( !isset( $_POST['gmt_edd_free'] ) ) {
			return $post->ID;
		}

		// Sanitize all data
		$sanitized = array();
		foreach ( $_POST['gmt_edd_free'] as $key => $detail ) {
			$sanitized[$key] = wp_filter_post_kses( $detail );
		}

		// Update data in database
		update_post_meta( $post->ID, 'gmt_edd_free_details', $sanitized );

	}
	add_action('save_post', 'gmt_edd_free_save_metabox', 1, 2);