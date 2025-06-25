<?php
/**
 * Plugin loader and autoloader
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Loader {

    /**
     * Plugin instance
     *
     * @var AI_Summary_Loader
     */
    private static $instance = null;

    /**
     * Plugin path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Get plugin instance
     *
     * @return AI_Summary_Loader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = dirname(dirname(__FILE__)) . '/';
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        
        $this->load_dependencies();
        $this->init_classes();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once $this->plugin_path . 'includes/class-ai-summary-core.php';
        require_once $this->plugin_path . 'includes/class-ai-summary-settings.php';
        require_once $this->plugin_path . 'includes/class-ai-summary-admin.php';
        require_once $this->plugin_path . 'includes/class-ai-summary-ajax.php';
        require_once $this->plugin_path . 'includes/class-ai-summary-frontend.php';
    }

    /**
     * Initialize classes
     */
    private function init_classes() {
        AI_Summary_Settings::init();
        AI_Summary_Ajax::init();
        
        if (is_admin()) {
            AI_Summary_Admin::init();
        } else {
            AI_Summary_Frontend::init();
        }
    }

    /**
     * Get plugin path
     *
     * @return string
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }
}
