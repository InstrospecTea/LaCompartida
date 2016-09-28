<?php

/**
 * Corresponde a la clase base para los calculadores que
 * requieren ser devueltos de acuerdo a una moneda
 */
abstract class AbstractCurrencyDataCalculator extends AbstractDataCalculator {

	protected $currencyId;

	/**
	 * Constructor
	 * @param Sesion $Session       La sesi�n para el acceso a datos
	 * @param [type] $filtersFields Los campos/keys por los que se debe filtrar y sus valores
	 * @param [type] $grouperFields Los campos/keys por los que se debe agrupar
	 * @param [type] $currencyId    La moneda en la que se devolver�n los valores
	 */
	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $options, $currencyId) {
		parent::__construct($Session, $filtersFields, $grouperFields, $options);
		$this->currencyId = $currencyId;
		$this->Session = $Session;
	}

	/**
	 * Agrega las relaciones y selecciones para obtener las monedas
	 * y sus tipos de cambio para convertir los valores a devolver
	 *
	 * @param Criteria $Criteria La Query a la que se le agregar� las relaciones y selects
	 */
	function addCurrencyToQuery(Criteria $Criteria) {

		$Criteria->add_left_join_with(array('prm_moneda', 'moneda_base'), CriteriaRestriction::equals('moneda_base.moneda_base', 1));

		$currencySource = $this->getCurrencySource();

		if (!Conf::getConf($this->Session, 'NuevoModuloFactura')) {
			$currencySource = 'documento';
		}

		if ($currencySource == 'documento' || $currencySource == 'factura') {
			// moneda del documento
			$Criteria->add_left_join_with('documento', CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('documento.id_cobro', 'cobro.id_cobro'),
				CriteriaRestriction::equals('documento.tipo_doc', "'N'")
			));

			$Criteria->add_left_join_with(array("{$currencySource}_moneda", 'cobro_moneda_documento'), CriteriaRestriction::and_clause(
				CriteriaRestriction::equals("cobro_moneda_documento.id_{$currencySource}", "{$currencySource}.id_{$currencySource}"),
				CriteriaRestriction::equals('cobro_moneda_documento.id_moneda', 'documento.id_moneda')
			));

		}

		// moneda de visualizaci�n
		$Criteria->add_left_join_with(array("{$currencySource}_moneda", 'cobro_moneda'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda.id_{$currencySource}", "{$currencySource}.id_{$currencySource}"),
			CriteriaRestriction::equals('cobro_moneda.id_moneda', $this->currencyId)
		));

		//moneda del cobro
		$Criteria->add_left_join_with(array("{$currencySource}_moneda", 'cobro_moneda_cobro'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda_cobro.id_{$currencySource}", "{$currencySource}.id_{$currencySource}"),
			CriteriaRestriction::equals('cobro_moneda_cobro.id_moneda', 'cobro.id_moneda')
		));

		//moneda_base
		$Criteria->add_left_join_with(array("{$currencySource}_moneda", 'cobro_moneda_base'), CriteriaRestriction::and_clause(
			CriteriaRestriction::equals("cobro_moneda_base.id_{$currencySource}", "{$currencySource}.id_{$currencySource}"),
			CriteriaRestriction::equals('cobro_moneda_base.id_moneda', 'moneda_base.id_moneda')
		));

		$Criteria->add_grouping('cobro.id_cobro');
	}

	/**
	 * Establece de d�nde se obtiene la moneda y tipo de cambio
	 * @return [type] [description]
	 */
	function getCurrencySource() {
		return 'documento';
	}

	/**
	 * Sobrecarga la query de trabajos para agregar los datos de moneda
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseWorkQuery(Criteria $Criteria) {
		parent::getBaseWorkQuery($Criteria);
		$this->addCurrencyToQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de tr�mites para agregar los datos de moneda
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseErrandQuery($Criteria) {
		parent::getBaseErrandQuery($Criteria);
		$this->addCurrencyToQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de cobros para agregar los datos de moneda
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseChargeQuery($Criteria) {
		parent::getBaseChargeQuery($Criteria);
		$this->addCurrencyToQuery($Criteria);
	}

}
