jQuery(document).ready(function( $ ) {
       $("#generate_new_obf_api_credentials").click( function(){
           var data = {
               action: "refresh_obf_api_credentials",
               security: adminPageData.ajaxNonce,
               api_token: $("#api_token").val()
           };

           $.post( ajaxurl, data )
               .done(function( response ){
                   $("#refresh_request_status").text( response.data.message );

                   if( response.data.hasOwnProperty( 'date') ) {
                       $("#last_saved_date").text(response.data.date);
                   }
               })
               .fail(function(xhr, status, error) {
                   $("#refresh_request_status").text( xhr.responseJSON.data.message );
               })
           ;
       });

});