<?php

class Vercel_Dashboard {
    public $vercelApiToken = null;
    public $projectId = null;
    public $gitRepo = null;
    public $gitOrg = null;
    public $gitBranch = null;

    public function __construct() {
        $this->vercelApiToken = nsz_decrypt_value(get_option('nsz_vercel_api_key'));
        $this->projectId = get_option('nsz_vercel_project_id');
        $this->gitRepo = get_option('nsz_vercel_git_repo');
        $this->gitOrg = get_option('nsz_vercel_git_org');
        $this->gitBranch = get_option('nsz_vercel_git_branch');

        $this->init();
    }

    public function init(){
        add_action('wp_dashboard_setup', array( $this, 'nsz_vercel_dashboard' ) );
        add_action('admin_enqueue_scripts', array( $this, 'nsz_vercel_dashboard_admin_style' ));

        // AJAX handler for refreshing deploys
        add_action('wp_ajax_refresh_vercel_deployments', array($this, 'ajax_refresh_deployments'));
    }

    public function nsz_vercel_dashboard_admin_style() {
        $url     = plugin_dir_url( __FILE__ );
        $version = '1.0.1';

        wp_enqueue_style( 'nsz_vercel_dashboard_admin_css', "{$url}assets/nsz-vercel-dashboard.css", false, $version);

        wp_enqueue_script( 'nsz_vercel_dashboard_admin_js', "{$url}assets/nsz-vercel-dashboard.js", false, $version);

        $params = array(
            'api_token' => $this->vercelApiToken,
            'project_id' => $this->projectId,
            'git_repo' => $this->gitRepo,
            'git_org' => $this->gitOrg,
            'git_branch' => $this->gitBranch,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vercel_dashboard_nonce')
        );

        wp_localize_script( 'nsz_vercel_dashboard_admin_js', 'nsz_vercel_dashboard_admin_js', $params );
    }


    public function nsz_vercel_dashboard() {
        wp_add_dashboard_widget( "nsz_vercel_dashboard_widget", "Vercel Dashboard", array( $this, "nsz_vercel_dashboard_widget" ) );
    }

    public function nsz_vercel_dashboard_widget() {
        $settings_url = esc_url(add_query_arg(
            'page',
            'nsz_vercel_dashboard_settings',
            get_admin_url() . 'options-general.php'
        ));
        if ($this->vercelApiToken && $this->projectId) {
            try {
                $deployments = $this->getDeployments($this->vercelApiToken, $this->projectId);

                if ($this->gitOrg && $this->gitRepo && $this->gitBranch) {
                    echo '<div class="nsz-design-vercel-header">
                            <h2>Vercel Deployments</h2>
                            <p>Compile and deploy your site after making changes.</p>

                            <div class="nsz-vercel-deploy-button-wrapper">
                                <button class="start-vercel-deploy button button-primary">Create Deployment</button>

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><circle fill="none" stroke-opacity="1" stroke="#2271B1" stroke-width=".5" cx="100" cy="100" r="0"><animate attributeName="r" calcMode="spline" dur="2" values="1;80" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-width" calcMode="spline" dur="2" values="0;25" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate><animate attributeName="stroke-opacity" calcMode="spline" dur="2" values="1;0" keyTimes="0;1" keySplines="0 .2 .5 1" repeatCount="indefinite"></animate></circle></svg>
                            </div>

                          </div>';
                } else {
                    echo "<p>Please set your Git Repo, Org, and Branch in the <a href='".$settings_url."'>plugin settings</a>.</p>";
                }

                echo '<ul id="nsz-design-vercel-deployments-list">';
                echo $this->generateDeploymentsListHtml($deployments);
                echo '</ul>';
            } catch (Exception $e) {
                echo '<div class="nsz-vercel-dash-error-holder">Error: ' . $e->getMessage().'</div>';
            }
        } else {
            echo "<div class='nsz-vercel-dash-error-holder'>Please set your Vercel API token and Project ID in the <a href='".$settings_url."'>plugin settings</a>.</div>";
        }
    }

    public function getDeployments($vercelApiToken, $projectId) {
        $url = "https://api.vercel.com/v6/deployments?projectId=$projectId&limit=8";
        $headers = [
            "Authorization: Bearer $vercelApiToken"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Failed to fetch deployments: ' . curl_error($ch));
        }
        curl_close($ch);

        $deployments = json_decode($response, true);
        if (isset($deployments['error'])) {
            throw new Exception('Error fetching deployments: ' . $deployments['error']['message']);
        }

        return $deployments['deployments'];
    }

    // Helper method to format "time ago"
    private function getTimeAgo($datetime) {
        $now = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
        $today = $now->format('Y-m-d');
        $deploymentDate = $datetime->format('Y-m-d');

        // If it's not today, show the date like "Sep 21"
        if ($deploymentDate !== $today) {
            return $datetime->format('M j');
        }

        // For today, show time ago
        $interval = $now->diff($datetime);

        if ($interval->h > 0) {
            return $interval->h . 'h ago';
        }

        if ($interval->i > 0) {
            return $interval->i . 'm ago';
        }

        // If less than a minute, show "Just now"
        return 'Just now';
    }

