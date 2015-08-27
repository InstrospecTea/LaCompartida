<?php

abstract class AbstractCurrencyDataCalculator extends AbstractDataCalculator {

  private $currencyId;

  public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields, $currencyId) {
    parent::__construct($Session, $filtersFields, $grouperFields, $selectFields);
    $this->currencyId = $currencyId;
  }

  function addCurrencyToQuery(Criteria $Criteria) {
    $Criteria
      ->add_select('cobro_moneda.id_moneda')
      ->add_select('cobro_moneda.tipo_cambio')
      ->add_select('cobro_moneda_base.id_moneda')
      ->add_select('cobro_moneda_base.tipo_cambio')
      ->add_select('cobro_moneda_cobro.id_moneda')
      ->add_select('cobro_moneda_cobro.tipo_cambio');

    $Criteria->add_left_join_with(array('prm_moneda', 'moneda_base'), CriteriaRestriction::equals('moneda_base.moneda_base', 1));

    $currencySource = $this->getCurrencySource();

    if ($currencySource == 'documento') {
      $Criteria->add_left_join_with('documento', CriteriaRestriction::and_clause(
        CriteriaRestriction::equals('documento.id_cobro', 'cobro.id_cobro'),
        CriteriaRestriction::equals('documento.tipo_doc', "'N'")
      ));

      // moneda del documento
      $Criteria->add_left_join_with(array('documento_moneda', 'cobro_moneda_documento'), CriteriaRestriction::and_clause(
        CriteriaRestriction::equals("cobro_moneda_documento.id_{$currencySource}", "{$currencySource}.id_{$currencySource}"),
        CriteriaRestriction::equals('cobro_moneda_documento.id_moneda', 'documento.id_moneda')
      ));
    }

    // moneda de visualización
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

  function getCurrencySource() {
    return 'documento';
  }

  function getBaseWorkQuery(Criteria $Criteria) {
    parent::getBaseWorkQuery($Criteria);
    $this->addCurrencyToQuery($Criteria);
  }

  function getBaseErrandQuery($Criteria) {
    parent::getBaseErrandQuery($Criteria);
    $this->addCurrencyToQuery($Criteria);
  }

  function getBaseChargeQuery($Criteria) {
    parent::getBaseChargeQuery($Criteria);
    $this->addCurrencyToQuery($Criteria);
  }

}
