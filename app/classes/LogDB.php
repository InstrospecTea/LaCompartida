<?php

require_once dirname(__FILE__) . '/../conf.php'; 



class LogDB extends Objeto {
	var $ArrayMovimientos=array();
	 
	function __construct($sesion, $titulo_tabla, $id_field) {
		$this->tabla = "log_db";
		$this->campo_id = $id_field;
		$this->titulo_tabla = $titulo_tabla;
		$this->sesion = $sesion;
		$query="select log_db.fecha, log_db.campo_tabla,  log_db.usuario,concat(usuario.nombre,' ',usuario.apellido1,' ',usuario.apellido2) as nombre_usuario,  log_db.valor_antiguo, log_db.valor_nuevo 
			from log_db left join usuario on log_db.usuario=usuario.id_usuario where id_field={$id_field} and titulo_tabla='{$titulo_tabla}' order by log_db.fecha desc;";

		$this->ArrayMovimientos=$this->sesion->pdodbh->query($query)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);


	}
 
}