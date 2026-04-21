<?php
namespace STSTraffic\Core;

class Updater {
    private $slug;
    private $current_version;
    private $repo;

    public function __construct($slug, $version, $repo) {
        $this->slug = $slug;
        $this->current_version = $version;
        $this->repo = $repo;

        add_filter('site_transient_update_plugins', [$this, 'check_update']);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) return $transient;

        $remote = $this->get_remote_info();
        if ($remote && version_compare($this->current_version, $remote->version, '<')) {
            $res = new \stdClass();
            $res->slug = $this->slug;
            $res->plugin = "{$this->slug}/{$this->slug}.php";
            $res->new_version = $remote->version;
            $res->package = "https://github.com/{$this->repo}/archive/refs/heads/main.zip";
            $res->url = "https://github.com/{$this->repo}";
            
            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }

    private function get_remote_info() {
        $url = "https://raw.githubusercontent.com/{$this->repo}/main/{$this->slug}.php";
        $response = wp_remote_get($url);
        if (is_wp_error($response)) return false;

        $content = wp_remote_retrieve_body($response);
        preg_match('/Version:\s*(.*)/', $content, $matches);
        
        return isset($matches[1]) ? (object) ['version' => trim($matches[1])] : false;
    }
}
