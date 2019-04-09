<?php
/*
* Plugin Name: Sharegroop HBook
* Plugin URI: https://hotelwp.com/hbook/
* Description: Add payment method "Sharegroop" to Hbook.
* Version: 1.0
* Author: HotelWP
* Author URI: https://hotelwp.com/
*/

if (!defined('WPINC')) {
    die;
}

/**
 * Class SharegroopHBook
 * @package otherCodes/HBook/Payments/ShareGroop
 */
class SharegroopHBook
{
    /**
     * SharegroopHBook constructor.
     * @return void
     */
    public function __construct()
    {
        register_activation_hook(__FILE__, [$this, 'plugin_activated']);
        add_action('plugins_loaded', [$this, 'init_sharegroop']);
    }

    /**
     * Set the plugin as activated
     * @return void
     */
    public function plugin_activated()
    {
        update_option('sharegroop_hbook_activated', '1');
    }

    /**
     * Function to be called on plugin activation.
     * Initialize the ShareGroop class.
     * @return void
     */
    public function init_sharegroop()
    {
        /** @TODO generate correct po files. */
        load_plugin_textdomain(
            'hb-sharegroop-admin',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );

        if (class_exists('HbPaymentGateway')) {
            require_once plugin_dir_path(__FILE__) . 'sharegroop.php';

            $hbookDB = new HbDataBaseActions();
            $utils = new HbUtils($hbookDB, null);

            new HbSharegroop($hbookDB, $utils);

        } else {
            add_action('admin_notices', [$this, 'no_hbook_notice']);
        }
    }

    /**
     * Prints the Require HBook message if needed.
     * @return void
     */
    public function no_hbook_notice()
    {
        ?>
        <div class="updated">
            <p><?php esc_html_e(
                    'Sharegroop plugin requires HBook plugin to work properly.',
                    'hb-sharegroop-admin'
                ); ?></p>
        </div>
        <?php
    }

}

/**
 * Main execution of the Hbook plugin connector
 */
new SharegroopHBook();