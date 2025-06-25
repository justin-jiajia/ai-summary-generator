<?php

/*
 * Plugin Name:       AI Summary Generator
 * Plugin URI:        https://github.com/justin-jiajia/ai-summary
 * Description:       Generate AI summaries for posts.
 * Version:           1.0
 * Author:            Justin Jiajia
 * Author URI:        https://hijiajia.top/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-summary-generator
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_SUMMARY_PLUGIN_FILE', __FILE__);
define('AI_SUMMARY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AI_SUMMARY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the plugin loader
require_once AI_SUMMARY_PLUGIN_PATH . 'includes/class-ai-summary-loader.php';

// Initialize the plugin
function ai_summary_init() {
    AI_Summary_Loader::get_instance();
}

// Hook into WordPress
add_action('plugins_loaded', 'ai_summary_init');

// Register uninstall hook
register_uninstall_hook(__FILE__, 'ai_summary_uninstall');

/**
 * Clean up plugin data on uninstall
 */
function ai_summary_uninstall() {
    // Delete all AI summary meta data using WordPress functions
    delete_post_meta_by_key('_ai_summary');
    delete_post_meta_by_key('_ai_summary_last_modified');
    
    // Delete plugin options
    delete_option('ai_summary_api_url');
    delete_option('ai_summary_api_token');
    delete_option('ai_summary_model_name');
    delete_option('ai_summary_prompt');
    delete_option('ai_summary_custom_css');
    delete_option('ai_summary_homepage_override');
    delete_option('ai_summary_update_on_post_update');
    delete_option('ai_summary_max_retries');
    
    // Clean up any cached data
    wp_cache_flush();
}

