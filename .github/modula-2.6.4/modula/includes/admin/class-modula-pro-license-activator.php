<?php

class Modula_Pro_License_Activator {

	private $main_item_name = 'Modula Grid Gallery';
	private $verify_alternative_server;
	function __construct() {

		register_activation_hook( MODULA_PRO_FILE, array( $this, 'activate_license' ) );
		register_deactivation_hook( MODULA_PRO_FILE, array( $this, 'deactivate_license' ) );

		add_action( 'admin_init', array( $this, 'register_license_option' ) );
		add_action( 'wp_ajax_modula_save_license', array( $this, 'activate_license_ajax' ) );
		add_action( 'wp_ajax_modula_deactivate_license', array( $this, 'deactivate_license_ajax' ) ); 

	}

	public function activate_license_ajax() {

		check_ajax_referer( 'modula_license_save', 'license_security' );

		if ( !isset( $_POST['license'] ) || '' == $_POST['license'] ){
			wp_send_json_error( __( 'No license was found', 'modula-pro' ) );
			die();
		}

		// retrieve the license from the AJAX
		$license      = trim( $_POST['license'] );
		$license_data = false;


		$this->verify_alternative_server = $_POST['altServer'];

		update_option( 'modula_pro_alernative_server', $this->verify_alternative_server );

		$store_url = ( 'true' ==  $this->verify_alternative_server ) ? MODULA_PRO_ALTERNATIVE_STORE_URL : MODULA_PRO_STORE_URL;

		// data to send in our API request
		$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => MODULA_PRO_STORE_ITEM_ID,
				'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
				$store_url,
				array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params,
				)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ){
			if ( is_wp_error( $response ) ){
				$message = $response->get_error_message();
			} else {
				$message = esc_html__( 'An error occurred, please try again.', 'modula-pro' );
			}
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ){
				switch ( $license_data->error ){
					case 'expired':
						$message = sprintf(
								esc_html__( 'Your license key expired on %s.', 'modula-pro' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'disabled':
					case 'revoked':
						$message = esc_html__( 'Your license key has been disabled.', 'modula-pro' );
						break;
					case 'missing':
						$message = esc_html__( 'Invalid license.', 'modula-pro' );
						break;
					case 'invalid':
					case 'site_inactive':
						$message = esc_html__( 'Your license is not active for this URL.', 'modula-pro' );
						break;
					case 'item_name_mismatch':
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'modula-pro' ), $this->main_item_name );
						break;
					case 'no_activations_left':
						$message = esc_html__( 'Your license key has reached its activation limit.', 'modula-pro' );
						break;
					default:
						$message = esc_html__( 'An error occurred, please try again.', 'modula-pro' );
						break;
				}
			}
		}

		// Check if anything passed on a message constituting a failure
		if ( !empty( $message ) ){
			wp_send_json_error( $message );
			die();
		}

		// Let's see if is update or just activate again the same license
		$license = $this->sanitize_license( $license );

		// $license_data->license will be either "valid" or "invalid"
		update_option( 'modula_pro_license_key', $license );
		update_option( 'modula_pro_license_status', $license_data );
		do_action('modula_after_license_save');
		wp_send_json( esc_html__('License activated','modula-pro') );
		die();
	}

	public function deactivate_license_ajax(){

		check_ajax_referer( 'modula_license_save', 'license_security' );

		// retrieve the license from the database
		$license = trim( get_option( 'modula_pro_license_key' ) );

		$this->verify_alternative_server = get_option( 'modula_pro_alernative_server' );

		$store_url = ( 'true' == $this->verify_alternative_server ) ? MODULA_PRO_ALTERNATIVE_STORE_URL : MODULA_PRO_STORE_URL;
		// data to send in our API request
		$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_id'    => MODULA_PRO_STORE_ITEM_ID,
				'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
				$store_url,
				array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params,
				)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ){

			if ( is_wp_error( $response ) ){
				$message = $response->get_error_message();
			} else {
				$message = esc_html__( 'An error occurred, please try again.', 'modula-pro' );
			}

			wp_send_json_error( $message );
			die();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "deactivated" or "failed"
		//if ( $license_data->license == 'deactivated' ){
			delete_option( 'modula_pro_license_status' );
		//}

		do_action('modula_after_license_deactivated');

		wp_send_json( esc_html__('License deactivated','modula-pro') );
		die();
	}

	public function register_license_option() {
		// creates our settings in the options table
		register_setting( 'modula_pro_license_key', 'modula_pro_license_key', array( $this, 'sanitize_license' ) );
	}

	public function sanitize_license( $new ) {
		$old = get_option( 'modula_pro_license_key' );
		if ( $old && $old != $new ) {
			delete_option( 'modula_pro_license_status' ); // new license has been entered, so must reactivate
			delete_transient( 'modula_pro_licensed_extensions' );
		}
		return $new;
	}

	public function activate_license() {

		check_admin_referer( 'activate-plugin_' . plugin_basename( MODULA_PRO_FILE ) );

		$license = trim( get_option( 'modula_pro_license_key' ) );
		$license_data = false;

		$store_url = ( 'true' ==  get_option( 'modula_pro_alernative_server' ) ) ? MODULA_PRO_ALTERNATIVE_STORE_URL : MODULA_PRO_STORE_URL;

		// data to send in our API request
		$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => MODULA_PRO_STORE_ITEM_ID,
				'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
				$store_url,
				array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params,
				)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ){
			return false;
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ){
				return false;
			}
		}

		// Check if anything passed on a message constituting a failure
		if ( !empty( $message ) ){
			return false;
		}

		// Let's see if is update or just activate again the same license
		$license = $this->sanitize_license( $license );

		update_option( 'modula_pro_license_status', $license_data );
		do_action('modula_after_license_save');

		return true;
	}

	public function deactivate_license(){

		check_admin_referer( 'deactivate-plugin_' . plugin_basename( MODULA_PRO_FILE ) );

		// retrieve the license from the database
		$license = trim( get_option( 'modula_pro_license_key' ) );

		$store_url = ( 'true' == get_option( 'modula_pro_alernative_server' ) ) ? MODULA_PRO_ALTERNATIVE_STORE_URL : MODULA_PRO_STORE_URL;
	
		// data to send in our API request
		$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_id'    => MODULA_PRO_STORE_ITEM_ID,
				'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_post(
				$store_url,
				array(
						'timeout'   => 15,
						'sslverify' => false,
						'body'      => $api_params,
				)
		);

		delete_option( 'modula_pro_license_status' );

		do_action('modula_after_license_deactivated');

	}

}

new Modula_Pro_License_Activator();
