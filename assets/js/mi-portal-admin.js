/* Mi portal · administración: las filas de las pestañas se reordenan arrastrando. */
jQuery(function ($) {
  $('#mp-filas').sortable({ handle: '.mp-admin__asa', axis: 'y', helper: function (e, tr) {
    var anchos = tr.children();
    var copia = tr.clone();
    copia.children().each(function (i) { $(this).width(anchos.eq(i).width()); });
    return copia;
  } });
});
