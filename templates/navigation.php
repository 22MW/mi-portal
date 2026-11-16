<?php
/**
 * Menú de Mi cuenta agrupado (sustituye myaccount/navigation.php de WooCommerce).
 *
 * @package Mi_Portal
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_navigation');

$grupos = Mi_Portal_Menu::agrupar(wc_get_account_menu_items());
$actual = '';
foreach ($grupos as $g) {
    foreach ($g['items'] as $endpoint => $etiqueta) {
        if (wc_is_current_account_menu_item($endpoint)) {
            $actual = $etiqueta;
        }
    }
}
?>
<nav class="woocommerce-MyAccount-navigation mp-nav" aria-label="<?php esc_attr_e('Páginas de la cuenta', 'mi-portal'); ?>">
    <ul class="mp-lista">
        <?php foreach ($grupos as $g) : ?>
            <?php if ('' !== $g['titulo']) : ?>
        <li class="mp-grupo" aria-hidden="true"><?php echo esc_html($g['titulo']); ?></li>
            <?php endif; ?>
            <?php foreach ($g['items'] as $endpoint => $etiqueta) : ?>
        <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"<?php echo wc_is_current_account_menu_item($endpoint) ? ' aria-current="page"' : ''; ?>><?php echo esc_html($etiqueta); ?></a>
        </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>

    <div class="mp-movil">
        <button class="mp-movil__toggle" type="button" aria-expanded="false" aria-controls="mpPanel">
            <span class="mp-movil__actual"><?php echo esc_html($actual ? $actual : __('Mi cuenta', 'mi-portal')); ?></span>
            <svg class="mp-movil__flecha" viewBox="0 0 10 6" fill="none" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="mp-movil__panel" id="mpPanel" hidden>
            <?php foreach ($grupos as $g) : ?>
                <?php if ('' !== $g['titulo']) : ?>
            <p class="mp-movil__grupo"><?php echo esc_html($g['titulo']); ?></p>
                <?php endif; ?>
            <ul>
                <?php foreach ($g['items'] as $endpoint => $etiqueta) : ?>
                <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"><?php echo esc_html($etiqueta); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
