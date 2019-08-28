<?php
/*
Plugin Name: OpenBadges for WordPress
Plugin URI: https://github.com/AppSaloon/openbadges-for-wordpress
Description: OpenBadges for WordPress
Version: 1.0.0
Author: AppSaloon
Author URI: https://www.appsaloon.be/
*/

require_once __DIR__ . '/src/external-apis/Open_Badge_Factory_Api.php';
require_once __DIR__ . '/src/external-apis/Open_Badge_Factory_Credentials.php';
require_once __DIR__ . '/src/Admin_Menu.php';
require_once  __DIR__ . '/src/internal-apis/Configuration_Api.php';
use appsaloon\obwp\settings\Admin_Menu;
use appsaloon\obwp\internal_apis\Configuration_Api;
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Credentials;
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Api;

new Admin_Menu( plugin_dir_url( __FILE__) );

$obf_credentials = new Open_Badge_Factory_Credentials( __DIR__  );
new Configuration_Api( new Open_Badge_Factory_Api( $obf_credentials ) );
