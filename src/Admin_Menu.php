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
	 * Set through the constructor by file path, it contains the information from the config file
	 *
	 * @var object
	 */
	private $config;

	/**
	 *
	 * @var
	 */
	private $main_menu_hook;

	/**
	 * Array of all hooks of the submenus
	 *
	 * @var array
	 */
	private $submenu_hooks = array();

	/**
	 * The url to the plugin directory
	 *
	 * @var
	 */
	private $plugin_url;

    /**
     * Constructor.
	 *
	 * @param string $plugin_url
	 * @param string $config_file_path
     */
    public function __construct( $plugin_url, $config_file_path ) {
    	$this->plugin_url = $plugin_url;
        $config_string = file_get_contents( $config_file_path );
        $this->config = json_decode( $config_string  );
    }

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
        $this->main_menu_hook = add_menu_page(
            $this->config->mainMenu->title,
            $this->config->mainMenu->menuTitle,
            $this->config->mainMenu->capability,
            $this->config->mainMenu->menuSlug,
            array( $this, 'display_main_admin_page' )
        );

        foreach( $this->config->subMenus as $identifier => $submenu_config ) {
            $submenu_hook = add_submenu_page(
                $this->config->mainMenu->menuSlug,
                $submenu_config->title,
                $submenu_config->menuTitle,
                $submenu_config->capability,
                $submenu_config->menuSlug,
                function() use ( $identifier ) {
                    include_once __DIR__ . '/../templates/admin-pages/' . $identifier . '.php';
                }
            );
            $this->submenu_hooks[$submenu_hook] = $identifier;
        }
    }

    /**
     * Renders the main plugin page for wp-admin by use of template
     */
    public function display_main_admin_page() {
        include_once __DIR__ . '/../templates/admin-pages/admin_main_menu.php';
    }

	/**
	 * @param $hook
	 */
	public function admin_enqueue_scripts_and_styles( $hook ) {
        $script_handle = $hook . strtolower( static::class ) . '_js';
        $ajax_nonce = wp_create_nonce($this->submenu_hooks[$hook] );
        
        wp_register_style( 'openbadges_css', $this->plugin_url .'dist/css/admin-openbadges.css');
        wp_enqueue_style( 'openbadges_css');
        wp_enqueue_script('openbadges_js', $this->plugin_url .'dist/js/admin_openbadges.js', 'jquery' , 1 , true );

        wp_localize_script(
            'openbadges_js',
            'adminPageData',
            array( 'ajaxNonce' => $ajax_nonce )
        );
    }
}