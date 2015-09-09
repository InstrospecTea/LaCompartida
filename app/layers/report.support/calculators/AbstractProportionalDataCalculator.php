<?php

/**
 * Corresponde a la clase base para los calculadores que
 * requieren ser devueltos de acuerdo a una proporcionalidad
 */
abstract class AbstractProportionalDataCalculator extends AbstractCurrencyDataCalculator {

	const PROPORTIONALITY_CLIENT = 'cliente';
	const PROPORTIONALITY_STANDARD = 'estandar';

	private $proportionality;

	/**
	 * Constructor
	 * @param Sesion $Session         La sesión para el acceso a datos
	 * @param [type] $filtersFields   Los campos/keys por los que se debe filtrar y sus valores
	 * @param [type] $grouperFields   Los campos/keys por los que se debe agrupar
	 * @param [type] $currencyId      La moneda en la que se devolverán los valores
	 * @param [type] $proportionality La proporcionalidad en la que se deben distribuir los valores
	 */
	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $options, $currencyId, $proportionality) {
		parent::__construct($Session, $filtersFields, $grouperFields, $options, $currencyId);
		$this->proportionality = $proportionality;
	}

	/**
	 * Obtiene la proporcionalidad elegida
	 * @return [type] [description]
	 */
	public function getProportionality() {
		return $this->proportionality;
	}

	/**
	 * Obtiene el factor de proporcionalidad adecuado
	 * @return String Query con factor de proporcionalidad
	 */
	function getWorksProportionalFactor() {
		$rate = $this->getWorksFeeField();
		$amount = $this->getWorksProportionalityAmountField();
		return "({$rate} / {$amount})";
	}

	/**
	 * Devuelve el par tabla.campo de la tarifa del trabajo
	 * en base a la proporcionalidad elegida
	 * @return string campo de donde se obtendrá la tarifa
	 */
	function getWorksFeeField() {
		$proportionality = $this->getProportionality();

		$duration = 'TIME_TO_SEC(trabajo.duracion_cobrada)';
		$duration_hours = "{$duration}/3600";

		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return "trabajo.tarifa_hh_estandar * {$duration_hours}";
		} else {
			return "IF(cobro.forma_cobro = 'FLAT FEE', trabajo.tarifa_hh_estandar, trabajo.tarifa_hh) * {$duration_hours}";
		}
	}

	/**
	 * Devuelve el campo del cobro desde donde se obtiene el monto total
	 * producido de trabajos en base a la proporcionalidad elegida
	 * @return string campo de donde se obtendrá el monto
	 */
	function getWorksProportionalityAmountField() {
		$proportionality = $this->getProportionality();
		if ($proportionality == PROPORTIONALITY_STANDARD)  {
			return 'IF(cobro.monto_thh_estandar > 0, cobro.monto_thh_estandar,
					IF(cobro.monto_trabajos > 0, cobro.monto_trabajos, 1))';
		} else {
			return "IF(cobro.forma_cobro = 'FLAT FEE',
					IF(cobro.monto_thh_estandar > 0, cobro.monto_thh_estandar,
						IF(cobro.monto_trabajos > 0, cobro.monto_trabajos, 1)
					),
					IF(cobro.monto_thh > 0,
						cobro.monto_thh,
						IF(cobro.monto_trabajos > 0, cobro.monto_trabajos, 1)
					)
				)";
		}
	}

	/**
	 * Obtiene el factor de proporcionalidad adecuado
	 * @return String Query con factor de proporcionalidad
	 */
	function getErrandsProportionalFactor() {
		$rate = $this->getErrandsFeeField();
		$amount = $this->getErrandsProportionalityAmountField();
		return "({$rate} / {$amount})";
	}

	/**
	 * Devuelve el par tabla.campo de la tarifa del trámite
	 * en base a la proporcionalidad elegida
	 * @return string campo de donde se obtendrá la tarifa
	 */
	function getErrandsFeeField() {
		return 'tramite.tarifa_tramite';
	}

	/**
	 * Devuelve el campo del cobro desde donde se obtiene el monto total
	 * producido de trámites en base a la proporcionalidad elegida.
	 * Actualmente Trámites no posee un campo total para tarifa estándar
	 * @return string campo de donde se obtendrá el monto
	 */
	function getErrandsProportionalityAmountField() {
		return 'cobro.monto_tramites';
	}
}
