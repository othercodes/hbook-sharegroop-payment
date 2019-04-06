
function hb_etransactions_payment_process( $form, callback_func ) {
	$form.addClass( 'submitted' );
	$form.find( 'input[type="submit"]' ).blur().prop( 'disabled', true );
	$form.find( '.hb-saving-resa' ).slideDown();
	$form.find( '.hb-confirm-error' ).slideUp();
	$form.append( jQuery( '<input type="hidden" name="hb-current-url" value="' + document.URL + '" />' ) );
	$form.append( jQuery( '<input type="hidden" name="check-in-formatted" value="' + $form.parents( '.hbook-wrapper' ).find( '.hb-check-in-date' ).val() + '" />' ) );
	$form.append( jQuery( '<input type="hidden" name="check-out-formatted" value="' + $form.parents( '.hbook-wrapper' ).find( '.hb-check-out-date' ).val() + '" />' ) );
	callback_func( $form );
	return true;
}

function hb_etransactions_payment_redirection( $form, response ) {
	var current_url = document.URL,
		back_url = '',
		pattern;

	pattern = /&payment_gateway(\=[^&]*)?(?=&|$)|payment_gateway(\=[^&]*)?(&|$)/;
	current_url = current_url.replace( pattern, '' );
	pattern = /&payment_confirm(\=[^&]*)?(?=&|$)|payment_confirm(\=[^&]*)?(&|$)/;
	current_url = current_url.replace( pattern, '' );
	pattern = /&payment_cancel(\=[^&]*)?(?=&|$)|payment_cancel(\=[^&]*)?(&|$)/;
	current_url = current_url.replace( pattern, '' );
	pattern = /&token(\=[^&]*)?(?=&|$)|token(\=[^&]*)?(&|$)/;
	current_url = current_url.replace( pattern, '' );

	if ( current_url.indexOf( '#' ) > 0 ) {
		current_url = current_url.substr( 0, current_url.indexOf( '#' ) );
	}

	if ( current_url.slice(-1) != '?' ) {
		if ( current_url.indexOf( '?' ) > 0 ) {
			current_url += '&';
		} else {
			current_url += '?';
		}
	}
	back_url = current_url + 'payment_gateway=etransactions&payment_cancel=1&token=' + response['payment_token'];
	try {
		history.pushState( {}, '', back_url );
	} catch ( e ) {}

	jQuery( '<form id="etransactionsPaybox" method="POST" action="' + hb_etransactions_url + '" accept-charset="UTF-8" >'
			+ '<input type="hidden" name="PBX_SITE" value="' + response['data_etransactions']['PBX_SITE'] + '"/>'
			+ '<input type="hidden" name="PBX_RANG" value="' + response['data_etransactions']['PBX_RANG'] + '"/>'
			+ '<input type="hidden" name="PBX_IDENTIFIANT" value="' + response['data_etransactions']['PBX_IDENTIFIANT'] + '"/>'
			+ '<input type="hidden" name="PBX_TOTAL" value="' + response['data_etransactions']['PBX_TOTAL'] + '"/>'
			+ '<input type="hidden" name="PBX_DEVISE" value="' + response['data_etransactions']['PBX_DEVISE'] + '"/>'
			+ '<input type="hidden" name="PBX_CMD" value="' + response['data_etransactions']['PBX_CMD'] + '"/>'
			+ '<input type="hidden" name="PBX_PORTEUR" value="' + response['data_etransactions']['PBX_PORTEUR'] + '"/>'
			+ '<input type="hidden" name="PXB_LANGUE" value="' + response['data_etransactions']['PXB_LANGUE'] + '"/>'
			+ '<input type="hidden" name="PBX_RETOUR" value="' + response['data_etransactions']['PBX_RETOUR'] + '"/>'
			+ '<input type="hidden" name="PBX_EFFECTUE" value="' + response['data_etransactions']['PBX_EFFECTUE'] + '"/>'
			+ '<input type="hidden" name="PBX_ANNULE" value="' + response['data_etransactions']['PBX_ANNULE'] + '"/>'
			+ '<input type="hidden" name="PBX_REFUSE" value="' + response['data_etransactions']['PBX_REFUSE'] + '"/>'
			+ '<input type="hidden" name="PBX_HASH" value="' + response['data_etransactions']['PBX_HASH'] + '"/>'
			+ '<input type="hidden" name="PBX_TIME" value="' + response['data_etransactions']['PBX_TIME'] + '"/>'
			+ '<input type="hidden" name="PBX_HMAC" value="' + response['data_etransactions']['PBX_HMAC'] + '"/>'
			+ '</form>'
	).appendTo(document.body).submit();

}