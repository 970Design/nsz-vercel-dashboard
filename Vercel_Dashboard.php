<?php

class Vercel_Dashboard {
    public $vercelApiToken = null;
    public $projectId = null;
    public $gitRepo = null;
    public $gitOrg = null;
    public $gitBranch = null;

    public function __construct() {
        $this->vercelApiToken = get_option('nsz_vercel_api_key');
        $this->projectId = get_option('nsz_vercel_project_id');
        $this->gitRepo = get_option('nsz_vercel_git_repo');
        $this->gitOrg = get_option('nsz_vercel_git_org');
        $this->gitBranch = get_option('nsz_vercel_git_branch');

        $this->init();
    }

    public function init(){
        add_action('wp_dashboard_setup', array( $this, 'nsz_vercel_dashboard' ) );
        add_action('admin_enqueue_scripts', array( $this, 'nsz_vercel_dashboard_admin_style' ));
    }

    public function nsz_vercel_dashboard_admin_style() {
        $url     = plugin_dir_url( __FILE__ );
        $version = '1.0';

        wp_enqueue_style( 'nsz_vercel_dashboard_admin_css', "{$url}assets/nsz-vercel-dashboard.css", false, $version);

        wp_enqueue_script( 'nsz_vercel_dashboard_admin_js', "{$url}assets/nsz-vercel-dashboard.js", false, $version);

        $params = array(
            'api_token' => $this->vercelApiToken,
            'project_id' => $this->projectId,
            'git_repo' => $this->gitRepo,
            'git_org' => $this->gitOrg,
            'git_branch' => $this->gitBranch,
        );

        wp_localize_script( 'nsz_vercel_dashboard_admin_js', 'nsz_vercel_dashboard_admin_js', $params );
    }


    public function nsz_vercel_dashboard() {
        wp_add_dashboard_widget( "nsz_vercel_dashboard_widget", "Vercel Dashboard", array( $this, "nsz_vercel_dashboard_widget" ) );
    }

    public function nsz_vercel_dashboard_widget() {
        if ($this->vercelApiToken && $this->projectId) {
            try {
                $deployments = $this->getDeployments($this->vercelApiToken, $this->projectId);

                if ($this->gitOrg && $this->gitRepo && $this->gitBranch) {
                    echo '<div class="nsz-design-vercel-header"><button class="start-vercel-deploy button button-primary">Start a New Deployment</button></div>';
                } else {
                    echo "<p>Please set your Git Repo, Org, and Branch in the <a href='".esc_url(add_query_arg('page', 'nsz_vercel_dashboard_settings', get_admin_url() . 'options-general.php'))."'>plugin settings</a>.</p>";
                }

                foreach ($deployments as $deployment) {
                    echo "<strong>Deployment ID:</strong> " . $deployment['uid'] . "<br />";
                    //echo "URL: " . $deployment['url'] . "<br />";
                    $createdAt = DateTime::createFromFormat('U', (intval($deployment['createdAt'] / 1000)))->setTimezone(new DateTimeZone('America/Denver'));
                    echo "<strong>Created At:</strong> " . $createdAt->format('n/j/Y g:ia') . "<br />";

                    if ($deployment['state'] === 'READY') {
                        //show minutes and seconds it took between the createdat and ready
                        $readyAt = DateTime::createFromFormat('U', (intval($deployment['ready'] / 1000)));
                        $interval = $createdAt->diff($readyAt);
                        echo "<strong>Build Time:</strong> " . $interval->format('%i minutes %s seconds') . "<br />";
                    }

                    if ($deployment['state'] === 'BUILDING') {
                        //show elapsed time
                        $elapsedTime = time() - (intval($deployment['createdAt'] / 1000));
                        $elapsedTime = gmdate("H:i:s", $elapsedTime);
                        echo "<strong>Elapsed Time:</strong> " . $elapsedTime . "<br />";
                    }

                    echo "<strong>State:</strong> <span class='nsz-vercel-state nsz-vercel-state-".strtolower($deployment['state'])."'>" . $deployment['state'] . "</span><br />";

                    if ($deployment['state'] === 'BUILDING' || $deployment['state'] === 'QUEUED') {
                        echo '<br /><button class="cancel-vercel-deploy button button-primary" data-id="'.$deployment['uid'].'">Cancel Deployment</button><hr />';
                    }

                    echo '<br />';
                }
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
        } else {
            $url = esc_url(add_query_arg(
                'page',
                'nsz_vercel_dashboard_settings',
                get_admin_url() . 'options-general.php'
            ));
            echo "<p>Please set your Vercel API token and Project ID in the <a href='".$url."'>plugin settings</a>.</p>";
        }
    }

    public function getDeployments($vercelApiToken, $projectId) {
        $url = "https://api.vercel.com/v6/deployments?projectId=$projectId&limit=5";
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


}