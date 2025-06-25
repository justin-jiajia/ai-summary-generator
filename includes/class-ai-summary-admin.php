<?php
/**
 * Admin interface functionality for AI Summary Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Admin {

    /**
     * Initialize admin functionality
     */
    public static function init() {
        add_action('edit_form_after_title', array(__CLASS__, 'add_post_editor_button'));
        add_filter('post_row_actions', array(__CLASS__, 'add_post_list_buttons'), 10, 2);
        add_filter('bulk_actions-edit-post', array(__CLASS__, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-post', array(__CLASS__, 'handle_bulk_action'), 10, 3);
        add_action('admin_notices', array(__CLASS__, 'admin_notices'));
        add_action('save_post', array(__CLASS__, 'update_on_post_save'));
    }

    /**
     * Add button to post editor
     * Show different button text based on whether summary exists
     */
    public static function add_post_editor_button() {
        global $post;
        if ($post && $post->post_type === 'post') {
            $has_summary = AI_Summary_Core::has_summary($post->ID);
            $needs_regen = AI_Summary_Core::needs_regeneration($post->ID);
            
            $url = wp_nonce_url(
                admin_url('admin-ajax.php?action=generate_ai_summary&post_id=' . $post->ID),
                'ai_summary_action'
            );
            
            if ($has_summary) {
                if ($needs_regen) {
                    echo '<a href="' . esc_url($url) . '" class="button button-primary">Regenerate AI Summary (Outdated)</a>';
                } else {
                    echo '<a href="' . esc_url($url) . '" class="button">Regenerate AI Summary</a>';
                }
            } else {
                echo '<a href="' . esc_url($url) . '" class="button button-primary">Generate AI Summary</a>';
            }
        }
    }

    /**
     * Add action buttons to post list
     * Show different buttons based on whether summary exists
     *
     * @param array $actions Existing actions
     * @param WP_Post $post Post object
     * @return array Modified actions
     */
    public static function add_post_list_buttons($actions, $post) {
        if ($post->post_type === 'post') {
            $has_summary = AI_Summary_Core::has_summary($post->ID);
            $needs_regen = AI_Summary_Core::needs_regeneration($post->ID);
            
            $generate_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=generate_ai_summary&post_id=' . $post->ID),
                'ai_summary_action'
            );
            $regenerate_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=regenerate_ai_summary&post_id=' . $post->ID),
                'ai_summary_action'
            );
            $clear_url = wp_nonce_url(
                admin_url('admin-ajax.php?action=clear_ai_summary&post_id=' . $post->ID),
                'ai_summary_action'
            );

            if ($has_summary) {
                // Show regenerate and clear options
                if ($needs_regen) {
                    $actions['regenerate_ai_summary'] = '<a href="' . esc_url($regenerate_url) . '" style="color: #d63638;">Regenerate AI Summary (Outdated)</a>';
                } else {
                    $actions['regenerate_ai_summary'] = '<a href="' . esc_url($regenerate_url) . '">Regenerate AI Summary</a>';
                }
                $actions['clear_ai_summary'] = '<a href="' . esc_url($clear_url) . '">Clear AI Summary</a>';
            } else {
                // Show generate option
                $actions['generate_ai_summary'] = '<a href="' . esc_url($generate_url) . '">Generate AI Summary</a>';
            }
        }
        return $actions;
    }

    /**
     * Add bulk action for generating summaries
     * Generates for posts without summaries and regenerates outdated ones
     *
     * @param array $bulk_actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public static function add_bulk_actions($bulk_actions) {
        $bulk_actions['generate_ai_summaries'] = 'Generate/Update AI Summaries';
        return $bulk_actions;
    }

    /**
     * Handle bulk action
     * Generate summaries for posts without them and regenerate outdated ones
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being performed
     * @param array $post_ids Array of post IDs
     * @return string Modified redirect URL
     */
    public static function handle_bulk_action($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'generate_ai_summaries') {
            return $redirect_to;
        }

        // Clear any existing notice parameters
        $notice_params = array('single_generated', 'single_regenerated', 'single_cleared', 'single_error', 'bulk_generated', 'bulk_errors', 'bulk_skipped');
        foreach ($notice_params as $param) {
            $redirect_to = remove_query_arg($param, $redirect_to);
        }

        $generated_count = 0;
        $error_count = 0;
        $skipped_count = 0;
        
        foreach ($post_ids as $post_id) {
            $has_summary = AI_Summary_Core::has_summary($post_id);
            $needs_regen = AI_Summary_Core::needs_regeneration($post_id);
            
            // Generate if no summary exists OR if summary is outdated
            if (!$has_summary || $needs_regen) {
                if (AI_Summary_Core::generate_summary($post_id)) {
                    $generated_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Summary exists and is current
                $skipped_count++;
            }
        }

        // Add appropriate notice
        if ($error_count > 0 && $generated_count === 0) {
            $redirect_to = add_query_arg('single_error', urlencode("Failed to generate any AI summaries. Please check your API settings."), $redirect_to);
        } else {
            $redirect_to = add_query_arg('bulk_generated', $generated_count, $redirect_to);
            if ($error_count > 0) {
                $redirect_to = add_query_arg('bulk_errors', $error_count, $redirect_to);
            }
            if ($skipped_count > 0) {
                $redirect_to = add_query_arg('bulk_skipped', $skipped_count, $redirect_to);
            }
        }

        return $redirect_to;
    }

    /**
     * Display admin notices
     */
    public static function admin_notices() {
        // Error notice (highest priority)
        
        if (!empty($_GET['single_error'])) {
            
            $error_message = sanitize_text_field(urldecode(wp_unslash($_GET['single_error'])));
            printf('<div id="message" class="error notice notice-error is-dismissible"><p><strong>Error:</strong> %s</p></div>', esc_html($error_message));
            return; // Don't show other notices when there's an error
        }

        // Success notices
        
        if (isset($_GET['bulk_generated'])) {
            
            $count = intval($_GET['bulk_generated']);
            
            $error_count = !empty($_GET['bulk_errors']) ? intval($_GET['bulk_errors']) : 0;
            
            $skipped_count = !empty($_GET['bulk_skipped']) ? intval($_GET['bulk_skipped']) : 0;
            
            if ($count > 0) {
                $message = sprintf('Successfully generated %d AI %s.', $count, $count === 1 ? 'summary' : 'summaries');
                if ($error_count > 0) {
                    $message .= sprintf(' %d %s failed.', $error_count, $error_count === 1 ? 'request' : 'requests');
                }
                if ($skipped_count > 0) {
                    $message .= sprintf(' %d %s already up-to-date.', $skipped_count, $skipped_count === 1 ? 'summary' : 'summaries');
                }
                printf('<div id="message" class="updated notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
            } else if ($skipped_count > 0) {
                printf('<div id="message" class="notice notice-info is-dismissible"><p>No summaries generated. All %d selected %s already up-to-date.</p></div>', 
                    esc_html($skipped_count), 
                    esc_html($skipped_count === 1 ? 'summary is' : 'summaries are')
                );
            }
            // Note: If both $count and $skipped_count are 0, it means only errors occurred
            // and that case is handled above with the single_error message
        }

        
        if (!empty($_GET['single_generated'])) {
            printf('<div id="message" class="updated notice notice-success is-dismissible"><p>AI summary generated successfully.</p></div>');
        }
        
        if (!empty($_GET['single_regenerated'])) {
            printf('<div id="message" class="updated notice notice-success is-dismissible"><p>AI summary regenerated successfully.</p></div>');
        }
        
        if (!empty($_GET['single_cleared'])) {
            printf('<div id="message" class="updated notice notice-success is-dismissible"><p>AI summary cleared successfully.</p></div>');
        }
    }

    /**
     * Update summary when post is saved
     * Only regenerate if summary exists and needs regeneration
     *
     * @param int $post_id Post ID
     */
    public static function update_on_post_save($post_id) {
        $update_enabled = get_option('ai_summary_update_on_post_update', 'no');
        if ($update_enabled !== 'yes') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post = get_post($post_id);
        if ($post->post_type !== 'post') {
            return;
        }

        // Only regenerate if summary exists and needs regeneration
        if (AI_Summary_Core::has_summary($post_id) && AI_Summary_Core::needs_regeneration($post_id)) {
            AI_Summary_Core::generate_summary($post_id);
        }
    }
}
