<?php
namespace STSTraffic\Core;

class Tracker {
    private $transient_key = 'sts_traffic_online_users';
    private $window = 300; 

    public function __construct() {
        add_action('wp', [$this, 'track_visit']);
        add_action('wp_footer', [$this, 'render_badge']);
    }

    public function track_visit() {
        if (is_admin() || is_user_logged_in()) return;
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $ua)) return;

        $now = time();
        $today = date('Y-m-d');
        $url = esc_url_raw(home_url(add_query_arg([], $GLOBALS['wp']->request)));
        $url_hash = md5($url);
        $title = get_the_title() ?: 'Home';
        $uid = md5($ip . $ua);

        // 1. Atualizar Online (Transient)
        $users = get_transient($this->transient_key) ?: [];
        $users[$uid] = ['url' => $url, 'time' => $now, 'title' => $title];
        $users = array_filter($users, function($u) use ($now) { return ($now - $u['time']) < $this->window; });
        set_transient($this->transient_key, $users, HOUR_IN_SECONDS);

        // 2. Atualizar Histórico (Database) - ON DUPLICATE KEY UPDATE para performance God Mode
        global $wpdb;
        $table = $wpdb->prefix . 'sts_traffic_stats';
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (url_hash, url, title, hits, visit_date) 
             VALUES (%s, %s, %s, 1, %s) 
             ON DUPLICATE KEY UPDATE hits = hits + 1",
            $url_hash, $url, $title, $today
        ));
    }

    public function render_badge() {
        if (is_admin()) return;
        $users = get_transient($this->transient_key) ?: [];
        $current_url = esc_url_raw(home_url(add_query_arg([], $GLOBALS['wp']->request)));
        
        $on_this_page = count(array_filter($users, function($u) use ($current_url) {
            return $u['url'] === $current_url;
        }));

        if ($on_this_page < 1) return; // Não mostra nada se estiver vazio

        ?>
        <div id="traffic-scout-badge" style="position:fixed; bottom:20px; left:20px; background:rgba(10,10,10,0.85); backdrop-filter:blur(10px); color:#00ffa3; padding:12px 18px; border-radius:50px; font-family:'Inter', sans-serif; font-size:13px; font-weight:700; border:1px solid rgba(0,255,163,0.3); box-shadow:0 10px 30px rgba(0,0,0,0.5); display:flex; align-items:center; gap:10px; z-index:999999; animation:stsPulse 2s infinite; cursor:default;">
            <div style="width:8px; height:8px; background:#00ffa3; border-radius:50%; box-shadow:0 0 10px #00ffa3;"></div>
            <span>🔥 <?php echo $on_this_page; ?> <?php _e('Chefes lendo agora','traffic-scout-elite');?></span>
        </div>
        <style> @keyframes stsPulse { 0%{transform:scale(1);} 50%{transform:scale(1.05);} 100%{transform:scale(1);} } </style>
        <?php
    }
}
