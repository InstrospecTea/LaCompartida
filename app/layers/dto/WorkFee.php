<?php

class WorkFee extends Entity {

  /**
   * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
   * @return string
   */
  public function getIdentity() {
    return '';
  }

  /**
   * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
   * a esta clase.
   * @return string
   */
  public function getPersistenceTarget() {
    return 'trabajo_tarifa';
  }

  /**
   * Obtiene los campos por defecto que debe llevar la entidad.
   * @return array
   */
  public function getTableDefaults() {
    return array();
  }

  protected function getFixedDefaults() {
    return array();
  }
}