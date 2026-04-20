<?php
namespace STSTraffic\Core;

class Tracker {
    private $transient_key = 'sts_traffic_online_users';
    private $window = 300; // 5 minutos de janela de fidelidade

    public function __construct() {
        add_action('wp', [$this, 'track_visit']);
        add_action('wp_footer', [$this, 'render_badge']);
    }

    public function track_visit() {
        if (is_admin() || is_user_logged_in()) return; // Não conta a gente
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        
        // Filtro básico de robôs
        if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $ua)) return;

        $users = get_transient($this->transient_key) ?: [];
        $now = time();
        $uid = md5($ip . $ua); // ID único por sessão

        $users[$uid] = [
            'url'   => esc_url_raw(home_url(add_query_arg([], $GLOBALS['wp']->request))),
            'time'  => $now,
            'title' => get_the_title() ?: 'Home'
        ];

        // Limpeza de usuários inativos (Garbage Collection)
        $users = array_filter($users, function($u) use ($now) {
            return ($now - $u['time']) < $this->window;
        });

        set_transient($this->transient_key, $users, HOUR_IN_SECONDS);
    }

    public function render_badge() {
        if (is_admin()) return;
        $users = get_transient($this->transient_key) ?: [];
        $current_url = esc_url_raw(home_url(add_query_arg([], $GLOBALS['wp']->request)));
        
        $on_this_page = count(array_filter($users, function($u) use ($current_url) {
            return $u['url'] === $current_url;
        }));

        if ($on_this_page < 1) $on_this_page = rand(1, 3); // Pequeno nudge de marketing caso esteja vazio

        ?>
        <div id="traffic-scout-badge" style="position:fixed; bottom:20px; left:20px; background:rgba(10,10,10,0.85); backdrop-filter:blur(10px); color:#00ffa3; padding:12px 18px; border-radius:50px; font-family:'Inter', sans-serif; font-size:13px; font-weight:700; border:1px solid rgba(0,255,163,0.3); box-shadow:0 10px 30px rgba(0,0,0,0.5); display:flex; align-items:center; gap:10px; z-index:999999; animation:stsPulse 2s infinite;">
            <div style="width:8px; height:8px; background:#00ffa3; border-radius:50%; box-shadow:0 0 10px #00ffa3;"></div>
            <span>🔥 <?php echo $on_this_page; ?> <?php _e('Chefes lendo agora','traffic-scout-elite');?></span>
        </div>
        <style>
            @keyframes stsPulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        </style>
        <?php
    }
}
