<?php

namespace appsaloon\obfw\settings;

class Admin_Menu {

    protected $config;

    /**
     * Settings constructor.
     */
    public function __construct() {
        $config_string = file_get_contents( __DIR__ . '/../config/admin-pages/Admin_Menu.json' );
        $this->config = json_decode( $config_string  );

        add_action( 'admin_menu', array( $this, 'add_settings_interface' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Adds OBF settings page to Settings menu
     */
    public function add_settings_interface() {
        add_menu_page(
            $this->config->mainMenu->title,
            $this->config->mainMenu->menuTitle,
            $this->config->mainMenu->capability,
            $this->config->mainMenu->menuSlug,
            array( $this, 'display_main_admin_page' )
        );

        foreach( $this->config->subMenus as $identifier => $submenu_config ) {
            add_submenu_page(
                $this->config->mainMenu->menuSlug,
                $submenu_config->title,
                $submenu_config->menuTitle,
                $submenu_config->capability,
                $submenu_config->menuSlug,
                function() use ( $identifier ) {
                    include_once __DIR__ . '/../config/admin-pages/templates/' . $identifier . '.php';
                }
            );
        }

    }

    /**
     * Renders the OBF Settings page by use of template
     */
    public function display_main_admin_page() {
        include_once __DIR__ . '/../config/admin-pages/templates/Admin_Main_Menu.php';
    }

    /**
     * Adds the OBF options and their default values
     *
     */
    public function register_settings() {
        /**add_option( self::ALLOW_ADMIN_BAR_COLORIZATION_FOR_NON_APPSALOON_USERS,
            'on'
        );
        register_setting(
            self::MDP_OPTIONS_GROUP,
            self::ALLOW_ADMIN_BAR_COLORIZATION_FOR_NON_APPSALOON_USERS,
            array(
                'type' => 'boolean',
                'description' => 'If true shows non AppSaloon users also a colorized admin bar depending on the environment',
                'sanizitize_callback' => array( $this, 'sanitize_boolean' ),
                'show_in_rest' => false,
                'default' => 'on'
            )
        );*/
    }
}