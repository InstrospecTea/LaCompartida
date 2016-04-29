<?php

/**
 * Corresponde a la clase base para los calculadores que
 * requieren ser devueltos de acuerdo a una proporcionalidad
 * y además requieren datos de facturacion.
 */
abstract class AbstractInvoiceProportionalDataCalculator
	extends AbstractProportionalDataCalculator {

 	/**
 	 * Estable un factor para multiplicar los montos
 	 * ya que se multiplicarán por el número de facturas
 	 * @return [type] [description]
 	 */
	public function invoiceFactor() {
		return "(1 /
			IFNULL((
				SELECT IF(COUNT(f.id_factura) = 0, 1, COUNT(f.id_factura))
				  FROM factura f
				 WHERE f.id_cobro = cobro.id_cobro
				   AND IFNULL(f.anulado, 0) = 0), 1))";
	}


	public function getInvoiceContribution() {
		return "(factura.subtotal / documento.subtotal_sin_descuento)";
	}

	public function addAmountFieldToCriteria(Criteria $Criteria, $fieldValue, $fieldName) {
		$factor = $this->invoiceFactor();
		$Criteria->add_select("($factor)*({$fieldValue})", $fieldName);
	}

	/**
	 * Agrega Invoice a Criteria
	 * @param Criteria $Criteria [description]
	 */
	function addInvoiceToQuery(Criteria $Criteria) {
		$Criteria->add_left_join_with('factura',
			CriteriaRestriction::and_clause(
  			array(
					CriteriaRestriction::equals('factura.id_cobro', 'cobro.id_cobro'),
					CriteriaRestriction::equals('IFNULL(factura.anulado, 0)', '0')
				)
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
