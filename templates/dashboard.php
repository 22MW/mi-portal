<?php
/**
 * Escritorio de Mi cuenta (sustituye myaccount/dashboard.php de WooCommerce): el saludo y una tarjeta de entrada
 * por cada pestaña visible del menú. Sin el párrafo de explicación de WooCommerce.
 *
 * @package Mi_Portal
 */

defined('ABSPATH') || exit;

$permitido = ['a' => ['href' => []]];
?>
<p class="mp-saludo">
    <?php
    printf(
        wp_kses(__('Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce'), $permitido),
        '<strong>' . esc_html($current_user->display_name) . '</strong>',
        esc_url(wc_logout_url())
    );
    ?>
</p>

<?php
$tarjetas = [];
foreach (Mi_Portal_Menu::agrupar(wc_get_account_menu_items()) as $g) {
    foreach ($g['items'] as $endpoint => $etiqueta) {
        if (! in_array($endpoint, ['dashboard', 'customer-logout'], true)) {
            $tarjetas[$endpoint] = $etiqueta;
        }
    }
}
if ($tarjetas) :
    ?>
<ul class="mp-tarjetas">
    <?php foreach ($tarjetas as $endpoint => $etiqueta) : ?>
    <li><a class="mp-tarjeta" href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"><?php echo esc_html($etiqueta); ?></a></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php
do_action('woocommerce_account_dashboard');
do_action('woocommerce_before_my_account'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
do_action('woocommerce_after_my_account'); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
