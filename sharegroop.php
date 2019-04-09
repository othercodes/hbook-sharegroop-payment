<?php

/**
 * Class HbSharegroop
 * @copyright otherCode 2019
 * @author Unay Santisteban <usantisteban@othercode.es>
 */
class HbSharegroop extends HbPaymentGateway
{
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

        $this->id = 'sharegroop';
        $this->version = '1.0.0';
        $this->name = esc_html__('ShareGroop', 'hb-sharegroop-admin');;
        $this->has_redirection = 'no';

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
        ];
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
     * Return the
     * @return array
     */
    public function js_data()
    {
        return [
            'hb_sharegroop_url' => 'some-url',
        ];
    }

    /**
     * Displays the payment selection from
     * @return string|null
     */
    public function payment_form()
    {
        $config = [
            "selector" => "#sharegroop-captain",
            "publicKey" => $this->get_public_key(),
            "locale" => "en",
            "currency" => "EUR",
        ];

        $output = '<div id="sharegroop-captain"></div>';
        $output .= '<script src="' . $this->get_url() . '"></script>';
        $output .= '<script>ShareGroop.initCaptain(' . json_encode($config) . ').mount();</script>';

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
     * Get the payment token
     * @return bool|string
     */
    public function get_payment_token()
    {
        return trim($_GET['token']);
    }

    public function process_payment($resa_info, $customer_info, $amount_to_pay)
    {
        $parameters_to_remove = array('token');
        $return_urls = $this->get_return_urls($parameters_to_remove);
        $token = substr(bin2hex(openssl_random_pseudo_bytes(64)), -20);
        $data_sharegroop = $this->data_for_sharegroop($amount_to_pay, $return_urls, $token, $resa_info, $customer_info);

        return array(
            'success' => true,
            'payment_token' => $token,
            'return_url' => $return_urls['payment_confirm'],
            'data_sharegroop' => $data_sharegroop
        );
    }

    public function confirm_payment()
    {
        $resa = $this->hbdb->get_resa_by_payment_token($_GET['token']);
        if (!$resa) {
            $response = array(
                'success' => false,
                'error_msg' => $this->hbdb->get_string('timeout_error')
            );
        } else {
            $status = '';
            $error_msg = '';

            if (isset ($_GET['Result'])) {
                $status = $_GET['Result'];

            }
            switch ($status) {
                case '00000' :
                    $response = array(
                        'success' => true,
                    );
                    break;

                case '99999' :
                    $response = array(
                        'success' => true,
                        'payment_status' => 'Pending',
                        'payment_status_reason' => esc_html__('You have chosen a payment method that needs further confirmation of payment.')
                    );
                    break;

                default :
                    $response = array(
                        'success' => false,
                        'error_msg' => sprintf(esc_html__('No charge has been done as an error occured on Credit Agricole with your payment. Please try again and if the problem occurs again, contact us with this error code: %s. We will do our best to assist you with your reservation.',
                            'hb-sharegroop-admin'), $status),
                    );
                    break;
            }
        }

        if ($response['success']) {
            $resa_id = $this->hbdb->update_resa_after_payment($_GET['token'], '', '', $resa['amount_to_pay']);
            if (!$resa_id) {
                $response = array(
                    'success' => false,
                    'error_msg' => 'Error (could not update reservation).'
                );
            } else {
                $this->utils->send_email('new_resa', $resa_id);
            }
        }

        return $response;
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
