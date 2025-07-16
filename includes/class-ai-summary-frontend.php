<?php
/**
 * Frontend display functionality for AI Summary Plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Frontend {

    /**
     * Initialize frontend functionality
     */
    public static function init() {
        add_filter('the_content', array(__CLASS__, 'display_summary'));
        add_filter('get_the_excerpt', array(__CLASS__, 'override_homepage_summary'));
    }

    /**
     * Display AI summary below post content
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public static function display_summary($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            $summary = AI_Summary_Core::get_summary(get_the_ID());
            if ($summary) {
                $custom_css = get_option('ai_summary_custom_css', '');
                $summary_html = '<div class="ai-summary" style="' . esc_attr($custom_css) . '"><blockquote class="wp-block-quote"><p><strong>AI Summary</strong></p><p>' . esc_html($summary) . '</p></blockquote></div>';
                $content = $summary_html . $content;
            }
        }
        return $content;
    }

    /**
     * Override homepage summary
     *
     * @param string $excerpt Post excerpt
     * @return string Modified excerpt
     */
    public static function override_homepage_summary($excerpt) {
        if ((is_home() || is_front_page()) && in_the_loop() && is_main_query()) {
            $override = get_option('ai_summary_homepage_override', 'no');
            if ($override === 'yes') {
                $summary = AI_Summary_Core::get_summary(get_the_ID());
                if ($summary) {
                    return '<p>' . esc_html($summary) . '</p>';
                }
            }
        }
        return $excerpt;
    }

    /**
     * Get AI summary for a specific post (for theme developers)
     *
     * @param int $post_id Post ID
     * @param bool $formatted Whether to return formatted HTML or plain text
     * @return string|false AI summary or false if not available
     */
    public static function get_post_summary($post_id = null, $formatted = false) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!$post_id) {
            return false;
        }

        $summary = AI_Summary_Core::get_summary($post_id);
        
        if (!$summary) {
            return false;
        }

        if ($formatted) {
            $custom_css = get_option('ai_summary_custom_css', '');
            return '<div class="ai-summary" style="' . esc_attr($custom_css) . '"><blockquote class="wp-block-quote"><p><strong>AI Summary</strong></p><p>' . esc_html($summary) . '</p></blockquote></div>';
        }

        return $summary;
    }
}
