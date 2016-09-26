<?php
/**
 * Agrupador por Número de Factura:
 *
 * * Agrupa por: factura.numero
 * * Muestra: factura.id_documento_legal + factura.numero
 * * Ordena por: factura.numero
 *
 * Más info en: https://github.com/LemontechSA/ttb/wiki/Reporte-Agrupador:-Estado
 */
class NumeroDocumentoGrouper extends AbstractGrouperTranslator {

	/**
	 * Obtiene el campo por el cual se agrupará la query
	 * @return String Campo por el que se agrupa en par tabla.campo o alias
	 */
	function getGroupField() {
		return "factura.numero";
	}

	/**
	 * Obtiene el campo de grupo que se devolverá en el SELECT de la query
	 * @return String par tabla.campo o alias de función
	 */
	function getSelectField() {
		$usaSerie = Conf::GetConf($this->Session, 'NumeroFacturaConSerie');
		return $usaSerie ? 'factura.numero' : 'factura.numero_sin_serie';
	}
	/**
	 * Obtiene el campo de grupo por el cual se ordenará la query
	 * @return String par tabla.campo o alias de función
	 */
	function getOrderField() {
		$usaSerie = Conf::GetConf($this->Session, 'NumeroFacturaConSerie');
		return $usaSerie ? 'factura.numero' : 'factura.numero_sin_serie';
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Cobros
	 * Glosa del cliente de cada asunto incluido en la liquidación
	 * @return void
	 */

	function translateForCharges(Criteria $Criteria) {
		$Criteria
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'numero_documento')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}

	/**
	 * Traduce los keys de agrupadores a campos para la query de Trámites
	 * Glosa del cliente del asunto del trámite
	 * @return void
	 */
	function translateForErrands(Criteria $Criteria) {
		$Criteria
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'numero_documento')
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
			->add_select("IFNULL({$this->getSelectField()}, 'Indefinido')", 'numero_documento')
			->add_grouping($this->getGroupField())
			->add_ordering($this->getOrderField());

		return $Criteria;
	}
}
