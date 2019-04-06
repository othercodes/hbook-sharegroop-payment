<?php

class HbSharegroop extends HbPaymentGateway {
	private $sharegroop;

	function __construct() {
		$this->hbdb = new HbDataBaseActions();
		$this->utils = new HbUtils( $this->hbdb, null );
		$this->version = '1.0';
		$this->id = 'sharegroop';
		$this->name = esc_html__( 'E-Transactions', 'hb-sharegroop-admin' );
		$this->has_redirection = 'yes';
		$this->payment_methods = array(
			'sharegroop' => esc_html__( 'Credit Agricole Logo', 'hb-sharegroop-admin' ),
			'cb' => esc_html__( 'CB', 'hb-sharegroop-admin' ),
			'visa' => esc_html__( 'Visa', 'hb-sharegroop-admin' ),
			'mastercard' => esc_html__( 'Mastercard', 'hb-sharegroop-admin' ),
			'ecartebleue'=> esc_html__( 'E-Carte Bleue', 'hb-sharegroop-admin' ),
			'maestro' => esc_html__( 'Discover', 'hb-sharegroop-admin' ),
			'americanexpress' => esc_html__( 'American Express', 'hb-sharegroop-admin' ),
			'diners' => esc_html__( 'Diners', 'hb-sharegroop-admin' ),
			'jcb' => esc_html__( 'JCB', 'hb-sharegroop-admin' ),
			'cofinoga' => esc_html__( 'Cofinoga', 'hb-sharegroop-admin' ),
			'aurore' => esc_html__( 'Aurore', 'hb-sharegroop-admin' ),
		);

		if ( get_option( 'sharegroop_hbook_activated' ) ) {
			delete_option( 'sharegroop_hbook_activated' );
			$this->insert_plugin_strings();
		}

		add_filter( 'hbook_payment_gateways', array( $this, 'add_sharegroop_gateway_class' ) );
		add_filter( 'hb_strings', array( $this, 'add_plugin_strings' ) );
	}

	public function add_sharegroop_gateway_class( $hbook_gateways ) {
		$hbook_gateways[] = $this;
		return $hbook_gateways;
	}

	public function get_strings_section() {
		return array(
			'title' => esc_html__( 'E-Transactions payment', 'hb-sharegroop-admin' ),
			'strings' => array(
				'sharegroop_payment_method_label' => esc_html__( 'Payment method label', 'hb-sharegroop-admin' ),
				'sharegroop_payment_method_description' => esc_html__( 'Payment method description', 'hb-sharegroop-admin' ),
				'sharegroop_bottom_text_line_1' => esc_html__( 'Text at the bottom of the form - line 1: ', 'hb-sharegroop-admin' ),
				'sharegroop_bottom_text_line_2' => esc_html__( 'Text at the bottom of the form - line 2: ', 'hb-sharegroop-admin' ),
			)
		);
	}

	public function get_strings_value() {
		return array(
			'sharegroop_payment_method_label' => esc_html__( 'E-Transactions, pay with your favorite method', 'hb-sharegroop-admin' ),
			'sharegroop_payment_method_description' => esc_html__( 'We will redirect you to the E-Transactions banking platform where you can pay with your favorite method.', 'hb-sharegroop-admin' ),
			'sharegroop_bottom_text_line_1' => esc_html__( 'Powered by ', 'hb-sharegroop-admin' ),
			'sharegroop_bottom_text_line_2' => esc_html__( 'We accept all major credit cards', 'hb-sharegroop-admin' ),
		);
	}

	public function get_payment_method_label() {
		$payment_method_label = $this->hbdb->get_string( 'sharegroop_payment_method_label' );
		$payment_method_icons = json_decode( get_option( 'hb_sharegroop_icons' ) );
		if ( ! is_array( $payment_method_icons ) ) {
			$payment_method_icons = array();
		}
		$output = $payment_method_label;
		foreach ( $payment_method_icons as $icon_id ) {
			if( $icon_id != 'sharegroop' ) {
				$output .= ' ';
				$output .= '<img ';
				$output .= 'src="' . plugin_dir_url( __FILE__ ) . 'img/' . $icon_id . '.png" ';
				$output .= 'alt="' . $this->payment_methods[ $icon_id ] . '" ';
				$output .= 'title="' . $this->payment_methods[ $icon_id ] . '" ';
				$output .=  '/>';
			}
		}
		return apply_filters( 'hb_sharegroop_payment_method_label', $output, $payment_method_label, $payment_method_icons );
	}

