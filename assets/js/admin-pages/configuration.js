jQuery(document).ready(function( $ ) {

	var response_elem = $('#refresh_request_status')
	$("#generate_new_obf_api_credentials").on( 'click', function() {

		$(this).attr('disabled', 'disabled')
		$('.loader').css('display', 'inline-block')

	 	var data = {
			action: "refresh_obf_api_credentials",
			security: adminPageData.ajaxNonce,
			api_token: $("#api_token").val()
	 	}

	 	//check if textarea is filled, so no ajax call needed when nothing is entered
	 	if ( $("#api_token").val() == '' ) {
	 		show_api_response( 'No api token provided', false )
	 	} else {
	 		//textarea has value (something), no we validate it
	 		$.post( ajaxurl, data )
			.done(function( response ) {
				show_api_response( response.data.message, true )
				if( response.data.hasOwnProperty( 'date') ) {
					$("#last_saved_date").text(response.data.date)
				}
			})
			.fail(function(xhr, status, error) {
				show_api_response( xhr.responseJSON.data.message, false )
			})
	 	}
	})

	$('#test_obf_api_connection').on('click', function() {

		var data = {
			action: "test_obf_api_connection",
			security: adminPageData.ajaxNonce
	 	}

	 	$.post( ajaxurl, data )
	 		.done(function( response ) {
	 			console.log(response)
	 		})
	 		.fail(function(xhr, status, error) {
	 			console.log(status, error)
	 		})
	})



	Tipped.create('.tooltip', {
		position: 'right'
	})

	function show_api_response( message, status ) {
 		$('.loader').css('display', 'none')
		if ( status == true ) {
			response_elem.addClass('responsesuccess')
			$('&nbsp;&nbsp;&nbsp;<span class="dashicons dashicons-yes"></span>&nbsp;').insertBefore('#refresh_request_status strong')
		} else {
			response_elem.addClass('responseerror')
			$('&nbsp;&nbsp;&nbsp;<span class="dashicons dashicons-no"></span>&nbsp;').insertBefore('#refresh_request_status strong')
		}
		
		response_elem.find('strong').text( message )
 	}

})