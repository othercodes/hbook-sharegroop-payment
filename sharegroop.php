<?php

/**
 * Class HbSharegroop
 * @copyright otherCode 2019
 * @author Unay Santisteban <usantisteban@othercode.es>
 */
class HbSharegroop extends HbPaymentGateway
{
    /**
     * Plugin ID
     * @var string
     */
    public $id = 'sharegroop';

    /**
     * Plugin version string
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Plugin name
     * @var string
     */
    public $name = 'ShareGroop';

    /**
     * This plugin redirects to another site?
     * @var string
     */
    public $has_redirection = 'no';

    /**
     * Lis of available payment methods
     * @var array
     */
    public $payment_methods;

    /**
     * HBook ShareGroop Payment constructor.
     * @param HbDataBaseActions $hbookdb
     * @param HbUtils $utils
     */
    public function __construct(HbDataBaseActions $hbookdb, HbUtils $utils)
    {
        $this->hbdb = $hbookdb;
        $this->utils = $utils;
        $this->name = esc_html__('ShareGroop', 'hb-sharegroop-admin');
        $this->payment_methods = [
            'visa' => esc_html__('Visa', 'hb-etransactions-admin'),
            'mastercard' => esc_html__('Mastercard', 'hb-etransactions-admin'),
            'americanexpress' => esc_html__('American Express', 'hb-etransactions-admin'),
        ];

        if (get_option('sharegroop_hbook_activated') == 1) {
            delete_option('sharegroop_hbook_activated');

            /**
             * directly "activating" the plugin inserting this two parameter
             * in the options table, little hack to by pass the lines 53 and
             * 60 in hbook/admin-pages/payment/payment.php
             */
            update_option('hb_' . $this->id . '_valid_purchase_code', 'yes');
            update_option('hb_' . $this->id . '_active', 'yes');

            $this->insert_plugin_strings();
        }

        add_filter('hbook_payment_gateways', [$this, 'add_sharegroop_gateway_class']);
        add_filter('hb_strings', [$this, 'add_plugin_strings']);

        /**
         * Register all "hb_sharegroop_ajax_" functions as ajax action
         * in wordpress dynamically.
         */
        array_map(function ($function) {
            if (strpos($function, 'hb_sharegroop_ajax_') === 0) {
                add_action('wp_ajax_' . $function, [$this, $function]);
                add_action('wp_ajax_nopriv_' . $function, [$this, $function]);
            }

            return $function;
        }, get_class_methods(get_class($this)));
    }

    /**
     * Append the current gateway to the list
     * @param array $gateways
     * @return array
     */
    public function add_sharegroop_gateway_class($gateways)
    {
        return array_merge($gateways, [$this]);
    }

    /**************************************************************
     *                  Environment and Frontend
     **************************************************************/

    /**
     * Return id string to been inserted in the XX_hb_strings table.
     * @return array
     */
    public function get_strings_section()
    {
        return [
            'title' => esc_html__('Sharegroop payment', 'hb-sharegroop-admin'),
            'strings' => [
                'sharegroop_payment_method_label' => esc_html__(
                    'Payment method label',
                    'hb-sharegroop-admin'
                ),
                'sharegroop_payment_method_description' => esc_html__(
                    'Payment method description',
                    'hb-sharegroop-admin'
                ),
                'sharegroop_bottom_text_line_1' => esc_html__(
                    'Text at the bottom of the form - line 1: ',
                    'hb-sharegroop-admin'
                ),
                'sharegroop_wait_confirmation_msg' => esc_html__(
                    'Wait confirmation message',
                    'hb-sharegroop-admin'
                )
            ]
        ];
    }

    /**
     * Return the value of the strings to been inserted
     * in XX_hb_strings table.
     * @return array
     */
    public function get_strings_value()
    {
        return [
            'sharegroop_payment_method_label' => esc_html__(
                'ShareGroop, group payments.',
                'hb-sharegroop-admin'
            ),
            'sharegroop_payment_method_description' => esc_html__(
                'Group payment will be started, shared payment link will allow your group to pay.',
                'hb-sharegroop-admin'
            ),
            'sharegroop_bottom_text_line_1' => esc_html__(
                'We accept all major credit cards',
                'hb-sharegroop-admin'
            ),
            'sharegroop_wait_confirmation_msg' => esc_html__(
                'Waiting Sharegroop confirmation...',
                'hb-sharegroop-admin'
            )
        ];
    }

