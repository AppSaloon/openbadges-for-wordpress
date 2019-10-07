console.log('yup')

/*
ID to use in shortcode for testing: OO7E1GaAYGa9T (Railway Ambassador Badge)
shortcode example:
[obf_display_claimable_badge badge_id="OO7E1GaAYGa9T"]



Execute Ajax call to ajaxurl (variable available in js).
Similar to button action in configuration.js:
names already changed for claim-badge action

$('#obf_issue_badge').on('click', function() {

		var data = {
			action: "obf_issue_badge",
			badge_id: "badge_id" // attached to button via data attribute
			recipient: [user_email] // attached to button via data attribute, must be put inside an array to make api call
			security: nonce // attached to button via data attribute

	 	}

	 	$.post( ajaxurl, data )
	 		.done(function( response ) {
	 			console.log(response)
	 		})
	 		.fail(function(xhr, status, error) {
	 			console.log(status, error)
	 		})
	})


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