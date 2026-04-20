<?php
/**
 * Plugin Name: Traffic Scout Elite
 * Plugin URI: https://descomplicandoreceitas.com.br
 * Description: Monitoramento ultra-leve de tráfego em tempo real com Social Proof dinâmico. Estilo God of War.
 * Version: 1.0.0
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
