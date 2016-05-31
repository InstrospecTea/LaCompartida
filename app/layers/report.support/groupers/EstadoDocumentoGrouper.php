<?php
/**
 * Agrupador por Estado del Documento Tributario:
 *
 * * Agrupa por: factura.anulado
 * * Muestra: cobro.estado
 * * Ordena por: cobro.estado
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Estado
 */
class EstadoDocumentoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return 'factura.estado_documento';
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return "factura.estado_documento";
	}
	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return $this->getSelectField();
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del cliente de cada asunto incluido en la liquidaci�n
	 * @return void
	 */

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'estado_documento')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * Glosa del cliente del asunto del tr�mite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'estado_documento')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Glosa del cliente del asunto del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'estado_documento')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField(), 'DESC');

		return $Criteria;
	}
}
