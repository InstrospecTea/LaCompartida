<?php

abstract class AbstractProportionalDataCalculator extends AbstractCurrencyDataCalculator {

	const PROPRTIONALITY_CLIENT = 'cliente';
	const PROPRTIONALITY_STANDARD = 'estandar';

	private $proportionality;

	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields, $currencyId, $proportionality) {
		parent::__construct($Session, $filtersFields, $grouperFields, $selectFields, $currencyId);
		$this->proportionality = $proportionality;
	}


}
