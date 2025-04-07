<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function nsz_vercel_dashboard_settings_page() {
    // Must check that the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Define variables for the field and option names
    $nsz_vercel_api_field = 'nsz_vercel_api_key';
    $nsz_vercel_project_id_field = 'nsz_vercel_project_id';
    $nsz_vercel_git_repo = 'nsz_vercel_git_repo';
    $nsz_vercel_git_org = 'nsz_vercel_git_org';
    $nsz_vercel_git_branch = 'nsz_vercel_git_branch';

    // Check if the form has been submitted
    if (isset($_POST['submitted']) && $_POST['submitted'] == 'Y') {
        // Sanitize and save the API Key
        $nsz_vercel_api_value = esc_attr($_POST[$nsz_vercel_api_field] ?? '');
        update_option($nsz_vercel_api_field, $nsz_vercel_api_value);

        // Sanitize and save the Project ID
        $nsz_vercel_project_id_value = esc_attr($_POST[$nsz_vercel_project_id_field] ?? '');
        update_option($nsz_vercel_project_id_field, $nsz_vercel_project_id_value);

        // Sanitize and save the Git Repo
        $nsz_vercel_git_repo_value = esc_attr($_POST[$nsz_vercel_git_repo] ?? '');
        update_option($nsz_vercel_git_repo, $nsz_vercel_git_repo_value);

        // Sanitize and save the Git Org
        $nsz_vercel_git_org_value = esc_attr($_POST[$nsz_vercel_git_org] ?? '');
        update_option($nsz_vercel_git_org, $nsz_vercel_git_org_value);

        // Sanitize and save the Git Branch
        $nsz_vercel_git_branch_value = esc_attr($_POST[$nsz_vercel_git_branch] ?? '');
        update_option($nsz_vercel_git_branch, $nsz_vercel_git_branch_value);

        // Display a success message
        ?>
        <div class="updated"><p><strong>Settings Updated</strong></p></div>
        <?php
    }

    // Retrieve the current values to display in the form
    $nsz_vercel_api_value = get_option($nsz_vercel_api_field, '');
    $nsz_vercel_project_id_value = get_option($nsz_vercel_project_id_field, '');

    // Assets
    $wordmark_url = plugins_url( 'assets/wordmark.svg', __FILE__ );

    ?>
    <section class="nsz-design-video-admin">

        <header class="nsz-design-video-header">
            <div class="nsz-design-video-container">
                <h1 class="nsz-design-video-header-title">
                    <img src="<?php echo esc_url( $wordmark_url ); ?>" alt="970 Design Wordmark" class="nsz-design-video-wordmark"> Vercel Settings
                </h1>
            </div>
        </header>

        <section class="nsz-design-video-container">
            <div class="nsz-design-video-card">
                <form method="post" action="">
                    <h2 class="nsz-design-video-title">Vercel Settings</h2>

                    <div class="nsz-design-video-row">
                        <div>
                            <label for="<?php echo esc_attr($nsz_vercel_api_field); ?>">API Token: <span class="required">*</span></label>
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_api_field); ?>" name="<?php echo esc_attr($nsz_vercel_api_field); ?>" value="<?php echo esc_html($nsz_vercel_api_value); ?>" size="35">
                            <br>
                        </div>
                    </div>
                    <div class="nsz-design-video-row">
                        <div>
                            <label for="<?php echo esc_attr($nsz_vercel_project_id_field); ?>">Project ID: <span class="required">*</span></label>
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_project_id_field); ?>" name="<?php echo esc_attr($nsz_vercel_project_id_field); ?>" value="<?php echo esc_html($nsz_vercel_project_id_value); ?>" size="35">
                            <br>
                        </div>
                    </div>
                    <div class="nsz-design-video-row">
                        <div>
                            <label for="<?php echo esc_attr($nsz_vercel_git_repo); ?>">Git Repo: <span class="required">*</span></label>
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_git_repo); ?>" name="<?php echo esc_attr($nsz_vercel_git_repo); ?>" value="<?php echo esc_html(get_option($nsz_vercel_git_repo, '')); ?>" size="35">
                            <br>
                        </div>
                    </div>
                    <div class="nsz-design-video-row">
                        <div>
                            <label for="<?php echo esc_attr($nsz_vercel_git_org); ?>">Git Org: <span class="required">*</span></label>
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_git_org); ?>" name="<?php echo esc_attr($nsz_vercel_git_org); ?>" value="<?php echo esc_html(get_option($nsz_vercel_git_org, '')); ?>" size="35">
                            <br>
                        </div>
                    </div>
                    <div class="nsz-design-video-row">
                        <div>
                            <label for="<?php echo esc_attr($nsz_vercel_git_branch); ?>">Git Branch: <span class="required">*</span></label>
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_git_branch); ?>" name="<?php echo esc_attr($nsz_vercel_git_branch); ?>" value="<?php echo esc_html(get_option($nsz_vercel_git_branch, '')); ?>" size="35">
                            <br>
                        </div>
                    </div>

                    <footer class="nsz-design-video-footer">
                        <input type="hidden" name="submitted" value="Y">
                        <div class="submit">
                            <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
                        </div>
                    </footer>
                </form>
            </div>
        </section>
    </section>
    <?php
}

function nsz_vercel_dashboard_menu_item() {
    add_options_page("Vercel Dashboard Settings", "Vercel Dashboard Settings", "manage_options", "nsz_vercel_dashboard_settings", "nsz_vercel_dashboard_settings_page");
}

add_action("admin_menu", "nsz_vercel_dashboard_menu_item");