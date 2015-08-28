<?php

abstract class AbstractProportionalDataCalculator extends AbstractCurrencyDataCalculator {

	const PROPORTIONALITY_CLIENT = 'cliente';
	const PROPORTIONALITY_STANDARD = 'estandar';

	private $proportionality;

	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields, $currencyId, $proportionality) {
		parent::__construct($Session, $filtersFields, $grouperFields, $selectFields, $currencyId);
		$this->proportionality = $proportionality;
	}

	public function getProportionality() {
		return $this->proportionality;
	}

	function getWorksFeeField() {
		$proportionality = $this->getProportionality();
		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return 'trabajo.tarifa_hh_estandar';
		} else {
			return 'trabajo.tarifa_hh';
		}
	}

	function getWorksProportionalityAmountField() {
		$proportionality = $this->getProportionality();
		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return 'monto_thh_estandar';
		} else {
			return 'monto_thh';
		}
	}

	function getErrandsFeeField() {
		$proportionality = $this->getProportionality();
		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return 'tramite.tarifa_tramite_estandar';
		} else {
			return 'tramite.tarifa_tramite';
		}
	}

	function getErrandsProportionalityAmountField() {
		$proportionality = $this->getProportionality();
		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return 'monto_tramites';
		} else {
			return 'monto_tramites';
		}
	}
}
