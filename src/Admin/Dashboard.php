<?php
namespace STSTraffic\Admin;

class Dashboard {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function add_menu() {
        add_menu_page('Traffic Scout', 'Traffic Scout 🟢', 'manage_options', 'traffic-scout-elite', [$this, 'render_page'], 'dashicons-chart-line', 2);
    }

    public function enqueue_styles($hook) {
        if ('toplevel_page_traffic-scout-elite' !== $hook) return;
        wp_add_inline_style('wp-admin', "
            .sts-traffic-body { background:#0a0a0a; color:#f0f0f0; padding:30px; min-height:100vh; font-family:'Outfit', sans-serif; margin-left:-20px; }
            .sts-traffic-card { background:#121212; border:1px solid #222; border-radius:15px; padding:35px; box-shadow:0 20px 50px rgba(0,0,0,0.5); }
            .sts-traffic-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; border-bottom:1px solid #333; padding-bottom:30px; }
            .sts-traffic-header h1 { font-size:32px; font-weight:800; color:#00ffa3; text-transform:uppercase; letter-spacing:2px; margin:0; text-shadow:0 0 20px rgba(0,255,163,0.3); }
            .sts-big-metrics { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:25px; margin-bottom:40px; }
            .sts-metric-box { background:#1a1a1a; padding:30px; border-radius:12px; border:1px solid #333; text-align:center; transition:0.3s; }
            .sts-metric-box:hover { border-color:#00ffa3; transform:translateY(-5px); }
            .sts-metric-val { display:block; font-size:48px; font-weight:900; color:#fff; line-height:1; }
            .sts-metric-label { font-size:12px; text-transform:uppercase; color:#888; font-weight:700; border-top:1px solid #333; margin-top:15px; padding-top:15px; display:block; }
            .sts-traffic-table { width:100%; border-collapse:collapse; margin-top:20px; }
            .sts-traffic-table th { text-align:left; padding:15px; background:#1a1a1a; color:#00ffa3; font-size:12px; text-transform:uppercase; border-bottom:2px solid #333; }
            .sts-traffic-table td { padding:20px 15px; border-bottom:1px solid #222; font-size:14px; }
            .sts-live-pulse { width:10px; height:10px; background:#00ffa3; border-radius:50%; display:inline-block; margin-right:8px; animation:stsPulse 1.5s infinite; box-shadow:0 0 10px #00ffa3; }
            @keyframes stsPulse { 0% { opacity:0.3; } 50% { opacity:1; } 100% { opacity:0.3; } }
            .sts-url-link { color:#888; text-decoration:none; font-size:12px; }
            .sts-url-link:hover { color:#00ffa3; }
        ");
    }

    public function render_page() {
        $users = get_transient('sts_traffic_online_users') ?: [];
        $total = count($users);
        
        // Calcular páginas mais populares agora
        $top_pages = [];
        foreach($users as $u) { $top_pages[$u['title']] = ($top_pages[$u['title']] ?? 0) + 1; }
        arsort($top_pages);
        $best_recipe = !empty($top_pages) ? key($top_pages) : '---';
        ?>
        <div class="sts-traffic-body">
            <div class="sts-traffic-card">
                <div class="sts-traffic-header">
                    <h1>Traffic Scout Elite <span style='font-size:12px; vertical-align:middle; background:#00ffa3; color:#000; padding:2px 8px; border-radius:4px;'>GOD MODE</span></h1>
                    <button class="button" onclick="location.reload()" style="background:#00ffa3; border:0; color:#000; font-weight:bold;"><?php _e('REFRESH RADAR','traffic-scout-elite');?></button>
                </div>

                <div class="sts-big-metrics">
                    <div class="sts-metric-box">
                        <span class="sts-metric-val"><?php echo $total; ?></span>
                        <span class="sts-metric-label"><?php _e('Chefes Online Agora','traffic-scout-elite');?></span>
                    </div>
                    <div class="sts-metric-box">
                        <span class="sts-metric-val" style="font-size:24px; padding:12px 0;"><?php echo esc_html($best_recipe); ?></span>
                        <span class="sts-metric-label"><?php _e('Receita Mais Quente','traffic-scout-elite');?></span>
                    </div>
                </div>

                <h2 style="color:#fff; font-size:18px; margin-bottom:20px;"><div class="sts-live-pulse"></div> <?php _e('Radar de Atividade em Tempo Real','traffic-scout-elite');?></h2>
                <table class="sts-traffic-table">
                    <thead>
                        <tr>
                            <th><?php _e('Localização Atual','traffic-scout-elite');?></th>
                            <th><?php _e('URL do Alvo','traffic-scout-elite');?></th>
                            <th><?php _e('Tempo desde o último pulso','traffic-scout-elite');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)) : ?>
                            <tr><td colspan="3" style="text-align:center; padding:50px; color:#555;"><?php _e('Silêncio total na cozinha... Nenhum tráfego detectado.','traffic-scout-elite');?></td></tr>
                        <?php else : ?>
                            <?php foreach(array_reverse($users) as $u) : 
                                $ago = time() - $u['time'];
                            ?>
                                <tr>
                                    <td style="font-weight:bold; color:#fff;"><?php echo esc_html($u['title']); ?></td>
                                    <td><a href="<?php echo $u['url']; ?>" target="_blank" class="sts-url-link"><?php echo $u['url']; ?></a></td>
                                    <td style="color:#00ffa3; font-weight:bold;"><?php echo $ago; ?>s ago</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
