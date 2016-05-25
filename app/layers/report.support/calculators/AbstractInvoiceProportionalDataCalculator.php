<?php

/**
 * Corresponde a la clase base para los calculadores que
 * requieren ser devueltos de acuerdo a una proporcionalidad
 * y además requieren datos de facturacion.
 */
abstract class AbstractInvoiceProportionalDataCalculator
	extends AbstractProportionalDataCalculator {

	public function getInvoiceContribution() {
		return "(factura.subtotal / documento.subtotal_sin_descuento)";
	}
	/**
	 * Sobrecarga la query de trabajos para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseWorkQuery(Criteria $Criteria) {
		parent::getBaseWorkQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de trámites para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseErrandQuery($Criteria) {
		parent::getBaseErrandQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
	}

	/**
	 * Sobrecarga la query de cobros para agregar los datos de factura
	 * @param  Criteria $Criteria Criteria a modificar
	 * @return void
	 */
	function getBaseChargeQuery($Criteria) {
		parent::getBaseChargeQuery($Criteria);
		$this->addInvoiceToQuery($Criteria);
	}
}
