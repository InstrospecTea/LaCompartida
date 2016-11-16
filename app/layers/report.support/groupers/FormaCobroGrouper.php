<?php
/**
 * Agrupador por Forma de Cobro:
 *
 * * Agrupa por: cobro.forma_cobro o Indefinido
 * * Muestra: cobro.forma_cobro o Indefinido
 * * Ordena por: cobro.forma_cobro o Indefinido
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Forma-Cobro
 */
class FormaCobroGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "IFNULL(cobro.forma_cobro, contrato.forma_cobro)";
	}

	/**
	 * Obtiene el campo de grupo que se devolver� en el SELECT de la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getSelectField() {
		return $this->getGroupField();
	}

	/**
	 * Obtiene el campo de grupo por el cual se ordenar� la query
	 * @return String par tabla.campo o alias de funci�n
	 */
	function getOrderField() {
		return $this->getGroupField();
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Forma de cobro del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * Forma de cobro del tr�mite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * Forma de cobro del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'forma_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
