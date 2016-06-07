<?php
/**
 * Agrupador por Mes del reporte
 *
 * - Agrupa por: DATE_FORMAT(fecha, '%m-%Y')
 * - Muestra: DATE_FORMAT(fecha, '%m-%Y')
 * - Ordena por: DATE_FORMAT(fecha, '%Y-%m')
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Mes-Reporte
 */
class MesReporteGrouper extends FilterDependantGrouperTranslator {

	/**
	 * Obtiene un array que explicita a aquellos filtros de los que este
	 * agrupador depende.
	 */
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
		return "DATE_FORMAT({$field_name}, '%m-%Y')";
	}
	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "DATE_FORMAT(%token%, '%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return "DATE_FORMAT(%token%, '%m-%Y')";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return "DATE_FORMAT(%token%, '%Y-%m')";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * IMPORTANT!!! mes-a�o de la Fecha que venga en el filtro campo_fecha
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		return $Criteria->add_select(
			$this->getDateField(),
			'mes_reporte'
		)->add_grouping(
			$this->getGroupField()
		)->add_ordering(
			$this->getOrderField()
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * mes-a�o de la fecha del tr�mite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		return $Criteria->add_select(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getSelectField()
			), 'mes_reporte'
		)->add_grouping(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getGroupField()
			)
		)->add_ordering(
			str_replace(
				"%token%",
				"tramite.fecha",
				$this->getOrderField()
			)
		);
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * mes-a�o de la fecha del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		return $Criteria->add_select(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getSelectField()
			), 'mes_reporte'
		)->add_grouping(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getGroupField()
			)
		)->add_ordering(
			str_replace(
				"%token%",
				"trabajo.fecha",
				$this->getOrderField()
			)
		);
	}
}
