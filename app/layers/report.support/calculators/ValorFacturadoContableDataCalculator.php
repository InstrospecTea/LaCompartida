<?php

/**
 * TODO: El valor facturado contable corresponde ValorCobradoDataCalculator multiplicado por el
 * aporte del monto total facturado en su Liquidaci�n.
 *
 * M�s info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Factuado-Contable
 */
class ValorFacturadoContableDataCalculator extends AbstractInvoiceProportionalDataCalculator {
	private $fieldName = 'valor_facturado_contable';
	/**
	 * Obtiene la query de trabajos correspondiente al valor facturado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {

		 $Criteria
			->add_select("0", $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de tr�mites correspondiente al valor facturado
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$Criteria
			->add_select("0", $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni tr�mites
	 * @param  Criteria $Criteria Query a la que se agregar� el c�lculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$Criteria
			->add_select("0", $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('EMITIDO', 'FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

}
