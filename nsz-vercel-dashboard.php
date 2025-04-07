<?php
/**
 * Vercel Dashboard
 *
 * Plugin Name: 970 Design Vercel Dashboard
 * Plugin URI:  https://970design.com/
 * Description: A dashboard to keep track of Vercel deployments
 * Version:     1.0
 * Author:      970Design
 * Author URI:  https://970design.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: nsz-vercel-dashboard
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

require_once 'nsz-vercel-dashboard-admin.php';

add_filter( 'plugin_action_links_nsz-vercel-dashboard/nsz-vercel-dashboard.php', 'nsz_vercel_dashboard_settings_link' );
function nsz_vercel_dashboard_settings_link( $links ) {
    $url = esc_url( add_query_arg(
        'page',
        'nsz_vercel_dashboard_settings',
        get_admin_url() . 'options-general.php'
    ) );

    $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

    $links[] = $settings_link;
    return $links;
}


add_action('admin_enqueue_scripts', 'nsz_vercel_dashboard_admin_style');
function nsz_vercel_dashboard_admin_style() {
    $url     = plugin_dir_url( __FILE__ );
    $version = '1.0';

    $vercelApiToken = get_option('nsz_vercel_api_key');
    $projectId = get_option('nsz_vercel_project_id');
    $gitRepo = get_option('nsz_vercel_git_repo');
    $gitOrg = get_option('nsz_vercel_git_org');
    $gitBranch = get_option('nsz_vercel_git_branch');

    wp_enqueue_style( 'nsz_vercel_dashboard_admin_css', "{$url}assets/nsz-vercel-dashboard.css", false, $version);

    wp_enqueue_script( 'nsz_vercel_dashboard_admin_js', "{$url}assets/nsz-vercel-dashboard.js", false, $version);

    $params = array(
        'api_token' => $vercelApiToken,
        'project_id' => $projectId,
        'git_repo' => $gitRepo,
        'git_org' => $gitOrg,
        'git_branch' => $gitBranch,
    );

    wp_localize_script( 'nsz_vercel_dashboard_admin_js', 'nsz_vercel_dashboard_admin_js', $params );
}


require_once 'Vercel_Dashboard.php';
$custom_dashboard_widgets = new Vercel_Dashboard();








