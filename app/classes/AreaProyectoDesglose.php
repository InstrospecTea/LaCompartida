<?php
require_once dirname(__FILE__) . '/../conf.php';

class AreaProyectoDesglose extends Objeto
{
	public function AreaProyectoDesglose($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_area_proyecto_desglose';
		$this->campo_id = 'id_area_proyecto_desglose';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
}
