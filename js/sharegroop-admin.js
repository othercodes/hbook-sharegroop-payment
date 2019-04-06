jQuery( document ).ready( function( $ ) {
	etransactions_data_live();

	function etransactions_data_live() {
		if ( $( 'input[name="hb_etransactions_mode"]:checked' ).val() == 'live' ) {
			$( '.hb-etransactions-mode-live' ).slideDown();
			$( '.hb-etransactions-mode-test' ).slideUp();
		} else {
			$( '.hb-etransactions-mode-live' ).slideUp();
			$( '.hb-etransactions-mode-test' ).slideDown();
		}
	}

	$( 'input[name="hb_etransactions_mode"]' ).change( function() {
		etransactions_data_live();
	});
});