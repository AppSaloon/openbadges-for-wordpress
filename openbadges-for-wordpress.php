<?php
/*
Plugin Name: OpenBadges for WordPress
Plugin URI: https://github.com/AppSaloon/openbadges-for-wordpress
Description: OpenBadges for WordPress
Version: 1.0.5
Author: AppSaloon (Koen Gabriels & Mark Creeten)
Author URI: https://www.appsaloon.be/
*/

require_once __DIR__ . '/src/external-apis/Open_Badge_Factory_Api.php';
require_once __DIR__ . '/src/external-apis/Issue_Open_Badge_Request_Body.php';
require_once __DIR__ . '/src/external-apis/Open_Badge_Factory_Credentials.php';
require_once __DIR__ . '/src/Admin_Menu.php';
use appsaloon\obwp\settings\Admin_Menu;
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Credentials;
use appsaloon\obwp\external_apis\openbadgefactory\Open_Badge_Factory_Api;

if( ! defined( 'OBWP_PLUGIN_URL' ) ) define( 'OBWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

( new Admin_Menu() )->register_hooks();

$obf_credentials = new Open_Badge_Factory_Credentials( __DIR__ );
new Open_Badge_Factory_Api( $obf_credentials, plugin_dir_url( __FILE__) ) ;
