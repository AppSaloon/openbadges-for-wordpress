<?php
/**
* The variable $badge is available here because this template is loaded from a shortcode callback that exposes $badge
*
*	$badge is an array with the following indexes:
*	email_subject,
*	tmetadata,
*	mtime,
*	intent,
*	email_footer,
*	email_link_text,
*	name,
*	evidence_definition,
*	tags,
*	ctime,
*	image,
*	expires,
*	copy_of,
*	email_body,
*	draft,
*	lastmodifiedby,
*	external_access,
*	description,
*	css,
*	image_small,
*	id,
*	criteria_html,
*	language,
*	evidence_html,
*	deleted,
*	client_alias,
*	client_id,
*	alignment
*
*	The following variables are also available
* 	$is_user_allowed_to_claim_badge -> used to check if the user is allowed to claim a badge
* 	$user_email -> only available if $is_user_allowed_to_claim_badge is true
*
**/

?>
<?php if( $is_user_allowed_to_claim_badge ): ?>
	<div>
		<h1><?php _ex( 'Congratulations!', 'obwp' );?></h1>
		<h1><?php _ex( 'You earned a badge', 'obwp' );?></h1>
		<img src="<?php echo $badge['image'];?>" />
		<h2><?php echo $badge['name'];?></h2>
		<p><?php echo __( 'Claim your badge and showcase your knowledge in your Open Badges Passport!', 'obwp' );?></p>
		<button id="open-badge-to-claim-<?php echo $badge['id'] ?>" badge_id="<?php echo $badge['id'];?>" user_email="<?php echo $user_email;?>">Mail me my badge</button>
	</div>
<?php else:?>
<div>
	<img src="<?php echo $badge['image'];?>" />
	<h2><?php echo $badge['name'];?></h2>
	<p><?php echo __( 'Log in to find out if you can claim this badge', 'obwp' );?></p>
</div>
<?php endif;?>
