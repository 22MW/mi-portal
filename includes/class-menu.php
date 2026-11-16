<?php
defined('ABSPATH') || exit;

/**
 * Menú de Mi cuenta: orden, nombres y pestañas ocultas según los ajustes, menú agrupado y archivos de aspecto.
 */
class Mi_Portal_Menu
{
    public static function init()
    {
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'filtrar'], 999);
        add_filter('woocommerce_locate_template', [__CLASS__, 'plantilla'], 10, 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'archivos']);
    }

    public static function filtrar($items)
    {
        if (Mi_Portal_Ajustes::$sin_filtro) {
            return $items;
        }
        $nuevos = [];
        foreach (Mi_Portal_Ajustes::combinar($items) as $endpoint => $fila) {
            if ($fila['visible']) {
                $nuevos[$endpoint] = $fila['etiqueta'];
            }
        }

        return $nuevos;
    }

    public static function plantilla($plantilla, $nombre)
    {
        if ('myaccount/navigation.php' === $nombre) {
            return MI_PORTAL_DIR . 'templates/navigation.php';
        }
        if ('myaccount/dashboard.php' === $nombre) {
            return MI_PORTAL_DIR . 'templates/dashboard.php';
        }

        return $plantilla;
    }

    /** Pestañas visibles repartidas por grupos: [ [titulo, items[endpoint => etiqueta]], … ]. */
    public static function agrupar(array $items)
    {
        $titulos = Mi_Portal_Ajustes::obtener()['grupos'];
        $filas = Mi_Portal_Ajustes::combinar($items);
        $grupos = [];
        foreach ($items as $endpoint => $etiqueta) {
            $g = isset($filas[$endpoint]) ? $filas[$endpoint]['grupo'] : 'g1';
            $grupos[$g]['titulo'] = isset($titulos[$g]) ? $titulos[$g] : '';
            $grupos[$g]['items'][$endpoint] = $etiqueta;
        }
        uksort($grupos, function ($a, $b) {
            return array_search($a, Mi_Portal_Ajustes::GRUPOS, true) <=> array_search($b, Mi_Portal_Ajustes::GRUPOS, true);
        });

        return array_values($grupos);
    }

    public static function archivos()
    {
        if (! function_exists('is_account_page') || ! is_account_page()) {
            return;
        }
        wp_enqueue_style('mi-portal', MI_PORTAL_URL . 'assets/css/mi-portal.css', [], MI_PORTAL_VERSION);
        wp_enqueue_script('mi-portal', MI_PORTAL_URL . 'assets/js/mi-portal.js', [], MI_PORTAL_VERSION, true);
        wp_localize_script('mi-portal', 'miPortal', [
            'base'     => wp_parse_url(wc_get_page_permalink('myaccount'), PHP_URL_PATH),
            'sinFetch' => self::endpoints_sin_fetch(),
        ]);
    }

    /**
     * Slugs de endpoint de Mi cuenta que se abren con recarga completa (su contenido depende de scripts
     * que se encolan en el pie de página y con el cambio por fetch no llegarían). Hoy: «ver pedido».
     * Se lee el slug vigente de WooCommerce, que respeta los ajustes y el idioma activo (WPML).
     * Para añadir otros endpoints: add_filter('mi_portal_endpoints_sin_fetch', fn($slugs) => array_merge($slugs, ['mi-endpoint']));
     */
    private static function endpoints_sin_fetch()
    {
        $vars  = WC()->query->get_query_vars();
        $slugs = ! empty($vars['view-order']) ? [$vars['view-order']] : [];
        $slugs = apply_filters('mi_portal_endpoints_sin_fetch', $slugs);

        return array_values(array_filter(array_map('strval', (array) $slugs)));
    }
}
