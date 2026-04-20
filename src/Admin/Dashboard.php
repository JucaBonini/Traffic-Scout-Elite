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
            .sts-traffic-body { background:#0a0a0a; color:#f0f0f0; padding:30px; min-height:100vh; font-family:'Inter', sans-serif; margin-left:-20px; }
            .sts-traffic-card { background:#121212; border:1px solid #222; border-radius:15px; padding:35px; box-shadow:0 20px 50px rgba(0,0,0,0.5); margin-bottom:30px; }
            .sts-traffic-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; border-bottom:1px solid #333; padding-bottom:30px; }
            .sts-traffic-header h1 { font-size:32px; font-weight:800; color:#00ffa3; text-transform:uppercase; letter-spacing:2px; margin:0; }
            .sts-big-metrics { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:25px; margin-bottom:40px; }
            .sts-metric-box { background:#1a1a1a; padding:30px; border-radius:12px; border:1px solid #333; text-align:center; transition:0.3s; }
            .sts-metric-box:hover { border-color:#00ffa3; }
            .sts-metric-val { display:block; font-size:48px; font-weight:900; color:#fff; }
            .sts-metric-label { font-size:12px; text-transform:uppercase; color:#888; font-weight:700; border-top:1px solid #333; margin-top:15px; padding-top:15px; display:block; }
            .sts-traffic-table { width:100%; border-collapse:collapse; }
            .sts-traffic-table th { text-align:left; padding:15px; background:#1a1a1a; color:#00ffa3; font-size:11px; text-transform:uppercase; border-bottom:2px solid #333; }
            .sts-traffic-table td { padding:15px; border-bottom:1px solid #222; font-size:13px; }
            .sts-rank-num { background:#00ffa3; color:#000; width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; border-radius:4px; font-weight:bold; font-size:11px; margin-right:10px; }
            .sts-live-pulse { width:10px; height:10px; background:#00ffa3; border-radius:50%; display:inline-block; margin-right:8px; animation:stsPulse 1.5s infinite; }
            @keyframes stsPulse { 0%{opacity:0.3;} 50%{opacity:1;} 100%{opacity:0.3;} }
            .sts-period-selector { background:#1a1a1a; border:1px solid #333; color:#fff; padding:8px 15px; border-radius:6px; cursor:pointer; }
        ");
    }

    public function render_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sts_traffic_stats';
        $users = get_transient('sts_traffic_online_users') ?: [];
        $period = $_GET['period'] ?? 'today';
        
        // Lógica de Ranking Histórico
        $where = "visit_date = CURDATE()";
        if ($period === 'week') $where = "visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        if ($period === 'month') $where = "visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $rank = $wpdb->get_results("
            SELECT url, title, SUM(hits) as total 
            FROM $table 
            WHERE $where 
            GROUP BY url_hash 
            ORDER BY total DESC 
            LIMIT 10
        ");
        ?>
        <div class="sts-traffic-body">
            <div class="sts-traffic-card">
                <div class="sts-traffic-header">
                    <h1>Traffic Scout Elite <span style='font-size:11px; vertical-align:middle; background:#00ffa3; color:#000; padding:2px 8px; border-radius:4px;'>v1.2.0</span></h1>
                    <button class="button" onclick="location.reload()" style="background:#00ffa3; border:0; color:#000; font-weight:bold;">REFRESH RADAR</button>
                </div>

                <div class="sts-big-metrics">
                    <div class="sts-metric-box">
                        <span class="sts-metric-val"><?php echo count($users); ?></span>
                        <span class="sts-metric-label"><?php _e('Chefes Online Agora','traffic-scout-elite');?></span>
                    </div>
                    <div class="sts-metric-box">
                        <span class="sts-metric-val" style="font-size:20px; padding:12px 0;"><?php echo !empty($rank) ? esc_html($rank[0]->title) : '---'; ?></span>
                        <span class="sts-metric-label"><?php _e('Ranking #1 Atualmente','traffic-scout-elite');?></span>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2 style="color:#fff; font-size:18px; margin:0;"><div class="sts-live-pulse"></div> <?php _e('TOP 10 Elite Ranking','traffic-scout-elite');?></h2>
                    <select class="sts-period-selector" onchange="location.href='?page=traffic-scout-elite&period='+this.val();" id="period-switch">
                        <option value="today" <?php selected($period, 'today');?>><?php _e('Hoje (Real-time)','traffic-scout-elite');?></option>
                        <option value="week" <?php selected($period, 'week');?>><?php _e('Últimos 7 dias','traffic-scout-elite');?></option>
                        <option value="month" <?php selected($period, 'month');?>><?php _e('Últimos 30 dias','traffic-scout-elite');?></option>
                    </select>
                </div>

                <table class="sts-traffic-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">POS</th>
                            <th><?php _e('Página / Receita','traffic-scout-elite');?></th>
                            <th style="text-align:right;"><?php _e('Total de Visualizações','traffic-scout-elite');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rank)) : ?>
                            <tr><td colspan="3" style="text-align:center; padding:50px; color:#555;"><?php _e('Aguardando os primeiros dados de audiência...','traffic-scout-elite');?></td></tr>
                        <?php else : $pos=1; foreach($rank as $r) : ?>
                            <tr>
                                <td><span class="sts-rank-num"><?php echo $pos++; ?></span></td>
                                <td style="font-weight:bold; color:#fff;"><?php echo esc_html($r->title); ?><br><small style="color:#444; font-weight:normal;"><?php echo esc_url($r->url); ?></small></td>
                                <td style="text-align:right; font-weight:bold; color:#00ffa3; font-size:18px;"><?php echo number_format($r->total); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="sts-traffic-card" style="padding:20px;">
                <h3 style="margin-top:0; color:#555; font-size:12px; text-transform:uppercase;"><?php _e('Radar de Atividade Atual','traffic-scout-elite');?> (Online)</h3>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <?php if (empty($users)) : echo '<small style="color:#333;">Ninguém online agora.</small>'; 
                          else : foreach($users as $u) : ?>
                        <div style="background:#1a1a1a; padding:5px 12px; border-radius:5px; font-size:11px; border:1px solid #222;">
                            <span class="sts-live-pulse" style="width:6px; height:6px;"></span> <?php echo esc_html($u['title']); ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <script>
            jQuery('#period-switch').on('change', function() {
                location.href = 'admin.php?page=traffic-scout-elite&period=' + jQuery(this).val();
            });
        </script>
        <?php
    }
}
