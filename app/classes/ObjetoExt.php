<?php

require_once dirname(__FILE__) . '/../conf.php';

class ObjetoExt extends Objeto {

	/**
	 * Lista la tabla con los campos indicados en la clase
	 * devuelve un array con llave campo_id y valor campo_glosa
	 * @param string $query_extra
	 * @param string $fields
	 * @return array
	 * @throws exception
	 */
	public function ListarExt($query_extra = '', $fields = '') {
		$query = $this->queryListar($query_extra, $fields);
		$qr = $this->sesion->pdodbh->query($query);
		return $this->statementToArrayByKey($qr->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP));
	}

	public function queryListar($query_extra, $fields)
	{
		if (empty($this->campo_id) || empty($this->campo_glosa)) {
			throw new Exception("Imposible Listar $this->tabla");
		}

		if (preg_match('/[\(\.]/', $this->campo_glosa)) { //verifica si es funcion o parte de table.field
			$glosa = $this->campo_glosa;
		} else {
			$glosa = "{$this->tabla}.{$this->campo_glosa}";
		}

		if (!empty($fields)) {
			$fields = ',' . $fields;
		}

		$query = "SELECT {$this->tabla}.{$this->campo_id} AS id, $glosa AS glosa $fields FROM {$this->tabla} {$query_extra}";

		return $query;
	}


	public function statementToArrayByKey($statement)
	{
		$result = array();

		foreach ($statement as $key => $value) {
			$result[$key] = $value[0];
		}
		return $result;
	}

}
