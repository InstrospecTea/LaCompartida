<?php
require_once dirname(__FILE__) . '/../conf.php';

class ClienteSeguimiento extends Objeto
{
	var $campos = array(
		'id',
		'codigo_cliente',
		'id_usuario',
		'fecha',
		'comentario'
	);

	function ClienteSeguimiento($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cliente_seguimiento";
		$this->campo_id = "id";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->editable_fields = $this->campos;
		$this->guardar_fecha = true;
	}

	function Prepare() {
		if (empty($this->fields['id_usuario'])) {
			$this->fields['id_usuario'] = $this->sesion->usuario->fields['id_usuario'];
		}
	}

	function SearchQuery() {
		$query = "SELECT SQL_CALC_FOUND_ROWS
					cs.id,
					cs.fecha_creacion,
					cs.comentario,
					CONCAT(u.apellido1, ', ', u.nombre) AS nombre_usuario,
					CONCAT(LEFT(u.nombre, 1), LEFT(u.apellido1, 1), LEFT(u.apellido2, 1)) AS iniciales_usuario,
					u.username AS username_usuario,
					cs.codigo_cliente
				FROM cliente_seguimiento cs
				INNER JOIN usuario u ON u.id_usuario = cs.id_usuario";

		$wheres = array();

		if (!empty($this->fields['codigo_cliente'])) {
			$wheres[] = "cs.codigo_cliente = '{$this->fields['codigo_cliente']}'";
		}

		if (count($wheres) > 0) {
			$query .= " WHERE " . implode(' AND ', $wheres);
		}

		$query .= " ORDER BY cs.fecha_creacion DESC";

		return $query;
	}

	function FindAll($params = null) {
		$statement = $this->sesion->pdodbh->prepare($this->SearchQuery());
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}
}
