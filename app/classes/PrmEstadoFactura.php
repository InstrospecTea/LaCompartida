<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmEstadoFactura extends Objeto
{
  public static $llave_carga_masiva = 'codigo';

  function PrmEstadoFactura($sesion, $fields = "", $params = "") {
    $this->tabla = "prm_estado_factura";
    $this->campo_id = "id_estado";
    $this->sesion = $sesion;
    $this->fields = $fields;
    $this->guardar_fecha = false;
  }
}
