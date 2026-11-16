<?php
defined('ABSPATH') || exit;

/**
 * Ajustes guardados (una sola opción) y lista de pestañas de Mi cuenta ya combinada con ellos.
 */
class Mi_Portal_Ajustes
{
    const OPCION = 'mi_portal_ajustes';
    const GRUPOS = ['g1', 'g2', 'g3', 'g4'];

    /** Evita que nuestro propio filtro actúe cuando pedimos las pestañas originales. */
    public static $sin_filtro = false;

    /** Contexto de WPML (String Translation) para los nombres que se escriben en el panel. */
    const CONTEXTO_WPML = 'Mi portal (ajustes)';

    /**
     * Traduce con WPML un nombre guardado en los ajustes (pestaña renombrada o título de grupo).
     * Sin WPML no hay filtro y devuelve el mismo texto.
     */
    public static function traducir($texto, $nombre)
    {
        return '' === $texto ? $texto : apply_filters('wpml_translate_single_string', $texto, self::CONTEXTO_WPML, $nombre);
    }

    /** Da de alta en WPML los nombres escritos en el panel para que se puedan traducir. Sin WPML no hace nada. */
    public static function registrar_cadenas(array $ajustes)
    {
        foreach ((array) ($ajustes['grupos'] ?? []) as $g => $titulo) {
            if ('' !== $titulo) {
                do_action('wpml_register_single_string', self::CONTEXTO_WPML, 'grupo_' . $g, $titulo);
            }
        }
        foreach ((array) ($ajustes['items'] ?? []) as $endpoint => $a) {
            if (! empty($a['etiqueta'])) {
                do_action('wpml_register_single_string', self::CONTEXTO_WPML, 'pestana_' . $endpoint, $a['etiqueta']);
            }
        }
    }

    public static function por_defecto()
    {
        return [
            'grupos' => ['g1' => __('Mi cuenta', 'mi-portal'), 'g2' => __('Sesión', 'mi-portal'), 'g3' => '', 'g4' => ''],
            'items'  => [],
        ];
    }

    public static function obtener()
    {
        $guardado = get_option(self::OPCION, []);

        return wp_parse_args(is_array($guardado) ? $guardado : [], self::por_defecto());
    }

    public static function grupo_por_defecto($endpoint)
    {
        return 'customer-logout' === $endpoint ? 'g2' : 'g1';
    }

    /** Pestañas tal como las deja WooCommerce y los demás plugins, sin tocar. */
    public static function originales()
    {
        self::$sin_filtro = true;
        $items = wc_get_account_menu_items();
        self::$sin_filtro = false;

        return $items;
    }

    /**
     * Pestañas con su ajuste: endpoint => [etiqueta, original, visible, grupo]. Van ordenadas por grupo y, dentro de cada grupo,
     * por el orden guardado. Las pestañas nuevas (de otros plugins) se añaden visibles al final del primer grupo.
     */
    public static function combinar(array $originales)
    {
        $ajustes = self::obtener();
        $guardados = is_array($ajustes['items']) ? $ajustes['items'] : [];
        $filas = [];
        $n = 0;
        foreach ($guardados as $endpoint => $a) {
            if (isset($originales[$endpoint])) {
                $filas[$endpoint] = self::fila($endpoint, $originales[$endpoint], $a, $n++);
            }
        }
        foreach ($originales as $endpoint => $etiqueta) {
            if (! isset($filas[$endpoint])) {
                $filas[$endpoint] = self::fila($endpoint, $etiqueta, [], $n++);
            }
        }
        uasort($filas, function ($a, $b) {
            return [array_search($a['grupo'], self::GRUPOS, true), $a['orden']] <=> [array_search($b['grupo'], self::GRUPOS, true), $b['orden']];
        });

        return $filas;
    }

    private static function fila($endpoint, $original, $a, $orden)
    {
        $grupo = isset($a['grupo']) && in_array($a['grupo'], self::GRUPOS, true) ? $a['grupo'] : self::grupo_por_defecto($endpoint);

        return [
            'original' => $original,
            'etiqueta' => isset($a['etiqueta']) && '' !== $a['etiqueta'] ? $a['etiqueta'] : $original,
            'visible'  => ! isset($a['visible']) || (bool) $a['visible'],
            'grupo'    => $grupo,
            'orden'    => $orden,
        ];
    }
}
