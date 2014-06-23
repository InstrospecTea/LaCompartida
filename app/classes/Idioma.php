<?php

require_once dirname(__FILE__) . '/../conf.php';

class Idioma extends Objeto {

	public static $llave_carga_masiva = 'glosa_idioma';

	function Idioma($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_idioma';
		$this->campo_id = 'id_idioma';
		$this->campo_glosa = 'glosa_idioma';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	public function Listar($query_extra = '') {
		
		if (preg_match('/[\(\.]/', $this->campo_glosa)) { //verifica si es funcion o parte de table.field
			$glosa = $this->campo_glosa;
		} else {
			$glosa = "{$this->tabla}.{$this->campo_glosa}";
		}
		$query = "SELECT
						{$this->tabla}.codigo_idioma AS id,
						$glosa AS glosa
					FROM {$this->tabla} {$query_extra}";
		$qr = $this->sesion->pdodbh->query($query);
		$usuarios = $qr->fetchAll(PDO::FETCH_ASSOC);
		$respuesta = array();

		$total_usuarios = count($usuarios);
		for ($x = 0; $x < $total_usuarios; ++$x) {
			$respuesta[$usuarios[$x]['id']] = $usuarios[$x]['glosa'];
		}
		return $respuesta;
	}
}