    // Reusable method to generate HTML for a single deployment
    private function generateDeploymentHtml($deployment) {
        $html = '<li>';
        $html .= "<strong class='nsz-vercel-deployment-id'>" . substr($deployment['uid'], 4, 9) . "</strong>";

        $html .= "<div class='nsz-vercel-deployment-status'>";
        $html .= "<span class='nsz-vercel-state nsz-vercel-state-".strtolower($deployment['state'])."'>" . ucfirst(strtolower($deployment['state'])) . "</span>";

        $createdAt = new DateTime('@' . intval($deployment['createdAt'] / 1000), new DateTimeZone(date_default_timezone_get()));

        if ($deployment['state'] === 'READY' || $deployment['state'] === 'ERROR' || $deployment['state'] === 'CANCELED')  {
            if (isset($deployment['ready']) && isset($deployment['buildingAt'])) {
                $buildingAt = new DateTime('@' . intval($deployment['buildingAt'] / 1000), new DateTimeZone(date_default_timezone_get()));
                $readyAt = new DateTime('@' . intval($deployment['ready'] / 1000));
                $interval = $buildingAt->diff($readyAt);

                $minutes = $interval->i;
                $seconds = $interval->s;

                if ($minutes > 0) {
                    $html .= $minutes . 'm' . ($seconds > 0 ? ' ' . $seconds . 's' : '');
                } else {
                    $html .= $seconds > 0 ? $seconds . 's' : '1s';
                }
            }
        }

        if ($deployment['state'] === 'BUILDING' && isset($deployment['buildingAt'])) {
            $elapsedTime = time() - (intval($deployment['buildingAt'] / 1000));
            $elapsedTime = gmdate("H:i:s", $elapsedTime);
            $html .= "" . $elapsedTime . "";
        }

        $html .= ' (' . $this->getTimeAgo($createdAt) . ')';

        $html .= "</div>";

        // $html .= "<div class='nsz-vercel-deployment-timestamp'>" . $this->getTimeAgo($createdAt) . "</div>";

        if ($deployment['state'] === 'BUILDING' || $deployment['state'] === 'QUEUED') {
            $html .= '
            <button class="cancel-vercel-deploy button button-cancel" data-id="'.$deployment['uid'].'">Cancel Deployment</button>';
        }

        $html .= '</li>';
        return $html;
    }

    // Reusable method to generate HTML for all deployments
    private function generateDeploymentsListHtml($deployments) {
        $html = '';
        foreach ($deployments as $deployment) {
            $html .= $this->generateDeploymentHtml($deployment);
        }
        return $html;
    }

    // Refresh deployment list
    public function ajax_refresh_deployments() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'vercel_dashboard_nonce')) {
            wp_die('Security check failed');
        }

        if ($this->vercelApiToken && $this->projectId) {
            try {
                $deployments = $this->getDeployments($this->vercelApiToken, $this->projectId);

                // Return structured data instead of HTML for more efficient updates
                $formattedDeployments = array();
                foreach ($deployments as $deployment) {
                    $createdAt = new DateTime('@' . intval($deployment['createdAt'] / 1000));
                    $createdAt->setTimezone(new DateTimeZone(date_default_timezone_get()));

                    $buildTime = '';
                    if ($deployment['state'] === 'READY' || $deployment['state'] === 'ERROR' || $deployment['state'] === 'CANCELED') {
                        if (isset($deployment['ready']) && isset($deployment['buildingAt'])) {
                            $buildingAt = new DateTime('@' . intval($deployment['buildingAt'] / 1000));
                            $readyAt = new DateTime('@' . intval($deployment['ready'] / 1000));
                            $interval = $buildingAt->diff($readyAt);

                            $minutes = $interval->i;
                            $seconds = $interval->s;

                            if ($minutes > 0) {
                                $buildTime = $minutes . 'm' . ($seconds > 0 ? ' ' . $seconds . 's' : '');
                            } else {
                                $buildTime = $seconds > 0 ? $seconds . 's' : '1s';
                            }
                        }
                    } elseif ($deployment['state'] === 'BUILDING' && isset($deployment['buildingAt'])) {
                        $elapsedTime = time() - (intval($deployment['buildingAt'] / 1000));
                        $buildTime = gmdate("H:i:s", $elapsedTime);
                    }

                    $formattedDeployments[] = array(
                        'uid' => $deployment['uid'],
                        'shortId' => substr($deployment['uid'], 4, 9),
                        'state' => $deployment['state'],
                        'createdAt' => $deployment['createdAt'],
                        'formattedTime' => $this->getTimeAgo($createdAt),
                        'buildingAt' => isset($deployment['buildingAt']) ? $deployment['buildingAt'] : null,
                        'buildTime' => $buildTime,
                        'isActive' => in_array($deployment['state'], ['BUILDING', 'QUEUED'])
                    );
                }

                wp_send_json_success(array(
                    'deployments' => $formattedDeployments,
                    'timestamp' => time()
                ));

            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
            }
        } else {
            wp_send_json_error(array('message' => 'API token or Project ID not configured'));
        }
    }


}