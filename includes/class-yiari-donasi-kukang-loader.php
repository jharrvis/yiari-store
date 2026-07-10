<?php

/**
 * Main loader class for YIARI Donasi Kukang Plugin
 * 
 * This class is responsible for loading all plugin modules and initializing the system
 */
class YIARI_Donasi_Kukang_Loader {

    /**
     * The unique identifier of this plugin.
     *
     * @since    3.1.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    3.1.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    3.1.0
     */
    public function __construct() {
        if (defined('YIARI_DONASI_KUKANG_VERSION')) {
            $this->version = YIARI_DONASI_KUKANG_VERSION;
        } else {
            $this->version = '3.1.1';
        }
        $this->plugin_name = 'yiari-donasi-kukang';
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    3.1.0
     * @access   private
     */
    private function set_locale() {
        // Load plugin text domain for translations
        add_action('init', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    3.1.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'yiari-donasi-kukang',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    3.1.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Initialize admin module
        if (is_admin()) {
            $admin_module = new YIARI_Admin_Module();
            $admin_module->initialize();
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    3.1.0
     * @access   private
     */
    private function define_public_hooks() {
        // Initialize public module
        $public_module = new YIARI_Public_Module();
        $public_module->initialize();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    3.1.0
     */
    public function run() {
        $this->set_locale();

        // Load dependencies first before defining hooks
        $this->load_dependencies();

        $this->define_admin_hooks();
        $this->define_public_hooks();

        // Initialize core modules
        add_action('plugins_loaded', array($this, 'initialize_core_modules'));
    }
    
    /**
     * Initialize core modules after plugins are loaded
     *
     * @since    3.1.0
     */
    public function initialize_core_modules() {
        // Dependencies already loaded in run() method

        // Initialize database
        $database_manager = new YIARI_Database_Manager();
        $database_manager->initialize();

        // Initialize currency system
        $currency_manager = new YIARI_Currency_Manager();
        $currency_manager->initialize();

        // Initialize shipping system
        $shipping_manager = new YIARI_Shipping_Manager();
        $shipping_manager->initialize();

        // Initialize payment system
        $payment_manager = new YIARI_Payment_Manager();
        $payment_manager->initialize();

        // Initialize form system
        $form_manager = new YIARI_Form_Manager();
        $form_manager->initialize();

        // Initialize email system
        $email_manager = new YIARI_Email_Manager();
        $email_manager->initialize();
    }
    
    /**
     * Load all dependencies required by the plugin
     *
     * @since    3.1.0
     */
    private function load_dependencies() {
        // Load required core modules
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-database-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-currency-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-shipping-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-payment-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-form-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-email-manager.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-admin-module.php';
        require_once YIARI_DONASI_KUKANG_PATH . 'modules/class-yiari-public-module.php';
        
        // Load helpers
        if (file_exists(YIARI_DONASI_KUKANG_PATH . 'helpers/functions.php')) {
            require_once YIARI_DONASI_KUKANG_PATH . 'helpers/functions.php';
        }
        if (file_exists(YIARI_DONASI_KUKANG_PATH . 'helpers/ajax-handlers.php')) {
            require_once YIARI_DONASI_KUKANG_PATH . 'helpers/ajax-handlers.php';
        }
    }
}
?>