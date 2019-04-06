<?php
/*
* Plugin Name: Sharegroop HBook
* Plugin URI: https://hotelwp.com/hbook/
* Description: Add payment method "Sharegroop" to Hbook.
* Version: 1.0
* Author: HotelWP
* Author URI: https://hotelwp.com/
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SharegroopHBook {

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'plugin_activated' ) );
		add_action( 'plugins_loaded', array( $this, 'init_sharegroop' ) );
	}

	public function plugin_activated() {
		update_option( 'sharegroop_hbook_activated', '1' );
	}

	public function init_etransactions() {
		load_plugin_textdomain( 'hb-sharegroop-admin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		if ( class_exists( 'HbPaymentGateway' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'sharegroop.php';
			$sharegroop = new HbSharegroop();
		} else {
			add_action( 'admin_notices', array( $this, 'no_hbook_notice' ) );
		}
	}

	public function no_hbook_notice() {
	?>
		<div class="updated">
			<p><?php esc_html_e( 'Sharegroop plugin requires HBook plugin to work properly.', 'hb-sharegroop-admin' ); ?></p>
		</div>
	<?php
	}

}

$sharegroop_hbook = new SharegroopHBook();