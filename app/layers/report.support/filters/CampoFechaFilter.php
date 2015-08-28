<?php

class CampoFechaFilter extends AbstractDependantFilterTranslator {

	function getFieldName() {
		switch ($this->getParentFilter()) {
			case 'cobro':
				$field_name = 'cobro.fecha_fin';
				break;
			case 'emision':
				$field_name = 'cobro.fecha_emision';
				break;
			case 'envio':
				$field_name = 'cobro.fecha_enviado_cliente';
				break;
			case 'facturacion':
				$field_name = 'cobro.fecha_facturacion';
				break;
			default:
				$field_name = 'trabajo.fecha';
				break;
		}

		return $field_name;
	}

	function getParentFilter() {
		return $this->parent;
	}

	function setParentFilterData($data) {
		$this->parent = $data;
	}

	static function getNameOfDependantFilters() {
		return array('fecha_ini', 'fecha_fin');
	}

	function translateForCharges(Criteria $Criteria) {
		// $filters = $this->getFilterData();

		// if ($this->getParentFilter() != 'trabajo') {
		// 	$field_name = $this->getFieldName();
		// } else {
		// 	$field_name = 'cobro.fecha_fin';
		// }

		// if ($this->getParentFilter() == 'cobro') {
		// 	$Criteria->add_restriction(
		// 		CriteriaRestriction::or_clause(
		// 			$Criteria->add_restriction(CriteriaRestriction::between($field_name, "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']} 23:59:59'")),
		// 			CriteriaRestriction::and_clause(
		// 				CriteriaRestriction::or_clause(
		// 					CriteriaRestriction::is_null($field_name),
		// 					CriteriaRestriction::equals($field_name, "'00-00-0000'")
		// 				),
		// 				CriteriaRestriction::between('cobro.fecha_creacion', "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']} 23:59:59'")
		// 			)
		// 		)
		// 	);
		// } else {
		// 	$Criteria->add_restriction(CriteriaRestriction::between($field_name, "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']} 23:59:59'"));
		// }

		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		$filters = $this->getFilterData();

		if ($this->getParentFilter() != 'trabajo') {
			$field_name = $this->getFieldName();
		} else {
			$field_name = 'tramite.fecha';
		}

		$Criteria->add_restriction(CriteriaRestriction::between($field_name, "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']} 23:59:59'"));

		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$filters = $this->getFilterData();

		$Criteria->add_restriction(CriteriaRestriction::between($this->getFieldName(), "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']} 23:59:59'"));

		return $Criteria;
	}

}
