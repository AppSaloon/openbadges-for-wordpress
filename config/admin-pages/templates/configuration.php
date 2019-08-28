<?php
	global $title;
	use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Credentials;
?>
<div class="wrap">
	<h2><?php echo $title;?></h2>
	<h3><span class="dashicons dashicons-admin-tools"></span>&nbsp;&nbsp;Configuration</h3>
</div>
<div id="poststuff">
	<div id="post-body" class="metabox-holder columns-2">
		<div id="post-body-content">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="api-token">Get CSR token</label>&nbsp;&nbsp;<span class="dashicons dashicons-warning tooltip" title="You can find the CRS token on https://openbadgefactory.com.<br>Log in > admin tools > API key > Generate certificate signing request token"></span>
						</th>
						<td>
							<a href="https://openbadgefactory.com/c/client/my/edit2/apikey" title="Get a new api token" class="button button-secondary" target="_blank">Get a new api token</a>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="api-token">CSR Token</label>
						</th>
						<td>
							<textarea id="api_token" cols="60" rows="9"></textarea>
							<br>
							<span class="description">CSR token will not be saved since it's only valid for 10 minutes</span>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							<button id="generate_new_obf_api_credentials" class="button button-primary">
							<?php if ( Open_Badge_Factory_Credentials::get_formatted_obf_api_credentials_date() == 'false' ) {
								echo 'Get certificate';
							} else {
								echo 'Renew certificate';
							}?>
							<span class="loader">Loading...</span>
							<p id="refresh_request_status"><strong></strong></p>
						</td>
					</tr>
					<tr>
						<th scope="row">Certified on:</th>
						<td>
							<p id="last_saved_date">
							<?php
							if ( Open_Badge_Factory_Credentials::get_formatted_obf_api_credentials_date() == 'false' ) {
								echo 'No date found';
							} else {
								echo Open_Badge_Factory_Credentials::get_formatted_obf_api_credentials_date();
							}
							?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div id="postbox-container-1" class="postbox-container">
			<div class="meta-box-sortables">
				<div class="postbox">
					<h3>Check connection</h3>
					<div class="inside">
						<button id="test_obf_api_connection" class="button button-secondary">Check connection</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
