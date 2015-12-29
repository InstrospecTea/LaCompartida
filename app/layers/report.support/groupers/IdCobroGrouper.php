<?php
/**
 * Agrupador por Id Cobro:
 *
 * * Agrupa por: cobro.id_cobro o Indefinido
 * * Muestra: cobro.id_cobro o Indefinido
 * * Ordena por: cobro.id_cobro
 *
 * M�s info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Id-Cobro
 */
class IdCobroGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupar� la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		$undefined = $this->getUndefinedField();
		return "IFNULL(cobro.id_cobro, $undefined)";
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
		return 'cobro.id_cobro';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * id cobro del cobro
	 * @return void
	 */
	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Tr�mites
	 * id cobro del tr�mite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trabajos
	 * id cobro del trabajo
	 * @return void
	 */
	function translateForWorks(Criteria $Criteria) {
		$Criteria
			->add_select($this->getSelectField(), 'id_cobro')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