	public function admin_fields() {
		return array(
			'label' => esc_html__( 'E-Transactions settings', 'hb-sharegroop-admin' ),
			'options' => array(
				'hb_sharegroop_mode' => array(
					'label' => esc_html__( 'E-Transactions mode:', 'hb-sharegroop-admin' ),
					'type' => 'radio',
					'choice' => array(
						'live' => esc_html__( 'Live', 'hb-sharegroop-admin' ),
						'test' => esc_html__( 'Test', 'hb-sharegroop-admin' ),
					),
					'default' => 'live'
				),
				'hb_sharegroop_site_id' => array(
					'label' => esc_html__( 'Merchant Site Number:', 'hb-sharegroop-admin' ),
					'type' => 'text',
				),
				'hb_sharegroop_rang' => array(
					'label' => esc_html__( 'Merchant Rang Number:', 'hb-sharegroop-admin' ),
					'type' => 'text',
				),
				'hb_sharegroop_identifiant' => array(
					'label' => esc_html__( 'Merchant E-Transactions Id:', 'hb-sharegroop-admin' ),
					'type' => 'text',
				),
				'hb_sharegroop_hmac_live' => array(
					'label' => esc_html__( 'HMAC Key Live:', 'hb-sharegroop-admin' ),
					'type' => 'text',
					'wrapper-class' => 'hb-sharegroop-mode-live'
				),
				'hb_sharegroop_hmac_test' => array(
					'label' => esc_html__( 'HMAC Key Test:', 'hb-sharegroop-admin' ),
					'type' => 'text',
					'wrapper-class' => 'hb-sharegroop-mode-test'
				),
				'hb_sharegroop_icons' => array(
					'label' => esc_html__( 'Displayed icons:', 'hb-sharegroop-admin' ),
					'type' => 'checkbox',
					'choice' => $this->payment_methods,
					'default' => '[]'
				),
			)
		);
	}

	public function js_scripts() {
		return array(
			array(
				'id' => 'hb-sharegroop',
				'url' => plugin_dir_url( __FILE__ ) . 'js/sharegroop.js',
				'version' => $this->version
			),
		);
	}

	public function admin_js_scripts() {
		return array(
			array(
				'id' => 'hb-sharegroop-admin',
				'url' => plugin_dir_url( __FILE__ ) . 'js/sharegroop-admin.js',
				'version' => $this->version
			),
		);
	}

	public function js_data() {
		switch ( get_option( 'hb_sharegroop_mode' ) ) {
			case 'test':
				$sharegroop_url = 'https://preprod-tpeweb.e-transactions.fr/cgi/MYchoix_pagepaiement.cgi';
				break;

			case 'live':
				$sharegroop_url = $this->availaible_paybox_server();
				break;
		}
		return array(
			'hb_sharegroop_url' => $sharegroop_url,
		);
	}

	public function bottom_area() {
		$txt_1 = $this->hbdb->get_string( 'sharegroop_bottom_text_line_1' );
		$txt_2 = $this->hbdb->get_string( 'sharegroop_bottom_text_line_2' );
		$output = '';
		$icons = json_decode( get_option( 'hb_sharegroop_icons' ) );
		if ( ! is_array( $icons ) ) {
			$icons = array();
		}
		if ( $txt_1 && in_array( 'sharegroop', $icons ) ) {
			$output .= '<i><small>';
			$output .= '<span>' . $txt_1 . ' &nbsp;</span>';
			$output .= '<img ';
			$output .= 'src="' . plugin_dir_url( __FILE__ ) . 'img/sharegroop.png" ';
			$output .= 'alt="" />';
			$output .= '</small></i>';
		}
		if ( $txt_2 ) {
			if ( $output ) {
				$output .= '<br/>';
			}
			$output .= '<i><small>';
			$output .= '<span>' . $txt_2 . ' &nbsp;</span>';
			foreach ( $icons as $icon_id ) {
				if ( $icon_id != 'sharegroop' ) {
					$output .= ' ';
					$output .= '<img ';
					$output .= 'src="' . plugin_dir_url( __FILE__ ) . 'img/' . $icon_id . '.png" ';
					$output .= 'alt="' . $this->payment_methods[ $icon_id ] . '" ';
					$output .= 'title="' . $this->payment_methods[ $icon_id ] . '" ';
					$output .=  '/>';
				}
			}
		}
		$output .= '</small></i>';

		return apply_filters( 'hb_sharegroop_bottom_area', $output );
	}

	public function payment_form() {
		$payment_desc = $this->hbdb->get_string( 'sharegroop_payment_method_description' );
		if ( $payment_desc ) {
			$payment_desc = '<p>' . $payment_desc . '</p>';
		}
		return $payment_desc;
	}

	public function process_payment( $resa_info, $customer_info, $amount_to_pay ) {
		$parameters_to_remove = array( 'token' );
		$return_urls = $this->get_return_urls( $parameters_to_remove );
		$token = substr( bin2hex( openssl_random_pseudo_bytes( 64 ) ), -20 );
		$data_sharegroop = $this->data_for_sharegroop( $amount_to_pay, $return_urls, $token, $resa_info, $customer_info );
		return array( 'success' => true, 'payment_token' => $token, 'return_url' => $return_urls['payment_confirm'], 'data_sharegroop' => $data_sharegroop );
	}

	public function get_payment_token() {
		return $_GET['token'];
	}

