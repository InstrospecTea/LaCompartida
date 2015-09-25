<?php
/**
 * Agrupador por Solicitante del trabajo o tr�mites
 *
 * * Agrupa por: solicitante
 * * Muestra: solicitante
 * * Ordena por:  solicitante
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Solicitante
 */
class SolicitanteGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "solicitante";
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return "solicitante";
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return "solicitante";
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * $this->getUndefinedField()
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getUndefinedField(), 'solicitante')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * Solicitante del tr�mite tramite.solicitante
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select("tramite.{$this->getSelectField()}", 'solicitante')
			->add_grouping("tramite.{$this->getGroupField()}")
			->add_ordering("tramite.{$this->getOrderField()}");

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Solicitante del trabajo trabajo.solicitante
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select("trabajo.{$this->getSelectField()}", 'solicitante')
			->add_grouping("trabajo.{$this->getGroupField()}")
			->add_ordering("trabajo.{$this->getOrderField()}");

		return $Criteria;
	}
}
