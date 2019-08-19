jQuery(document).ready(function( $ ) {
       $("#generate-new-obf-api-credentials").click( function(){
           console.log('button clicked');
           console.log( adminPageData );

           var data = {
               action: "refresh_obf_api_credentials",
               security: adminPageData.ajaxNonce,
               api_token: $("#api-token").val()
           };

           $.post( ajaxurl , data, function( response ) {
               $("#refresh-request-status").text(response.data);
           } );
       });

});