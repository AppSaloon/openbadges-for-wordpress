<?php
    global $title;
?>
<div>
    <h2><?php echo $title;?></h2>
    <div>
        <label for="api-token">Api Token</label>
        <textarea id="api-token" placeholder="Api token will not be saved since it's only valid for 10 minutes"></textarea>
    </div>
    <div>
        <a href="https://openbadgefactory.com/c/client/my/edit2/apikey">Click here to get a new api token</a>
    </div>
    <div id="refresh-request-status">

    </div>
    <button id="generate-new-obf-api-credentials">
        Click here to generate a new private key and client certificate.
    </button>
    <div>Private key status: </div>
    <div>Client Certificate status:</div>
</div>
