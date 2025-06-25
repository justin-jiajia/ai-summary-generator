<?php
/**
 * AJAX handlers for AI Summary Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Ajax {

    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        add_action('wp_ajax_generate_ai_summary', array(__CLASS__, 'generate_summary'));
        add_action('wp_ajax_regenerate_ai_summary', array(__CLASS__, 'regenerate_summary'));
        add_action('wp_ajax_clear_ai_summary', array(__CLASS__, 'clear_summary'));
    }

    /**
     * Clear previous notice parameters from URL
     *
     * @param string $url URL to clean
     * @return string Cleaned URL
     */
    private static function clear_previous_notices($url) {
        $notice_params = array('single_generated', 'single_regenerated', 'single_cleared', 'single_error', 'bulk_generated', 'bulk_errors', 'bulk_skipped');
        
        foreach ($notice_params as $param) {
            $url = remove_query_arg($param, $url);
        }
        
        return $url;
    }

    /**
     * Handle AJAX request for generating summary
     * If summary exists, regenerate it. If not, generate new one.
     */
    public static function generate_summary() {
        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ai_summary_action')) {
            wp_redirect(add_query_arg('single_error', urlencode('Security check failed.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_redirect(add_query_arg('single_error', urlencode('Insufficient permissions.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Validate and sanitize post ID
        if (!isset($_GET['post_id'])) {
            wp_redirect(add_query_arg('single_error', urlencode('Missing post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        $post_id = intval($_GET['post_id']);

        // Validate post ID
        if (!$post_id || !get_post($post_id)) {
            wp_redirect(add_query_arg('single_error', urlencode('Invalid post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Check if summary already exists
        $existing_summary = AI_Summary_Core::get_summary($post_id);
        
        if ($existing_summary) {
            // Summary exists, regenerate it
            if (AI_Summary_Core::generate_summary($post_id)) {
                wp_redirect(add_query_arg('single_regenerated', 1, self::clear_previous_notices(admin_url('edit.php'))));
            } else {
                wp_redirect(add_query_arg('single_error', urlencode('Failed to regenerate AI summary. Please check your API settings and try again.'), self::clear_previous_notices(admin_url('edit.php'))));
            }
        } else {
            // No summary exists, generate new one
            if (AI_Summary_Core::generate_summary($post_id)) {
                wp_redirect(add_query_arg('single_generated', 1, self::clear_previous_notices(admin_url('edit.php'))));
            } else {
                wp_redirect(add_query_arg('single_error', urlencode('Failed to generate AI summary. Please check your API settings and try again.'), self::clear_previous_notices(admin_url('edit.php'))));
            }
        }
        exit;
    }

    /**
     * Handle AJAX request for regenerating summary
     */
    public static function regenerate_summary() {
        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ai_summary_action')) {
            wp_redirect(add_query_arg('single_error', urlencode('Security check failed.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_redirect(add_query_arg('single_error', urlencode('Insufficient permissions.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Validate and sanitize post ID
        if (!isset($_GET['post_id'])) {
            wp_redirect(add_query_arg('single_error', urlencode('Missing post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        $post_id = intval($_GET['post_id']);

        // Validate post ID
        if (!$post_id || !get_post($post_id)) {
            wp_redirect(add_query_arg('single_error', urlencode('Invalid post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        if (AI_Summary_Core::generate_summary($post_id)) {
            wp_redirect(add_query_arg('single_regenerated', 1, self::clear_previous_notices(admin_url('edit.php'))));
        } else {
            wp_redirect(add_query_arg('single_error', urlencode('Failed to regenerate AI summary. Please check your API settings and try again.'), self::clear_previous_notices(admin_url('edit.php'))));
        }
        exit;
    }

    /**
     * Handle AJAX request for clearing summary
     * If summary exists, clear it. If not, generate new one.
     */
    public static function clear_summary() {
        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ai_summary_action')) {
            wp_redirect(add_query_arg('single_error', urlencode('Security check failed.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_redirect(add_query_arg('single_error', urlencode('Insufficient permissions.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Validate and sanitize post ID
        if (!isset($_GET['post_id'])) {
            wp_redirect(add_query_arg('single_error', urlencode('Missing post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        $post_id = intval($_GET['post_id']);

        // Validate post ID
        if (!$post_id || !get_post($post_id)) {
            wp_redirect(add_query_arg('single_error', urlencode('Invalid post ID.'), self::clear_previous_notices(admin_url('edit.php'))));
            exit;
        }

        // Check if summary already exists
        $existing_summary = AI_Summary_Core::get_summary($post_id);
        
        if ($existing_summary) {
            // Summary exists, clear it
            AI_Summary_Core::clear_summary($post_id);
            wp_redirect(add_query_arg('single_cleared', 1, self::clear_previous_notices(admin_url('edit.php'))));
        } else {
            // No summary exists, generate new one
            if (AI_Summary_Core::generate_summary($post_id)) {
                wp_redirect(add_query_arg('single_generated', 1, self::clear_previous_notices(admin_url('edit.php'))));
            } else {
                wp_redirect(add_query_arg('single_error', urlencode('Failed to generate AI summary. Please check your API settings and try again.'), self::clear_previous_notices(admin_url('edit.php'))));
            }
        }
        exit;
    }
}
