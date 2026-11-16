<?php
defined('ABSPATH') || exit;

/**
 * Pantalla Ajustes > Mi portal: ordenar, ocultar y renombrar las pestañas de Mi cuenta y poner nombre a los grupos.
 */
class Mi_Portal_Admin
{
    const PAGINA = 'mi-portal';

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'registrar']);
        add_action('admin_post_mi_portal_restaurar', [__CLASS__, 'restaurar']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'archivos']);
    }

    public static function menu()
    {
        add_options_page(__('Mi portal', 'mi-portal'), __('Mi portal', 'mi-portal'), 'manage_options', self::PAGINA, [__CLASS__, 'pantalla']);
    }

    public static function registrar()
    {
        register_setting('mi_portal', Mi_Portal_Ajustes::OPCION, ['sanitize_callback' => [__CLASS__, 'limpiar']]);
    }

    public static function archivos($hook)
    {
        if ('settings_page_' . self::PAGINA !== $hook) {
            return;
        }
        wp_enqueue_script('mi-portal-admin', MI_PORTAL_URL . 'assets/js/mi-portal-admin.js', ['jquery', 'jquery-ui-sortable'], MI_PORTAL_VERSION, true);
        wp_enqueue_style('mi-portal-admin', MI_PORTAL_URL . 'assets/css/mi-portal-admin.css', [], MI_PORTAL_VERSION);
    }

    /** El orden de las filas es el orden en que llegan: no hace falta un campo de orden. */
    public static function limpiar($entrada)
    {
        $limpio = Mi_Portal_Ajustes::por_defecto();
        $entrada = is_array($entrada) ? $entrada : [];
        foreach (Mi_Portal_Ajustes::GRUPOS as $g) {
            $limpio['grupos'][$g] = isset($entrada['grupos'][$g]) ? sanitize_text_field($entrada['grupos'][$g]) : '';
        }
        $items = isset($entrada['items']) && is_array($entrada['items']) ? $entrada['items'] : [];
        foreach ($items as $endpoint => $a) {
            $endpoint = preg_replace('/[^a-z0-9_-]/i', '', (string) $endpoint);
            if ('' === $endpoint || ! is_array($a)) {
                continue;
            }
            $grupo = isset($a['grupo']) && in_array($a['grupo'], Mi_Portal_Ajustes::GRUPOS, true) ? $a['grupo'] : Mi_Portal_Ajustes::grupo_por_defecto($endpoint);
            $limpio['items'][$endpoint] = [
                'visible'  => empty($a['visible']) ? 0 : 1,
                'etiqueta' => isset($a['etiqueta']) ? sanitize_text_field($a['etiqueta']) : '',
                'grupo'    => $grupo,
            ];
        }

        return $limpio;
    }

    public static function restaurar()
    {
        check_admin_referer('mi_portal_restaurar');
        if (current_user_can('manage_options')) {
            delete_option(Mi_Portal_Ajustes::OPCION);
        }
        wp_safe_redirect(add_query_arg(['page' => self::PAGINA, 'restaurado' => 1], admin_url('options-general.php')));
        exit;
    }

    public static function pantalla()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $ajustes = Mi_Portal_Ajustes::obtener();
        $filas = Mi_Portal_Ajustes::combinar(Mi_Portal_Ajustes::originales());
        $nombre = Mi_Portal_Ajustes::OPCION;
        ?>
        <div class="wrap mp-admin">
            <h1><?php esc_html_e('Mi portal', 'mi-portal'); ?></h1>
            <?php if (isset($_GET['restaurado'])) : // phpcs:ignore WordPress.Security.NonceVerification ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Ajustes restaurados.', 'mi-portal'); ?></p></div>
            <?php endif; ?>
            <p><?php esc_html_e('Ordena, oculta y renombra las pestañas de Mi cuenta. Arrastra las filas para cambiar el orden.', 'mi-portal'); ?></p>
            <p class="description"><?php esc_html_e('Ocultar una pestaña solo la quita del menú: quien conozca su dirección todavía puede abrirla.', 'mi-portal'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('mi_portal'); ?>

                <h2><?php esc_html_e('Grupos del menú', 'mi-portal'); ?></h2>
                <p class="description"><?php esc_html_e('Cada grupo lleva un título sobre sus pestañas. Un grupo sin título se muestra sin encabezado y uno sin pestañas no se muestra.', 'mi-portal'); ?></p>
                <table class="form-table" role="presentation">
                    <?php foreach (Mi_Portal_Ajustes::GRUPOS as $i => $g) : ?>
                    <tr>
                        <th scope="row"><label for="mp-g-<?php echo esc_attr($g); ?>"><?php echo esc_html(sprintf(/* translators: %d: número de grupo */ __('Grupo %d', 'mi-portal'), $i + 1)); ?></label></th>
                        <td><input type="text" class="regular-text" id="mp-g-<?php echo esc_attr($g); ?>" name="<?php echo esc_attr($nombre); ?>[grupos][<?php echo esc_attr($g); ?>]" value="<?php echo esc_attr($ajustes['grupos'][$g]); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <h2><?php esc_html_e('Pestañas', 'mi-portal'); ?></h2>
                <table class="widefat striped mp-admin__tabla">
                    <thead><tr><th></th><th><?php esc_html_e('Visible', 'mi-portal'); ?></th><th><?php esc_html_e('Pestaña', 'mi-portal'); ?></th><th><?php esc_html_e('Nombre en el menú', 'mi-portal'); ?></th><th><?php esc_html_e('Grupo', 'mi-portal'); ?></th></tr></thead>
                    <tbody id="mp-filas">
                    <?php foreach ($filas as $endpoint => $f) : ?>
                        <tr>
                            <td class="mp-admin__asa" title="<?php esc_attr_e('Arrastrar', 'mi-portal'); ?>">&#8942;&#8942;</td>
                            <td><input type="checkbox" name="<?php echo esc_attr($nombre); ?>[items][<?php echo esc_attr($endpoint); ?>][visible]" value="1" <?php checked($f['visible']); ?>></td>
                            <td><strong><?php echo esc_html($f['original']); ?></strong> <code><?php echo esc_html($endpoint); ?></code></td>
                            <td><input type="text" class="regular-text" name="<?php echo esc_attr($nombre); ?>[items][<?php echo esc_attr($endpoint); ?>][etiqueta]" value="<?php echo esc_attr($f['etiqueta'] === $f['original'] ? '' : $f['etiqueta']); ?>" placeholder="<?php echo esc_attr($f['original']); ?>"></td>
                            <td>
                                <select name="<?php echo esc_attr($nombre); ?>[items][<?php echo esc_attr($endpoint); ?>][grupo]">
                                    <?php foreach (Mi_Portal_Ajustes::GRUPOS as $i => $g) : ?>
                                    <option value="<?php echo esc_attr($g); ?>" <?php selected($f['grupo'], $g); ?>><?php echo esc_html('' !== $ajustes['grupos'][$g] ? $ajustes['grupos'][$g] : sprintf(__('Grupo %d', 'mi-portal'), $i + 1)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button(__('Guardar cambios', 'mi-portal')); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="mi_portal_restaurar">
                <?php wp_nonce_field('mi_portal_restaurar'); ?>
                <?php submit_button(__('Restaurar valores de fábrica', 'mi-portal'), 'secondary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }
}
