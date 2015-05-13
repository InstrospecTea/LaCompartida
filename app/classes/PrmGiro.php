<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmGiro extends Objeto
{
	public function __construct($Sesion, $fields = '', $params = '')
	{
		$this->tabla = 'prm_giro';
		$this->campo_id = 'id_giro';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}
}