	private function get_merchant_settings() {
		$merchant_settings = array(
			'site_id' => get_option( 'hb_sharegroop_site_id' ),
			'rang' => get_option( 'hb_sharegroop_rang' ),
			'identifiant' => get_option( 'hb_sharegroop_identifiant' )
		);
		if ( get_option( 'hb_sharegroop_mode' ) == 'test' ) {
			$merchant_settings['hmac'] = get_option( 'hb_sharegroop_hmac_test' );
		} else {
			$merchant_settings['hmac'] = get_option( 'hb_sharegroop_hmac_live' );
		}

		return $merchant_settings;
	}

	private function availaible_paybox_server() {
		$serveurs = array( 'tpeweb.paybox.com', 'tpeweb1.paybox.com' );
		$serveurOK = '';
		foreach( $serveurs as $serveur ) {
			$doc = new DOMDocument();
			$doc->loadHTMLFile( 'https://'.$serveur.'/load.html' );
			$server_status = '';
			$element = $doc->getElementById( 'server_status' );
			if( $element ) {
				$server_status = $element->textContent;
				if( $server_status == 'OK' ) {
					$serveurOK = $serveur;
				break;
				}
			}
		}

		if( ! $serveurOK ) {
			die( esc_html__( 'Error : no server could be found', 'hb-sharegroop-admin' ) );
		}

		return( 'https://'.$serveurOK.'/cgi/MYchoix_pagepaiement.cgi' );

	}

	private function data_for_sharegroop( $amount_to_pay, $return_urls, $token, $resa_info, $customer_info ) {
		$merchant_settings = $this->get_merchant_settings();
		$order_desc = $token . '-' . $this->get_external_payment_desc( $resa_info, $customer_info );
		$lang = get_locale();
		$available_lang = array (
			'fr_FR' => 'FRA',
			'en_US' => 'GBR',
			'es_ES' => 'ESP',
			'it_IT' => 'ITA',
			'de_DE' => 'DEU',
			'nl_NL' => 'NLD',
			'sv_SE' => 'SWE',
			'pt_PT' => 'PRT'
		);

		if ( array_key_exists( $lang, $available_lang ) ) {
			$sharegroop_lang = $available_lang[ $lang ];
		} else {
			$sharegroop_lang = 'FRA';
		}

		$data_sharegroop = array(
			'PBX_SITE' => $merchant_settings['site_id'],
			'PBX_RANG' => $merchant_settings['rang'],
			'PBX_IDENTIFIANT' => $merchant_settings['identifiant'],
			'PBX_TOTAL' => $amount_to_pay * 100,
			'PBX_DEVISE' => '978',
			'PBX_CMD' => $order_desc,
			'PBX_PORTEUR' => $customer_info['email'],
			'PXB_LANGUE' => $sharegroop_lang,
			'PBX_RETOUR' => 'Mt:M;Ref:R;Result:E',
			'PBX_EFFECTUE' => $return_urls['payment_confirm'].'&token='.$token,
			'PBX_ANNULE' => $return_urls['payment_cancel'] . '&token=' . $token,
			'PBX_REFUSE' => $return_urls['payment_confirm'].'&token='.$token,
			'PBX_HASH' => 'SHA512',
			'PBX_TIME' => date('c'),
		);

		// We build the encoded msg
		$msg = '';
		$i = 1;
		$data_count = count( $data_sharegroop );

		foreach ( $data_sharegroop as $key => $value ) {
			$msg .= $key . '=' . $value;
			if ( $i != $data_count ) {
				$msg .= '&';
				$i++;
			}
		}

		$binKey = pack( "H*", $merchant_settings['hmac'] ); // If ASCII key, we modify to binary
		$hmac = strtoupper( hash_hmac( 'sha512', $msg, $binKey));
		$data_sharegroop['PBX_HMAC'] = $hmac;

		return $data_sharegroop;
	}

	public function confirm_payment() {
		$resa = $this->hbdb->get_resa_by_payment_token( $_GET['token'] );
		if ( ! $resa ) {
			$response = array(
				'success' => false,
				'error_msg' => $this->hbdb->get_string( 'timeout_error' )
			);
		} else {
			$status ='';
			$error_msg = '';

			if ( isset ( $_GET['Result'] ) ) {
				$status = $_GET['Result'];

			}
			switch ( $status ) {
				case '00000' :
					$response = array(
						'success' => true,
					);
				break;

				case '99999' :
					$response = array(
						'success' => true,
						'payment_status' => 'Pending',
						'payment_status_reason' => esc_html__( 'You have chosen a payment method that needs further confirmation of payment.')
					);
				break;

				default :
					$response = array(
						'success' => false,
						'error_msg' => sprintf( esc_html__( 'No charge has been done as an error occured on Credit Agricole with your payment. Please try again and if the problem occurs again, contact us with this error code: %s. We will do our best to assist you with your reservation.', 'hb-sharegroop-admin' ), $status ),
					);
				break;
			}
		}

		if ( $response['success'] ) {
			$resa_id = $this->hbdb->update_resa_after_payment( $_GET['token'], '', '', $resa['amount_to_pay'] );
			if ( ! $resa_id ) {
				$response = array(
					'success' => false,
					'error_msg' => 'Error (could not update reservation).'
				);
			} else {
				$this->utils->send_email( 'new_resa', $resa_id );
			}
		}

		return $response;
	}

}
