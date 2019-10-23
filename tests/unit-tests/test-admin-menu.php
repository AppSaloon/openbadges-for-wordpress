<?php

require_once __DIR__ . '/../../src/Admin_Menu.php';
use appsaloon\obwp\settings\Admin_Menu;
use org\bovigo\vfs\vfsStream,
	org\bovigo\vfs\vfsStreamDirectory;

class Test_Admin_Menu extends \WP_Mock\Tools\TestCase {

	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_register_hooks() {
		$admin_menu = new Admin_Menu();
		WP_Mock::expectActionAdded( 'admin_menu', array( $admin_menu, 'add_settings_interface' ) );
		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $admin_menu, 'admin_enqueue_scripts_and_styles' ) );

		$admin_menu->register_hooks();

		$this->assertHooksAdded();
	}

	public function test_styles_and_scripts_are_enqueued() {
		define( 'OBWP_PLUGIN_URL', 'obwp_plugin_url_mocked/' );

		WP_Mock::userFunction( 'wp_create_nonce',
			array(
				'times' => 1,
				'return' => 'a_nonce_for_this'
			)
		);

		WP_Mock::userFunction('wp_register_style',
			array(
				'times' => 1,
				'args' => array(
					'openbadges_css',
					'obwp_plugin_url_mocked/dist/css/admin-openbadges.css'
				)
			)
		);

		WP_Mock::userFunction( 'wp_enqueue_style',
			array(
				'times' => 1,
				'args' => 'openbadges_css'
			)
		);

		WP_Mock::userFunction('wp_enqueue_script',
			array(
				'times' => 1,
				'args' => array(
					'openbadges_js',
					'obwp_plugin_url_mocked/dist/js/admin_openbadges.js',
					'jquery',
					1,
					true
				)
			)
		);

		WP_Mock::userFunction( 'wp_localize_script',
			array(
				'times' => 1,
				'args' => array(
					'openbadges_js',
					'adminPageData',
					array(
						'ajaxNonce' => 'a_nonce_for_this'
					)
				)
			)
		);

		$admin_menu = new Admin_Menu();
		$admin_menu->admin_enqueue_scripts_and_styles( 'a_hook');

		$this->assertConditionsMet();
	}

}
