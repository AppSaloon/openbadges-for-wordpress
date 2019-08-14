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
require_once __DIR__ . '/src/Admin_Menu.php';
use appsaloon\obfw\settings\Admin_Menu;

new Admin_Menu();
