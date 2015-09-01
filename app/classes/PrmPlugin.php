<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmPlugin extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_plugin';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	/**
	 *
	 * Permite saber si un plugin está activo.
	 *
	 * @param mixed $nombre recibe el nombre de el o los plugins a revisar si están activos
	 *
	 * @return bool Retorna TRUE | FALSE para saber si cuenta con algún plugin activo
	 */
	public function isActive($nombre) {
		$criteria = new Criteria($this->sesion);
		$criteria->add_select('COUNT(*)', 'total')
				->add_from($this->tabla)
				->add_restriction(CriteriaRestriction::equals('activo', 1));

		if (is_array($nombre)) {
			$criteria->add_restriction(CriteriaRestriction::in('archivo_nombre', $nombre));
		} else {
			$criteria->add_restriction(CriteriaRestriction::equals('archivo_nombre', "'$nombre'"));
		}

		try {
			$result = $criteria->run();
			return sizeof($result[0]['total']) > 0 ? TRUE : FALSE;

		} catch (Exception $e) {
			echo "Error: {$e} {$criteria->__toString()}";
		}
	}
}
