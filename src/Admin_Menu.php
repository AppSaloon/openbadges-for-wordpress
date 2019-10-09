<?php

namespace appsaloon\obwp\settings;

class Admin_Menu {

    protected $config;
    protected $main_menu_hook;
    protected $submenu_hooks = array();
    protected $plugin_url;

    /**
     * Settings constructor.
     */
    public function __construct( $plugin_url ) {
        $this->plugin_url = $plugin_url;
        $config_string = file_get_contents( __DIR__ . '/../config/admin-pages/admin_menu.json' );
        $this->config = json_decode( $config_string  );

        add_action( 'admin_menu', array( $this, 'add_settings_interface' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
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
                    include_once __DIR__ . '/../config/admin-pages/templates/' . $identifier . '.php';
                }
            );
            $this->submenu_hooks[$submenu_hook] = $identifier;
        }

    }

    /**
     * Renders the main plugin page for wp-admin by use of template
     */
    public function display_main_admin_page() {
        include_once __DIR__ . '/../config/admin-pages/templates/admin_main_Menu.php';
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

    public function admin_enqueue_scripts_and_styles( $hook ) {
        $script_handle = $hook . strtolower( static::class ) . '_js';
        $ajax_nonce = wp_create_nonce($this->submenu_hooks[$hook] );
        
        wp_register_style( 'openbadges_css', $this->plugin_url.'files/css/admin-openbadges.css');
        wp_enqueue_style( 'openbadges_css');
        wp_enqueue_script('openbadges_js', $this->plugin_url.'files/js/admin_openbadges.js', 'jquery' , 1 , true );

        wp_localize_script(
            'openbadges_js',
            'adminPageData',
            array( 'ajaxNonce' => $ajax_nonce )
        );
        // if( in_array( $hook, array_keys( $this->submenu_hooks ) ) ) {
        //     $this->load_submenu_page_script( $hook );
        //     $this->load_submenu_page_style( $hook );
        // } elseif( $hook === $this->main_menu_hook ) {
        //     $this->load_main_menu_page_scripts();
        //     $this->load_main_menu_page_style();
        // }

    }

    // private function load_submenu_page_script( $hook ) {
    //     if( file_exists( __DIR__ . '/../js/admin-pages/' . $this->submenu_hooks[$hook] . '.js') ) {
    //         if( $this->config->subMenus->{$this->submenu_hooks[$hook]}->enableJquery ) {
    //             wp_enqueue_script( "jquery" );
    //          }

    //         $script_handle = $hook . strtolower( static::class ) . '_js';

    //         wp_enqueue_script(
    //             $script_handle,
    //             $this->plugin_url . './js/admin-pages/' . $this->submenu_hooks[$hook] . '.js'
    //         );
    //         $ajax_nonce = wp_create_nonce($this->submenu_hooks[$hook] );

    //         wp_localize_script(
    //             $script_handle,
    //             'adminPageData',
    //             array( 'ajaxNonce' => $ajax_nonce )
    //         );
    //     }
    // }

    // private function load_submenu_page_style( $hook ) {
    //     if( file_exists( __DIR__ . '/../css/admin-pages/' . $this->submenu_hooks[$hook] . '.css') ) {
    //         wp_enqueue_style(
    //             $hook . strtolower( static::class ) . '_css',
    //             $this->plugin_url . './css/admin-pages/' . $this->submenu_hooks[$hook] . '.css'
    //         );
    //     }
    // }

    // private function load_main_menu_page_scripts(  ) {
    //     if( file_exists( __DIR__ . '/../js/admin-pages/admin_main_menu.js') ) {
    //         if( $this->config->mainMenu->enableJquery ) {
    //             wp_enqueue_script( "jquery" );
    //         }

    //         $script_handle = $this->main_menu_hook . strtolower( static::class ) . '_js';

    //         wp_enqueue_script(
    //             $script_handle,
    //             $this->plugin_url . './js/admin-pages/admin_main_menu.js'
    //         );
    //         $ajax_nonce = wp_create_nonce( 'admin_main_menu' );

    //         wp_localize_script(
    //             $script_handle,
    //             'adminPageData
    //             ',
    //             array( 'ajaxNonce' => $ajax_nonce )
    //         );
    //     }
    // }

    // private function load_main_menu_page_style() {
    //     if( file_exists( __DIR__ . '/../css/admin-pages/admin_main_menu.css') ) {
    //         wp_enqueue_style(
    //             $this->main_menu_hook . strtolower( static::class ) . '_css',
    //             $this->plugin_url . './css/admin-pages/admin_main_menu.css'
    //         );
    //     }
    // }
}