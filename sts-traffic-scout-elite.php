<?php
/**
 * Plugin Name: Traffic Scout Elite
 * Plugin URI: https://descomplicandoreceitas.com.br
 * Description: Monitoramento ultra-leve de tráfego em tempo real com Social Proof dinâmico. Estilo God of War.
 * Version: 1.3.0
 * Author: Juca Souza Bonini
 * License: GPLv2 or later
 * Text Domain: traffic-scout-elite
 */

defined('ABSPATH') || exit;

// Autoloader Simples
spl_autoload_register(function ($class) {
    if (strpos($class, 'STSTraffic\\') !== 0) return;
    $file = plugin_dir_path(__FILE__) . 'src/' . str_replace('\\', '/', substr($class, 11)) . '.php';
    if (file_exists($file)) require $file;
});

// Inicialização Épica
add_action('plugins_loaded', function() {
    new \STSTraffic\Core\Tracker();
    if (is_admin()) {
        new \STSTraffic\Admin\Dashboard();
    }
});

// Forjando o Banco de Dados na Ativação
register_activation_hook(__FILE__, function() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Tabela 1: Hits por URL (Ranking)
    $table_stats = $wpdb->prefix . 'sts_traffic_stats';
    $sql1 = "CREATE TABLE $table_stats (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        url_hash varchar(32) NOT NULL,
        url text NOT NULL,
        title text NOT NULL,
        hits bigint(20) DEFAULT 0,
        visit_date date NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_visit (url_hash, visit_date)
    ) $charset_collate;";
    dbDelta($sql1);

    // Tabela 2: Visitantes Únicos (Telemetria Humana)
    $table_visitors = $wpdb->prefix . 'sts_traffic_visitors';
    $sql2 = "CREATE TABLE $table_visitors (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        uid varchar(32) NOT NULL,
        visit_date date NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_user_day (uid, visit_date)
    ) $charset_collate;";
    dbDelta($sql2);
});
