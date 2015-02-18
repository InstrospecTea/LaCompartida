<?php

require_once dirname(__FILE__) . '/../conf.php';

/**
 * Esta clase extiende de Objeto
 *
 * De esta forma se puede agregar funcionalidad sin romper las
 * bases del sistema (llámese framework)
 */
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
		if (empty($this->campo_id) || empty($this->campo_glosa)) {
			throw new exception("Imposible Listar $this->tabla");
		}
		if (preg_match('/[\(\.]/', $this->campo_glosa)) { //verifica si es funcion o parte de table.field
			$glosa = $this->campo_glosa;
		} else {
			$glosa = "{$this->tabla}.{$this->campo_glosa}";
		}
		if (!empty($fields)) {
			$fields = ',' . $fields;
		}
		$query = "SELECT
            {$this->tabla}.{$this->campo_id} AS id,
            $glosa AS glosa
            $fields
          FROM {$this->tabla} {$query_extra}";
		$qr = $this->sesion->pdodbh->query($query);
		$respuesta = $qr->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
		$result = array();
		foreach ($respuesta as $key => $value) {
			$result[$key] = $value[0];
		}
		return $result;
	}

}