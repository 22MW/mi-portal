<?php
/**
 * Plugin Name: Mi portal
 * Description: Pone una capa cuidada a la página Mi cuenta de WooCommerce: menú agrupado, tarjetas, menú desplegable en móvil y cambio de pestaña sin recargar. No añade pestañas ni funciones; solo cambia el aspecto y permite ordenar, ocultar y renombrar las que ya existen.
 * Version: 1.2.4
 * Author: 22MW
 * Text Domain: mi-portal
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

define('MI_PORTAL_VERSION', '1.2.4');
define('MI_PORTAL_DIR', plugin_dir_path(__FILE__));
define('MI_PORTAL_URL', plugin_dir_url(__FILE__));

// Textos del plugin: los .mo/.po van en languages/ (Loco Translate, WPML String Translation o cualquier otro gestor).
add_action('init', function () {
    load_plugin_textdomain('mi-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }
    require_once MI_PORTAL_DIR . 'includes/class-ajustes.php';
    require_once MI_PORTAL_DIR . 'includes/class-menu.php';
    require_once MI_PORTAL_DIR . 'includes/class-admin.php';

    if (is_admin()) {
        Mi_Portal_Admin::init();
    }
    // Mientras la opción «mi_portal_solo_admin» vale 1, la capa solo la ven los administradores (para probarla en la web real).
    if (get_option('mi_portal_solo_admin') && ! current_user_can('manage_options')) {
        return;
    }
    Mi_Portal_Menu::init();
});
