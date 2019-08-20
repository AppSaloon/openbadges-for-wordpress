<?php
    global $title;
    use appsaloon\obwp\internal_apis\Configuration_Api;
?>
<div>
    <h2><?php echo $title;?></h2>
    <div>
        <label for="api-token">Api Token</label>
        <textarea id="api_token" placeholder="Api token will not be saved since it's only valid for 10 minutes"></textarea>
    </div>
    <div>
        <a href="https://openbadgefactory.com/c/client/my/edit2/apikey">Click here to get a new api token</a>
    </div>
    <div id="refresh_request_status">

    </div>
    <button id="generate_new_obf_api_credentials">
        Click here to generate a new private key and client certificate.
    </button>
    <div>Last saved on: </div>
    <div id="last_saved_date"><?php echo Configuration_Api::get_formatted_obf_api_credentials_date(); ?></div>
</div>
