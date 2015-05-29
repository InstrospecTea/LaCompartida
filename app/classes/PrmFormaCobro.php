<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmFormaCobro extends Objeto {

  public function __construct($sesion, $fields = '', $params = '') {
    $this->tabla = 'prm_forma_cobro';
    $this->campo_id = 'forma_cobro';
    $this->campo_glosa = 'descripcion';
    $this->sesion = $sesion;
    $this->fields = $fields;
  }

}
