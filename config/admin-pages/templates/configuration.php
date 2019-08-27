<?php
	global $title;
	use appsaloon\obwp\internal_apis\Configuration_Api;
?>
<div class="wrap">
	<h2><?php echo $title;?></h2>
	<h3><span class="dashicons dashicons-admin-tools"></span>&nbsp;&nbsp;Configuration</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="api-token">Get CSR token</label>&nbsp;&nbsp;<span class="dashicons dashicons-warning"></span>
				</th>
				<td>
					<a href="https://openbadgefactory.com/c/client/my/edit2/apikey" title="Get a new api token" class="button button-secondary" target="_blank">Get a new api token</a>
					<br>
					<span class="description">You need to login on openbadgefactory.com <span class="dashicons dashicons-external"></span></span>
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
					<button id="generate_new_obf_api_credentials" class="button button-primary">Get certificate</button>
					<span id="refresh_request_status">&nbsp;&nbsp;&nbsp;<span class="dashicons dashicons-yes"></span>&nbsp;Certificate successful</span>
				</td>
			</tr>
		</tbody>
	</table>



	<div>Last saved on: </div>
	<div id="last_saved_date"><?php echo Configuration_Api::get_formatted_obf_api_credentials_date(); ?></div>
</div>
