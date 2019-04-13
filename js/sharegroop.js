jQuery(document).ready(function ($) {

    /**
     * The complete HBook details form
     * @type {*|jQuery|HTMLElement}
     */
    const hb_booking_details_form = $('form.hb-booking-details-form.has-validation-callback');

    /**
     * The HBook submit button
     * @type {*|jQuery|HTMLElement}
     */
    const hb_submit_btn = hb_booking_details_form.find('.hb-confirm-button > input[type="submit"]');

    /**
     * Creates the actual order against sharegroop
     * @param details
     */
    const hb_sharegroup_payment_process = (details) => {

        let hb_accom_price_raw = $('.hb-accom-price-raw').attr('value');

        let hb_first_name = details.find('input[name="hb_first_name"]').val();
        let hb_last_name = details.find('input[name="hb_last_name"]').val();
        let hb_email = details.find('input[name="hb_email"]').val();

        let check_in = details.find('input[name="hb-details-check-in"]').val();
        let check_out = details.find('input[name="hb-details-check-out"]').val();
        var accom_id = details.find('input[name="hb-details-accom-id"]').val();

        let submit_btn = details.find('.hb-confirm-button > input[type="submit"]');

        let accomodation = JSON.parse(hb_sharegroop_accomodations).find(function (element) {
            return element.id == accom_id;
        });

        let configuration = {
            "selector": "#sharegroop-captain",
            "publicKey": hb_sharegroop_public_key,
            "locale": hb_sharegroop_locale,
            "currency": hb_sharegroop_currency,
            "order": {
                "email": hb_email,
                "firstName": hb_first_name,
                "lastName": hb_last_name,
                "trackId": accom_id + '.' + check_in + '.' + check_out,
                "amount": hb_accom_price_raw * 100,
                "items": [
                    {
                        "trackId": accom_id + '.' + check_in + '.' + check_out,
                        "name": accomodation.name,
                        "description": accomodation.name + '#' +accom_id + '.' + check_in + '.' + check_out,
                        "amount": hb_accom_price_raw * 100
                    }
                ]
            },
            "events": {
                "onReady": function () {
                    console.log('[captain] ready');
                },
                "onValidated": function (data) {
                    console.log('[captain] validated', data);

                    $('<input>').attr({
                        type: 'hidden',
                        id: 'hb_sharegroup_order_id',
                        name: 'hb_sharegroup_order_id',
                        value: data.order
                    }).appendTo('form.hb-booking-details-form.has-validation-callback');

                    $('<input>').attr({
                        type: 'hidden',
                        id: 'hb_sharegroup_amount_paid',
                        name: 'hb_sharegroup_amount_paid',
                        value: data.amount / 100
                    }).appendTo('form.hb-booking-details-form.has-validation-callback');

                    submit_btn.submit();
                }
            }
        };

        if (hb_sharegroop_mode === 'test') {
            console.log(configuration);
        }

        ShareGroop.initCaptain(configuration).mount();
    };

    /**
     * Default value of the submit button
     * @type string
     */
    const hb_submit_btn_default_text = hb_submit_btn.val();

    if ($('input:radio[value="sharegroop"]').is(':checked')) {

        hb_submit_btn.blur().val(hb_sharegroop_wait_msg);
        hb_submit_btn.blur().prop('disabled', true);

        hb_sharegroup_payment_process(hb_booking_details_form);

    } else {
        $('input:radio[name="hb-payment-gateway"]').change(function () {
            if ($(this).attr('value') === 'sharegroop') {

                hb_submit_btn.blur().val(hb_sharegroop_wait_msg);
                hb_submit_btn.blur().prop('disabled', true);

                hb_sharegroup_payment_process(hb_booking_details_form);
            } else {

                hb_submit_btn.blur().val(hb_submit_btn_default_text);
                hb_submit_btn.blur().prop('disabled', false);
            }
        });
    }
});