<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Openbadges_For_Wordpress
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
$is_travis_run = getenv( 'IS_TRAVIS' );

if( $is_travis_run ) {
	$WP_VERSION = getenv( 'WP_VERSION' );
} else {
	$WP_VERSION = '5.2';
}


if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	$plugin_path = dirname( dirname( __FILE__ ) );
	require  $plugin_path . '/openbadges-for-wordpress.php';

	if( getenv( 'IS_TRAVIS') ) {
		$WP_VERSION = getenv( 'WP_VERSION' );

		shell_exec( 'cd ' . $plugin_path );
		shell_exec( 'composer install' );
		if( ! class_exists( 'WP_Filesystem_Base') ) {
			$_tests_dir = getenv( 'WP_TESTS_DIR' );
			if ( ! $_tests_dir ) {
				$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
			}

			// download the WordPress base filesystem API class
			shell_exec( 'mkdir -p ' . $_tests_dir . '/src/wp-admin/includes' );
			shell_exec( 'mkdir -p ' . $_tests_dir . '/src/wp-includes' );
			shell_exec( 'wget -q -nv -O ' . $_tests_dir . '/src/wp-admin/includes/class-wp-filesystem-base.php http://develop.svn.wordpress.org/branches/' . $WP_VERSION . '/src/wp-admin/includes/class-wp-filesystem-base.php' );
			shell_exec( 'wget -q -nv -O ' . $_tests_dir . '/src/wp-includes/class-wp-error.php http://develop.svn.wordpress.org/branches/' . $WP_VERSION . '/src/wp-includes/class-wp-error.php' );

			// load WordPress' base filesystem API class
			require_once ( $_tests_dir . '/src/wp-admin/includes/class-wp-filesystem-base.php' );
		}
	}

	// Load the filesystem API shim that uses mock filesystems
	require_once ( dirname( dirname( __FILE__ ) ) . '/vendor/jdgrimes/wp-filesystem-mock/src/wp-filesystem-mock.php' );
	require_once ( dirname( dirname( __FILE__ ) ) . '/vendor/jdgrimes/wp-filesystem-mock/src/wp-mock-filesystem.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// download the WordPress base filesystem API class
shell_exec( 'mkdir -p ' . $_tests_dir . '/src/wp-admin/includes' );
shell_exec( 'mkdir -p ' . $_tests_dir . '/src/wp-includes' );
shell_exec( 'wget -q -nv -O ' . $_tests_dir . '/src/wp-admin/includes/class-wp-filesystem-base.php http://develop.svn.wordpress.org/branches/' . $WP_VERSION . '/src/wp-admin/includes/class-wp-filesystem-base.php' );
shell_exec( 'wget -q -nv -O ' . $_tests_dir . '/src/wp-includes/class-wp-error.php http://develop.svn.wordpress.org/branches/' . $WP_VERSION . '/src/wp-includes/class-wp-error.php' );

// load WordPress' base filesystem API class
require_once ( $_tests_dir . '/src/wp-admin/includes/class-wp-filesystem-base.php' );


// Load the filesystem API shim that uses mock filesystems
//require_once ( $_tests_dir . '/../vendor/jdgrimes/wp-filesystem-mock-src/wp-filesystem-mock.php' );

/**
 * The mock filesystem class.
 */

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
