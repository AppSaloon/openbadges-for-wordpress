jQuery(document).ready(function(e){e("#open_badge_to_claim").on("click",function(){var a={action:"obf_issue_badge",badge_id:e(this).data("badge_id"),recipient:[e(this).data("user_email")],security:e(this).data("nonce"),email_footer:"You have claimed this badge on the railtalent.org website",email_link_text:"email link text that obf will ignore anyway"};console.log(openbadges_ajax_object.ajaxurl),e.ajax({type:"post",dataType:"json",url:openbadges_ajax_object.ajaxurl,action:"obf_issue_badge",data:a,success:function(a){console.log(jqXHR)}})})});