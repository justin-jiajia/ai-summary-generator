<?php
/**
 * Settings page functionality for AI Summary Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Settings {

    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_options_page(
            'AI Summary Settings',
            'AI Summary',
            'manage_options',
            'ai-summary-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Summary Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_summary_settings');
                do_settings_sections('ai-summary-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings and fields
     */
    public static function register_settings() {
        // Register settings with sanitization callbacks
        register_setting('ai_summary_settings', 'ai_summary_api_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('ai_summary_settings', 'ai_summary_api_token', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        register_setting('ai_summary_settings', 'ai_summary_model_name', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        register_setting('ai_summary_settings', 'ai_summary_prompt', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        register_setting('ai_summary_settings', 'ai_summary_custom_css', array(
            'type' => 'string',
            'sanitize_callback' => array(__CLASS__, 'sanitize_css'),
            'default' => ''
        ));
        register_setting('ai_summary_settings', 'ai_summary_homepage_override', array(
            'type' => 'string',
            'sanitize_callback' => array(__CLASS__, 'sanitize_yes_no'),
            'default' => 'no'
        ));
        register_setting('ai_summary_settings', 'ai_summary_update_on_post_update', array(
            'type' => 'string',
            'sanitize_callback' => array(__CLASS__, 'sanitize_yes_no'),
            'default' => 'no'
        ));
        register_setting('ai_summary_settings', 'ai_summary_max_retries', array(
            'type' => 'integer',
            'sanitize_callback' => array(__CLASS__, 'sanitize_retry_count'),
            'default' => 3
        ));

        // Add settings section
        add_settings_section(
            'ai_summary_main_section',
            'API Configuration',
            null,
            'ai-summary-settings'
        );

        // Add settings fields
        add_settings_field(
            'ai_summary_api_url',
            'API URL',
            array(__CLASS__, 'api_url_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_api_token',
            'API Token',
            array(__CLASS__, 'api_token_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_model_name',
            'Model Name',
            array(__CLASS__, 'model_name_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_prompt',
            'Prompt',
            array(__CLASS__, 'prompt_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_custom_css',
            'Custom CSS',
            array(__CLASS__, 'custom_css_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_homepage_override',
            'Override Homepage Summary',
            array(__CLASS__, 'homepage_override_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_update_on_post_update',
            'Update Summary on Post Update',
            array(__CLASS__, 'update_on_post_update_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );

        add_settings_field(
            'ai_summary_max_retries',
            'Max Retry Attempts',
            array(__CLASS__, 'max_retries_callback'),
            'ai-summary-settings',
            'ai_summary_main_section'
        );
    }

    /**
     * Settings field callbacks
     */
    public static function api_url_callback() {
        $value = get_option('ai_summary_api_url', '');
        echo '<input type="text" name="ai_summary_api_url" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Enter the API endpoint URL for your AI service (e.g., https://api.openai.com/v1/chat/completions)</p>';
    }

    public static function api_token_callback() {
        $value = get_option('ai_summary_api_token', '');
        echo '<input type="text" name="ai_summary_api_token" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Your API authentication token or key. Keep this secure and do not share it publicly.</p>';
    }

    public static function model_name_callback() {
        $value = get_option('ai_summary_model_name', '');
        echo '<input type="text" name="ai_summary_model_name" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Specify the AI model to use (e.g., gpt-3.5-turbo, gpt-4, claude-3, etc.)</p>';
    }

    public static function prompt_callback() {
        $value = get_option('ai_summary_prompt', '');
        echo '<input type="text" name="ai_summary_prompt" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">Custom system prompt for generating summaries.</p>';
    }

    public static function custom_css_callback() {
        $value = get_option('ai_summary_custom_css', '');
        echo '<textarea name="ai_summary_custom_css" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Add custom CSS styles to customize the appearance of AI summaries on your site.</p>';
    }

    public static function homepage_override_callback() {
        $value = get_option('ai_summary_homepage_override', 'no');
        echo '<select name="ai_summary_homepage_override">
                <option value="no"' . selected($value, 'no', false) . '>No</option>
                <option value="yes"' . selected($value, 'yes', false) . '>Yes</option>
              </select>';
        echo '<p class="description">Enable this to display AI summaries on your homepage instead of full post content. <strong>Note:</strong> This may not work with all themes as it depends on the theme calling <code>get_the_excerpt()</code>. If this doesn\'t work, use <code>ai_summary_get_summary($post_id)</code> function in your theme templates.</p>';
    }

    public static function update_on_post_update_callback() {
        $value = get_option('ai_summary_update_on_post_update', 'no');
        echo '<select name="ai_summary_update_on_post_update">
                <option value="no"' . selected($value, 'no', false) . '>No</option>
                <option value="yes"' . selected($value, 'yes', false) . '>Yes</option>
              </select>';
        echo '<p class="description">Automatically regenerate AI summary when a post is updated or modified.</p>';
    }

    public static function max_retries_callback() {
        $value = get_option('ai_summary_max_retries', 3);
        echo '<select name="ai_summary_max_retries">
                <option value="1"' . selected($value, 1, false) . '>1 (No Retry)</option>
                <option value="2"' . selected($value, 2, false) . '>2</option>
                <option value="3"' . selected($value, 3, false) . '>3 (Default)</option>
                <option value="4"' . selected($value, 4, false) . '>4</option>
                <option value="5"' . selected($value, 5, false) . '>5</option>
              </select>';
        echo '<p class="description">Number of retry attempts for failed API requests. Uses exponential backoff (1s, 2s, 4s, etc.)</p>';
    }

    /**
     * Sanitization callbacks
     */
    
    /**
     * Sanitize CSS input
     */
    public static function sanitize_css($input) {
        // Basic CSS sanitization - remove script tags and dangerous functions
        $input = wp_strip_all_tags($input);
        $input = str_replace(array('javascript:', 'expression(', 'behavior:'), '', $input);
        return sanitize_textarea_field($input);
    }

    /**
     * Sanitize yes/no values
     */
    public static function sanitize_yes_no($input) {
        return in_array($input, array('yes', 'no'), true) ? $input : 'no';
    }

    /**
     * Sanitize retry count
     */
    public static function sanitize_retry_count($input) {
        $value = intval($input);
        return ($value >= 1 && $value <= 5) ? $value : 3;
    }
}
