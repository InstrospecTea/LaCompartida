<?php

require_once dirname(__FILE__) . '/../conf.php';

class ReporteProductividad {

  public static $configuracion_reporte = array(
    array(
      'field' => 'dato',
      'title' => 'Cobro',
      'extras' => array(
        'width' => 8,
        'attrs' => 'style="text-align:right"'
      )
    ),
    array(
      'field' => 'dato2',
      'title' => 'Cliente',
      'extras' => array(
        'width' => 20,
        'attrs' => 'style="text-align:left"'
      )
    )
  );
}