<?php
/**
 * Vercel Dashboard
 *
 * Plugin Name: 970 Design Vercel Dashboard
 * Plugin URI:  https://970design.com/
 * Description: A dashboard to keep track of Vercel deployments
 * Version:     1.9
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

require_once 'Vercel_Dashboard.php';
$vercel_dashboard_widgets = new Vercel_Dashboard();

require 'path/to/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/970Design/nsz-vercel-dashboard',
    __FILE__, //Full path to the main plugin file or functions.php.
    'nsz-vercel-dashboard'
);