<?php

abstract class AbstractProportionalDataCalculator extends AbstractCurrencyDataCalculator {

	const PROPRTIONALITY_CLIENT = 'cliente';
	const PROPRTIONALITY_STANDARD = 'estandar';

	private $proportionality;

	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields, $currencyId, $proportionality) {
		parent::__construct($Session, $filtersFields, $grouperFields, $selectFields, $currencyId);
		$this->proportionality = $proportionality;
	}

	public function getProportionality() {
		return $this->proportionality;
	}

	function getWorksFeeField() {
		$proporcionality = $this->getProportionality();
		if ($proporcionality == PROPRTIONALITY_ESTANDAR)  {
			return 'trabajo.tarifa_hh_estandar';
		} else {
			return 'trabajo.tarifa_hh';
		}
	}

	function getWorksProporcionalityAmountField() {
		$proporcionality = $this->getProportionality();
		if ($proporcionality == PROPRTIONALITY_ESTANDAR)  {
			return 'monto_thh_estandar';
		} else {
			return 'monto_thh';
		}
	}

	function getErrandsFeeField() {
		$proporcionality = $this->getProportionality();
		if ($proporcionality == PROPRTIONALITY_ESTANDAR)  {
			return 'tramite.tarifa_tramite_estandar';
		} else {
			return 'tramite.tarifa_tramite';
		}
	}

	function getErrandsProporcionalityAmountField() {
		$proporcionality = $this->getProportionality();
		if ($proporcionality == PROPRTIONALITY_ESTANDAR)  {
			return 'monto_tramites';
		} else {
			return 'monto_tramites';
		}
	}
}
