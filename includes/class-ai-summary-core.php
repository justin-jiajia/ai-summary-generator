<?php
/**
 * Core AI Summary functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AI_Summary_Core {

    /**
     * Generate AI summary for a post
     *
     * @param int $post_id Post ID
     * @param string|null $prompt_override Optional prompt override
     * @param int $max_retries Maximum number of retry attempts
     * @return bool Success status
     */
    public static function generate_summary($post_id, $prompt_override = null, $max_retries = 3) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $api_url = get_option('ai_summary_api_url');
        $api_token = get_option('ai_summary_api_token');
        $model_name = get_option('ai_summary_model_name');
        $prompt = $prompt_override ?? get_option('ai_summary_prompt', $post->post_content);

        if (!$api_url || !$api_token || !$model_name) {
            return false;
        }

        $request_body = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompt
                ],
                [
                    'role' => 'user',
                    'content' => 'Title: ' . $post->post_content
                ]
            ],
            'temperature' => 1.0,
            'top_p' => 1.0,
            'model' => $model_name,
        ];

        $request_args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_body),
            'timeout' => 30, // 30 second timeout
        ];

        // Retry logic
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $response = wp_remote_post($api_url, $request_args);

            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                // Success response codes
                if ($response_code >= 200 && $response_code < 300) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    $summary = $data['choices'][0]['message']['content'] ?? '';

                    if (!empty($summary)) {
                        update_post_meta($post_id, '_ai_summary', $summary);
                        update_post_meta($post_id, '_ai_summary_last_modified', $post->post_modified);
                        return true;
                    }
                }
                
                // Rate limit or server error - retry with backoff
                if ($response_code === 429 || ($response_code >= 500 && $response_code < 600)) {
                    if ($attempt < $max_retries) {
                        // Exponential backoff: 1s, 2s, 4s
                        $delay = pow(2, $attempt - 1);
                        sleep($delay);
                        continue;
                    }
                }
                
                // Client error (4xx) - don't retry
                if ($response_code >= 400 && $response_code < 500 && $response_code !== 429) {
                    break;
                }
            } else {
                // Network error - retry with backoff
                if ($attempt < $max_retries) {
                    $delay = pow(2, $attempt - 1);
                    sleep($delay);
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Clear AI summary for a post
     *
     * @param int $post_id Post ID
     * @return bool Success status
     */
    public static function clear_summary($post_id) {
        delete_post_meta($post_id, '_ai_summary');
        delete_post_meta($post_id, '_ai_summary_last_modified');
        return true;
    }

    /**
     * Get AI summary for a post
     *
     * @param int $post_id Post ID
     * @return string|false Summary content or false if not found
     */
    public static function get_summary($post_id) {
        return get_post_meta($post_id, '_ai_summary', true);
    }

    /**
     * Check if post has AI summary
     *
     * @param int $post_id Post ID
     * @return bool
     */
    public static function has_summary($post_id) {
        $summary = self::get_summary($post_id);
        return !empty($summary);
    }

    /**
     * Check if summary needs regeneration based on post modification date
     *
     * @param int $post_id Post ID
     * @return bool True if summary needs regeneration
     */
    public static function needs_regeneration($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $summary_last_modified = get_post_meta($post_id, '_ai_summary_last_modified', true);
        
        // If no last modified timestamp, summary needs regeneration
        if (empty($summary_last_modified)) {
            return true;
        }

        // Compare post modification time with summary last modified time
        return $post->post_modified !== $summary_last_modified;
    }

    /**
     * Get the maximum number of retry attempts from settings
     *
     * @return int Maximum retry attempts (default: 3)
     */
    public static function get_max_retries() {
        return (int) get_option('ai_summary_max_retries', 3);
    }

    /**
     * Generate AI summary with configurable retry attempts
     *
     * @param int $post_id Post ID
     * @param string|null $prompt_override Optional prompt override
     * @return bool Success status
     */
    public static function generate_summary_with_retries($post_id, $prompt_override = null) {
        $max_retries = self::get_max_retries();
        return self::generate_summary($post_id, $prompt_override, $max_retries);
    }
}
