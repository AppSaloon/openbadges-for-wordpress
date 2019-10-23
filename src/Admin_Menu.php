<?php

namespace appsaloon\obwp\settings;

/**
 * Class Admin_Menu
 *
 * Adds an menu to the backend based on a json config file
 * @package appsaloon\obwp\settings
 */
class Admin_Menu {

	/**
	 * Title for the main/status page
	 */
	const MAIN_MENU_PAGE_TITLE = 'OpenBadges Status Page';

	/**
	 * Menu title for the main/status page
	 */
	const MAIN_MENU_MENU_TITLE = 'OpenBadges';

	/**
	 * Slug for the main/status page
	 */
	const MAIN_MENU_MENU_SLUG = 'obwp_status_page';

	/**
	 * Required capability for the user to view the main/status page
	 */
	const MAIN_MENU_CAPABILITY = 'manage_options';

	/**
	 * Path to the template for the main/status page
	 */
	const MAIN_MENU_TEMPLATE_PATH = __DIR__ . '/../templates/admin-pages/admin_main_menu.php';


	/**
	 * Title for the configuration page
	 */
	const CONFIG_MENU_PAGE_TITLE = 'OpenBadges Configuration';

	/**
	 * Menu title for the configuration page
	 */
	const CONFIG_MENU_MENU_TITLE = 'Configuration';

	/**
	 * Slug for the configuration page
	 */
	const CONFIG_MENU_MENU_SLUG = 'obwp_configuration_page';

	/**
	 * Required capability for the user to view the configuration page
	 */
	const CONFIG_MENU_CAPABILITY = 'manage_options';

	/**
	 * Path to the template for the configuration page
	 */
	const CONFIG_MENU_TEMPLATE_PATH = __DIR__ . '/../templates/admin-pages/configuration.php';


	/**
	 * Hook for the configuration menu as returned by add_submenu_page function.
	 * Used to create nonce used by the JS call
	 * @var
	 */
	protected $config_menu_hook;

	/**
	 * Adds all the actions and filters for the Admin Menu
	 *
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_interface' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_and_styles' ) );
	}

    /**
     * Adds plugin pages to wp-admin menu
     */
    public function add_settings_interface() {
        add_menu_page(
            self::MAIN_MENU_PAGE_TITLE,
            self::MAIN_MENU_MENU_TITLE,
            self::MAIN_MENU_CAPABILITY,
            self::MAIN_MENU_MENU_SLUG,
            array( $this, 'display_main_admin_page' )
        );

        $this->config_menu_hook = add_submenu_page(
        	self::MAIN_MENU_MENU_SLUG,
			self::CONFIG_MENU_PAGE_TITLE,
			self::CONFIG_MENU_MENU_TITLE,
			self::CONFIG_MENU_CAPABILITY,
			self::CONFIG_MENU_MENU_SLUG,
			array( $this, 'display_config_page' )
		);
    }

    /**
     * Renders the main plugin page for wp-admin by use of template
     */
    public function display_main_admin_page() {
        include_once self::MAIN_MENU_TEMPLATE_PATH;
    }

	/**
	 * Renders the configuration page for wp-admin by use of template
	 */
    public function display_config_page() {
		include_once self::CONFIG_MENU_TEMPLATE_PATH;
	}

	/**
	 * @param $hook
	 */
	public function admin_enqueue_scripts_and_styles( $hook ) {
        $ajax_nonce = wp_create_nonce( $this->config_menu_hook );
        
        wp_register_style( 'openbadges_css', OBWP_PLUGIN_URL .'dist/css/admin-openbadges.css' );
        wp_enqueue_style( 'openbadges_css');
        wp_enqueue_script('openbadges_js', OBWP_PLUGIN_URL .'dist/js/admin_openbadges.js', 'jquery' , 1 , true );

        wp_localize_script(
            'openbadges_js',
            'adminPageData',
            array( 'ajaxNonce' => $ajax_nonce )
        );
    }
}