    /**
     * Insert payment strings into database.
     * @return void
     */
    public function insert_plugin_strings()
    {
        global $wpdb;

        foreach ($this->get_strings_value() as $string_id => $string_value) {
            if (!$this->hbdb->get_string($string_id)) {
                $wpdb->query("INSERT INTO {$this->hbdb->strings_table} (id, locale, value) 
                                    VALUES ('$string_id', 'en_US', '$string_value')");
            } else {
                $wpdb->query("UPDATE {$this->hbdb->strings_table} 
                                    SET `value` = '$string_value'
                                    WHERE id = '$string_id'");
            }
        }
    }

    /**
     * Display the payment method label.
     * @return mixed|string|void
     */
    public function get_payment_method_label()
    {
        $payment_method_label = $this->hbdb->get_string('sharegroop_payment_method_label');
        $payment_method_icons = json_decode(get_option('hb_sharegroop_icons'));
        $output = $payment_method_label;

        $output .= $this->get_payment_method_icons();

        return apply_filters(
            'hb_sharegroop_payment_method_label',
            $output,
            $payment_method_label,
            $payment_method_icons
        );
    }

    /**
     * Load the admin fields
     * @return array
     */
    public function admin_fields()
    {
        return [
            'label' => esc_html__('ShareGroop settings', 'hb-sharegroop-admin'),
            'options' => [
                'hb_sharegroop_mode' => [
                    'label' => esc_html__('ShareGroop mode:', 'hb-sharegroop-admin'),
                    'type' => 'radio',
                    'choice' => [
                        'live' => esc_html__('Live', 'hb-sharegroop-admin'),
                        'test' => esc_html__('Test', 'hb-sharegroop-admin'),
                    ],
                    'default' => 'live'
                ],
                'hb_sharegroop_icons' => [
                    'label' => esc_html__('Displayed icons:', 'hb-sharegroop-admin'),
                    'type' => 'checkbox',
                    'choice' => $this->payment_methods,
                    'default' => '[]'
                ],
                'hb_sharegroop_live_secret_key' => [
                    'label' => esc_html__('Live Secret Key:', 'hb-sharegroop-admin'),
                    'type' => 'text',
                    'wrapper-class' => 'hb-sharegroop-mode-live'
                ],
                'hb_sharegroop_live_public_key' => [
                    'label' => esc_html__('Live Public Key:', 'hb-sharegroop-admin'),
                    'type' => 'text',
                    'wrapper-class' => 'hb-sharegroop-mode-live'
                ],
                'hb_sharegroop_test_secret_key' => [
                    'label' => esc_html__('Test Secret Key:', 'hb-sharegroop-admin'),
                    'type' => 'text',
                    'wrapper-class' => 'hb-sharegroop-mode-test'
                ],
                'hb_sharegroop_test_public_key' => [
                    'label' => esc_html__('Test Public Key:', 'hb-sharegroop-admin'),
                    'type' => 'text',
                    'wrapper-class' => 'hb-sharegroop-mode-test'
                ],
            ]
        ];
    }

    /**
     * Load the admin js scripts
     * @return array
     */
    public function admin_js_scripts()
    {
        return [
            [
                'id' => 'hb-sharegroop-admin',
                'url' => plugin_dir_url(__FILE__) . 'js/sharegroop-admin.js',
                'version' => $this->version
            ],
        ];
    }

    /**
     * Load the front end js scripts
     * @return array
     */
    public function js_scripts()
    {
        return [
            [
                'id' => 'hbook-sharegroop',
                'url' => plugin_dir_url(__FILE__) . 'js/sharegroop.js',
                'version' => $this->version
            ],
        ];
    }

    /**
     * Return js variables
     * @return array
     */
    public function js_data()
    {
        $accomodations = [];
        foreach ($this->hbdb->get_all_accom() as $accom_id => $accom_name) {
            $accomodations[] = [
                'id' => $accom_id,
                'name' => $accom_name,
            ];
        }

        return [
            "hb_sharegroop_public_key" => $this->get_public_key(),
            "hb_sharegroop_locale" => function_exists('pll_current_language')
                ? pll_current_language()
                : substr(get_option('WPLANG'), 0, 2),
            "hb_sharegroop_currency" => get_option('hb_currency'),
            "hb_sharegroop_mode" => get_option('hb_sharegroop_mode'),
            "hb_sharegroop_wait_msg" => $this->hbdb->get_string('sharegroop_wait_confirmation_msg'),
            "hb_sharegroop_accomodations" => json_encode($accomodations),
        ];
    }

    /**
     * Displays the payment selection from
     * @return string|null
     */
    public function payment_form()
    {
        $output = '<div id="sharegroop-captain"></div>';
        $output .= '<script src="' . $this->get_url() . '"></script>';

        $payment_desc = $this->hbdb->get_string('sharegroop_payment_method_description');
        if ($payment_desc) {
            $output .= '<div><p>' . $payment_desc . '</p></div>';
        }

        return $output;
    }

    /**
     * Displays the bottom of the payment selection form
     * @return mixed|string|void
     */
    public function bottom_area()
    {
        $line1 = $this->hbdb->get_string('sharegroop_bottom_text_line_1');
        $output = '<i><small>';

        if ($line1) {
            $output .= '<span>' . $line1 . ' &nbsp;</span>';
            $output .= $this->get_payment_method_icons();
        }

        $output .= '</small></i>';
        return apply_filters('hb_sharegroop_bottom_area', $output);
    }

    /**************************************************************
     *                  Payment Methods
     **************************************************************/

    /**
     * Process the required payment
     * @param $reservation
     * @param $customer
     * @param $amount
     * @return mixed
     */
    public function process_payment($reservation, $customer, $amount)
    {
        if (empty($_POST['hb_sharegroup_order_id'])) {
            return 'E1001: Unable to process payment, please contact administrator.';
        }

        if (empty($_POST['hb_sharegroup_amount_paid'])) {
            return 'E1002: Unable to process payment, please contact administrator.';
        }

        $orderRegex = '/ord_[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}/';
        if (preg_match($orderRegex, $_POST['hb_sharegroup_order_id']) !== 1) {
            return 'E1003: Invalid Order ID, please contact administrator.';
        }

        $orderId = filter_input(INPUT_POST, 'hb_sharegroup_order_id', FILTER_SANITIZE_STRING);
        $paid = filter_input(INPUT_POST, 'hb_sharegroup_amount_paid', FILTER_VALIDATE_INT);

        return [
            'success' => true,
            'payment_info' => $orderId,
            'admin_comment' => $orderId,
            'paid' => $paid,
        ];
    }

    /**************************************************************
     *                  Internal Helper Methods
     **************************************************************/

    /**
     * Return js url
     * @return string
     */
    private function get_url()
    {
        switch (get_option('hb_sharegroop_mode')) {
            case 'test':
                return 'https://widget.sandbox.sharegroop.com/widget.js';
            case 'live':
                return 'https://widget.sharegroop.com/widget.js';
        }
    }

    /**
     * Return the secret key
     * @return mixed|void
     */
    private function get_secret_key()
    {
        switch (get_option('hb_sharegroop_mode')) {
            case 'test':
                return get_option('hb_sharegroop_test_secret_key');
            case 'live':
                return get_option('hb_sharegroop_live_secret_key');
        }
    }

    /**
     * Return the public key
     * @return mixed|void
     */
    private function get_public_key()
    {
        switch (get_option('hb_sharegroop_mode')) {
            case 'test':
                return get_option('hb_sharegroop_test_public_key');
            case 'live':
                return get_option('hb_sharegroop_live_public_key');
        }
    }

    /**
     * Return the payment methods icons as html
     * @return string
     */
    protected function get_payment_method_icons()
    {
        $output = '';
        $payment_method_icons = json_decode(get_option('hb_sharegroop_icons'));
        if (!is_array($payment_method_icons)) {
            $payment_method_icons = [];
        }

        foreach ($payment_method_icons as $icon_id) {
            if ($icon_id != 'sharegroop') {
                $output .= ' ';
                $output .= '<img ';
                $output .= 'src="' . plugin_dir_url(__FILE__) . 'img/' . $icon_id . '.png" ';
                $output .= 'alt="' . $this->payment_methods[$icon_id] . '" ';
                $output .= 'title="' . $this->payment_methods[$icon_id] . '" ';
                $output .= '/>';
            }
        }

        return $output;
    }

}
