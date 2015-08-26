<?php

abstract class AbstractCurrencyDataCalculator extends AbstractDataCalculator {

  private $currencyId;

  public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields, $currencyId) {
    parent::__construct($Session, $filtersFields, $grouperFields, $selectFields);
    $this->currencyId = $currencyId;
  }

}
