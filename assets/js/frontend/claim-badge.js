/*
ID to use in shortcode for testing: OO7E1GaAYGa9T (Railway Ambassador Badge)
shortcode example:
[obf_display_claimable_badge id="OO7E1GaAYGa9T"]



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
			security: $(this).data('nonce'),
			email_footer: 'You have claimed this badge on the railtalent.org website',
			email_link_text: 'email link text that obf will ignore anyway'
		}

		$.ajax({
			type: "post",
			dataType: "json",
			url: openbadges_ajax_object.ajaxurl,
			action: "obf_issue_badge",
			data: data,
			success: function( msg ) {
				if ( msg.success == true ) {
					$('#open_badge_to_claim').prop('disabled', true).after('<p class="success">'+msg.data+'</p>')
				}
			},
			fail: function( jqXHR, textStatus, errorThrown ) {
				console.log( textStatus, errorThrown)
			}
		})


	})

})