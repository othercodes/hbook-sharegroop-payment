jQuery(document).ready(function ($) {
    sharegroop_data_live();

    function sharegroop_data_live() {
        if ($('input[name="hb_sharegroop_mode"]:checked').val() == 'live') {
            $('.hb-sharegroop-mode-live').slideDown();
            $('.hb-sharegroop-mode-test').slideUp();
        } else {
            $('.hb-sharegroop-mode-live').slideUp();
            $('.hb-sharegroop-mode-test').slideDown();
        }
    }

    $('input[name="hb_sharegroop_mode"]').change(function () {
        sharegroop_data_live();
    });
});