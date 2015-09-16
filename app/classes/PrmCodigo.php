<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmCodigo extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_codigo';
		$this->campo_id = 'codigo';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	function LoadById($id_codigo) {
		$this->campo_id = 'id_codigo';
		return $this->Load($id_codigo);
	}

	/**
	 *
	 * Retorna listado de prm_codigo según grupo deseado
	 *
	 * @param string $grupo grupo a buscar
	 *
	 * @return array $rows contiene arreglo con el grupo prm_codigo deseado
	 */
	public function getCodigosByGrupo($grupo) {
		$criteria = new Criteria($this->sesion);
		$criteria->add_select('P.id_codigo', 'id_codigo')
				->add_select('P.glosa', 'glosa')
				->add_from('prm_codigo P')
				->add_restriction(CriteriaRestriction::equals('grupo', "'$grupo'"))
		 		->add_ordering('P.glosa');
		try {
			$result = $criteria->run();
			$rows = array();

			foreach ($result as $key => $value) {
				$rows[$value['id_codigo']] = $value['glosa'];
			}

			return $rows;

		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}
	}
}
