<?php

/** @var WP_Mock_Filesystem $mock_fs */

class Test_Open_Badge_Factory_Credentials extends WP_UnitTestCase {
	/** @var  WP_Mock_Filesystem */
	protected $mock_fs;

	public function setUp() {
		// Creating a new mock filesystem.
		// We assign it to a member property so we can access it later.
		$this->mock_fs = new WP_Mock_Filesystem();

		// Create the /wp-content directory.
		// This part is optional, and you'll do more or less setup here depending on
		// what you are testing.
		$this->mock_fs->mkdir_p( WP_CONTENT_DIR );

		// Tell the WordPress filesystem API shim to use this mock filesystem.
		WP_Filesystem_Mock::set_mock( $this->mock_fs );

		// Tell the shim to start overriding whatever other filesystem access method
		// is in use.
		WP_Filesystem_Mock::start();

		// Set up the $wp_filesystem global, if the code being tested doesn't do this.
//		WP_Filesystem();
	}

	public function test_get_private_key_path() {
		$plugin_path = 'openbadges-for-wordpress';

		$this->assertTrue( true );


	}
}