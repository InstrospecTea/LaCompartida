<?php

/**
 * Corresponde a la clase base para los calculadores que
 * requieren ser devueltos de acuerdo a una proporcionalidad
 * y además requieren datos de facturacion.
 */
abstract class AbstractInvoiceProportionalDataCalculator
	extends AbstractProportionalDataCalculator {

	public function getInvoiceContribution() {
		return "((factura.subtotal *  (moneda_factura.tipo_cambio / cobro_moneda_documento.tipo_cambio ) ) / documento.subtotal_sin_descuento)";
	}

	function addCurrencyToInvoiceQuery($Criteria) {
		$Criteria->add_left_join_with(
				array('documento_moneda', 'moneda_factura'),
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('moneda_factura.id_documento', 'documento.id_documento'),
					CriteriaRestriction::equals('moneda_factura.id_moneda', 'factura.id_moneda')
				)
			);
	}

	/**
	 * Sobrecarga la query de trabajos para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseWorkQuery(Criteria $Criteria) {
		parent::getBaseWorkQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
		$this->addCurrencyToInvoiceQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de trámites para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseErrandQuery($Criteria) {
		parent::getBaseErrandQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
		$this->addCurrencyToInvoiceQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de cobros para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseChargeQuery($Criteria) {
		parent::getBaseChargeQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
		$this->addCurrencyToInvoiceQuery($Criteria);
	}
}
