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
<div id="openbadges">
<?php if( $is_user_allowed_to_claim_badge ): ?>
	
		<h1 class="tbk__title"><?php _e( 'Congratulations!', 'obwp' ); ?></h1>
		<h4 class="tbk__subtitle"><?php _e( 'You earned a badge', 'obwp' ); ?></h4>
		<div class="row">
			<div class="col-md-3 text-center">
				<img src="<?php echo $badge['image'];?>" class="openbadge-image">
				<h3><?php echo $badge['name'];?></h3>
				<p><?php echo __( 'Claim your badge and showcase your knowledge in your Open Badges Passport!', 'obwp' );?></p>
				<?php $nonce = wp_create_nonce( 'open-badge-issue-' . $badge['id'] ) ?>
				<button id="open_badge_to_claim" data-badge_id="<?php echo $badge['id'];?>" data-user_email="<?php echo $user_email;?>" data-nonce="<?php echo $nonce ?>" class="zn-button btn btn-fullcolor btn--rounded"><span class="zn-buttonText">Mail me my badge</span></button>
			</div>
		</div>
	
<?php else :?>

	<div class="row">
		<div class="col-md-3 col-sm-3 text-center">
			<img src="<?php echo $badge['image'];?>" />
			<h2><?php echo $badge['name'];?></h2>
			<p><?php echo __( 'Log in to find out if you can claim this badge', 'obwp' );?></p>
		</div>
	</div>

<?php endif;?>

</div>