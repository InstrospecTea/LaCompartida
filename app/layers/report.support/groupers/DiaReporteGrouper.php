<?php
/**
 * Agrupador por Día del reporte
 *
 * * Agrupa por: fecha
 * * Muestra: DATE_FORMAT(fecha, '%d-%m-%Y')
 * * Ordena por:  fecha
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Dia-Reporte
 */
class DiaReporteGrouper extends FilterDependantGrouperTranslator {

	public function getFilterDependences() {
		return array('campo_fecha');
	}

	public function getDateField() {
		switch ($this->filterValues['campo_fecha']) {
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
		return "DATE_FORMAT({$field_name}, '%d-%m-%Y')";
	}

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "DATE_FORMAT(%token%, '%d-%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		return "DATE_FORMAT(%token%, '%d-%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		return "DATE_FORMAT(%token%, '%d-%m-%Y')";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * IMPORTANT!!! Fecha que venga en el filtro campo_fecha
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		return $Criteria->add_select(
			$this->getDateField(),
			'dia_reporte'
		)->add_grouping(
			$this->getDateField()
		)->add_ordering(
			$this->getDateField()
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Fecha del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		return $Criteria->add_select(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getSelectField()
			), 'dia_reporte'
		)->add_grouping(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getSelectField()
			)
		)->add_ordering(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getSelectField()
			)
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Fecha del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		return $Criteria->add_select(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getSelectField()
			), 'dia_reporte'
		)->add_grouping(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getSelectField()
			)
		)->add_ordering(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getSelectField()
			)
		);
	}
}
