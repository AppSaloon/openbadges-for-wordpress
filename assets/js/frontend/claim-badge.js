/*
ID to use in shortcode for testing: OO7E1GaAYGa9T (Railway Ambassador Badge)
shortcode example:
[obf_display_claimable_badge badge_id="OO7E1GaAYGa9T"]



Returned json in case of success:
{
	"success":true,
	"data":["badge was issued"]
}

In case of failure:
{
	"success":false,
	"data":["<message describing the error>"]
}
* */
jQuery(document).ready(function( $ ) {

	$('#open_badge_to_claim').on('click', function() {

		var data = {
			action: "obf_issue_badge",
			// attached to button via data attribute
			badge_id: $(this).data('badge_id'), 
			// attached to button via data attribute, must be put inside an array to make api call
			recipient: [$(this).data('user_email')],
			// attached to button via data attribute
			security: $(this).data('nonce') 
		}

		console.log(openbadges_ajax_object.ajaxurl)

		// $.post( openbadges_ajax_object, data )
		// 	.done(function( response ) {
		// 		console.log( response )
		// 	})
		// 	.fail(function(xhr, status, error) {
		// 		console.log(status, error)
		// 	})

		$.ajax({
			type: "get",
			dataType: "json",
			url: openbadges_ajax_object.ajaxurl,
			action: "obf_issue_badge",
			data: data,
			success: function(msg){
				console.log(jqXHR);
			}
		})


	})

})