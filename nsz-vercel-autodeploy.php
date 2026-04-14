<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function nsz_vercel_trigger_deployment() {
    // Prevent duplicate deployments within 60 seconds
    $transient_key = 'autodeploy_last_triggered';
    if (get_transient($transient_key)) {
        error_log("Autodeploy skipped: deployment already triggered recently");
        return;
    }

    if (($_SERVER['WP_ENV'] ?? '') !== 'development') {
        $url = get_option('nsz_vercel_deployment_webhook_url');
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Failed to call URL: $url, Error: $error_message");
        } else {
            // Set transient to prevent duplicates for 60 seconds
            set_transient($transient_key, true, 60);
            error_log("Autodeploy triggered successfully");
        }
    }
}

function nsz_vercel_handle_autodeploy_post_save($new_status, $old_status, $post) {
    // Skip if autodeploy is not enabled
    if (!get_option('nsz_vercel_enable_autodeploy', false) || empty(get_option('nsz_vercel_deployment_webhook_url'))) {
        return;
    }

    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post)) {
        return;
    }

    // Skip if both old and new status are draft
    if ($new_status === 'draft' && $old_status === 'draft') {
        return;
    }

    nsz_vercel_trigger_deployment();
}

function nsz_vercel_handle_autodeploy_options_save($post_id, $menu_slug) {
    // Skip if autodeploy is not enabled
    if (!get_option('nsz_vercel_enable_autodeploy', false) || empty(get_option('nsz_vercel_deployment_webhook_url'))) {
        return;
    }

    nsz_vercel_trigger_deployment();
}

add_action('transition_post_status', 'nsz_vercel_handle_autodeploy_post_save', 10, 3);
add_action('acf/options_page/save', 'nsz_vercel_handle_autodeploy_options_save', 10, 2);
