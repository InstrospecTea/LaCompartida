<?php

require_once dirname(__FILE__) . '/../conf.php';

class LogDB extends Objeto {
	function __construct($sesion, $fields = "", $params = "") {
		$this->tabla = "log_db";
		$this->campo_id = 'id';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	function Movimientos($titulo_tabla, $id_field){
		$query="select log_db.fecha, log_db.campo_tabla,  log_db.usuario,concat(usuario.nombre,' ',usuario.apellido1,' ',usuario.apellido2) as nombre_usuario,  log_db.valor_antiguo, log_db.valor_nuevo, log_db.url
			from log_db left join usuario on log_db.usuario=usuario.id_usuario where id_field={$id_field} and titulo_tabla='{$titulo_tabla}' order by log_db.fecha desc;";

		return UtilesApp::utf8izar($this->sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP));
	}

	function Loggear($tabla, $id, $campo, $valor, $valor_original){
		if($valor != $valor_original){
			$this->fields[$this->campo_id] = null;
			$this->Edit('id_field', $id);
			$this->Edit('titulo_tabla', $tabla);
			$this->Edit('campo_tabla', $campo);
			$this->Edit('fecha', gmdate('Y-m-d H:i:s'));
			$this->Edit('usuario', $this->sesion->usuario->fields['id_usuario']);
			$this->Edit('valor_antiguo', $valor_original);
			$this->Edit('valor_nuevo', $valor);
			$this->Edit('url', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
			return $this->Write();
		}
		return false;
	}
}