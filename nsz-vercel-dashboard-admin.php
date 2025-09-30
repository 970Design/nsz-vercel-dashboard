<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!function_exists('nsz_encrypt_value')) {
    function nsz_encrypt_value($value)
    {
        if (empty($value)) {
            return '';
        }

        $key = hash('sha256', AUTH_SALT . SECURE_AUTH_SALT, true);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt(
                $value,
                'AES-256-CBC',
                $key,
                0,
                $iv
        );

        if ($encrypted === false) {
            return '';
        }

        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('nsz_decrypt_value')) {
    function nsz_decrypt_value($encrypted_data)
    {
        if (empty($encrypted_data)) {
            return '';
        }

        $decoded = base64_decode($encrypted_data);
        if ($decoded === false) {
            return '';
        }

        if (strlen($decoded) < 17) {
            return '';
        }

        $iv = substr($decoded, 0, 16);
        if (strlen($iv) !== 16) {
            return '';
        }

        $encrypted = substr($decoded, 16);
        $key = hash('sha256', AUTH_SALT . SECURE_AUTH_SALT, true);
        $decrypted = openssl_decrypt(
                $encrypted,
                'AES-256-CBC',
                $key,
                0,
                $iv
        );

        return $decrypted === false ? '' : $decrypted;
    }
}

if (!function_exists('nsz_obfuscate_string')) {
    function nsz_obfuscate_string($string, $show_start = 4, $show_end = 4)
    {
        if (empty($string)) {
            return '';
        }

        $length = strlen($string);
        if ($length <= ($show_start + $show_end)) {
            return str_repeat('*', $length);
        }

        $start = substr($string, 0, $show_start);
        $end = substr($string, -$show_end);
        $middle_length = $length - ($show_start + $show_end);

        return $start . str_repeat('*', $middle_length) . $end;
    }
}

function nsz_vercel_dashboard_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $nsz_vercel_api_field = 'nsz_vercel_api_key';
    $nsz_vercel_project_id_field = 'nsz_vercel_project_id';
    $nsz_vercel_git_repo = 'nsz_vercel_git_repo';
    $nsz_vercel_git_org = 'nsz_vercel_git_org';
    $nsz_vercel_git_branch = 'nsz_vercel_git_branch';

    if (isset($_POST['submitted']) && $_POST['submitted'] == 'Y') {

        $nsz_vercel_api_value = get_option($nsz_vercel_api_field, null);
        if (isset($_POST[$nsz_vercel_api_field]) && !str_contains($_POST[$nsz_vercel_api_field], '*')) {
            $nsz_vercel_api_value = nsz_encrypt_value(sanitize_text_field($_POST[$nsz_vercel_api_field]));
        }
        update_option($nsz_vercel_api_field, $nsz_vercel_api_value);

        $nsz_vercel_project_id_value = sanitize_text_field($_POST[$nsz_vercel_project_id_field] ?? '');
        update_option($nsz_vercel_project_id_field, $nsz_vercel_project_id_value);

        $nsz_vercel_git_repo_value = sanitize_text_field($_POST[$nsz_vercel_git_repo] ?? '');
        update_option($nsz_vercel_git_repo, $nsz_vercel_git_repo_value);

        $nsz_vercel_git_org_value = sanitize_text_field($_POST[$nsz_vercel_git_org] ?? '');
        update_option($nsz_vercel_git_org, $nsz_vercel_git_org_value);

        $nsz_vercel_git_branch_value = sanitize_text_field($_POST[$nsz_vercel_git_branch] ?? '');
        update_option($nsz_vercel_git_branch, $nsz_vercel_git_branch_value);
        ?>
        <div class="updated"><p><strong>Settings Updated</strong></p></div>
        <?php
    }

    $nsz_vercel_api_value = nsz_decrypt_value(get_option($nsz_vercel_api_field, ''));
    $nsz_vercel_project_id_value = get_option($nsz_vercel_project_id_field, '');
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
                            <input required type="text" id="<?php echo esc_attr($nsz_vercel_api_field); ?>" name="<?php echo esc_attr($nsz_vercel_api_field); ?>" value="<?php echo esc_html(nsz_obfuscate_string($nsz_vercel_api_value)); ?>" size="35">
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