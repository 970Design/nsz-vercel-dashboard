<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Cancel any active (BUILDING / QUEUED / INITIALIZING) deployments for the
 * configured Vercel project. Used to prevent a backlog of auto-deploys when
 * pages are being edited quickly — a new autodeploy supersedes any in-flight
 * one that hasn't finished building yet.
 *
 * Silently no-ops if the Vercel API token or project ID are not configured
 * (the webhook itself still fires — this just skips the cancel step).
 */
function nsz_vercel_cancel_active_deployments() {
    $api_token = nsz_decrypt_value(get_option('nsz_vercel_api_key', ''));
    $project_id = get_option('nsz_vercel_project_id', '');

    if (empty($api_token) || empty($project_id)) {
        error_log('Autodeploy cancel skipped: Vercel API token or project ID not configured');
        return;
    }

    $list_url = add_query_arg(
        [
            'projectId' => $project_id,
            'state'     => 'BUILDING,QUEUED,INITIALIZING',
            'limit'     => 20,
        ],
        'https://api.vercel.com/v6/deployments'
    );

    $response = wp_remote_get($list_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_token,
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        error_log('Autodeploy cancel: failed to fetch active deployments — ' . $response->get_error_message());
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body) || empty($body['deployments'])) {
        return;
    }

    foreach ($body['deployments'] as $deployment) {
        if (empty($deployment['uid'])) {
            continue;
        }

        $cancel_url = 'https://api.vercel.com/v12/deployments/' . rawurlencode($deployment['uid']) . '/cancel';
        $cancel_response = wp_remote_request($cancel_url, [
            'method'  => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($cancel_response)) {
            error_log('Autodeploy cancel: failed to cancel ' . $deployment['uid'] . ' — ' . $cancel_response->get_error_message());
        } else {
            $status = wp_remote_retrieve_response_code($cancel_response);
            if ($status >= 200 && $status < 300) {
                error_log('Autodeploy cancel: canceled in-flight deployment ' . $deployment['uid']);
            } else {
                error_log('Autodeploy cancel: unexpected status ' . $status . ' cancelling ' . $deployment['uid']);
            }
        }
    }
}

function nsz_vercel_trigger_deployment() {
    // Prevent duplicate deployments within 60 seconds
    $transient_key = 'autodeploy_last_triggered';
    if (get_transient($transient_key)) {
        error_log("Autodeploy skipped: deployment already triggered recently");
        return;
    }

    if (($_SERVER['WP_ENV'] ?? '') === 'development') {
        return;
    }

    $url = get_option('nsz_vercel_deployment_webhook_url');
    if (empty($url)) {
        return;
    }

    // Cancel any in-flight deployments so the latest edit supersedes them
    // instead of queueing behind them (prevents a backlog when edits arrive
    // faster than a build takes to complete).
    nsz_vercel_cancel_active_deployments();

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
