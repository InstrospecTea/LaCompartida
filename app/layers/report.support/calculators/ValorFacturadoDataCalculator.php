<?php
/**
 * El valor facturado corresponde ValorCobradoDataCalculator multiplicado por el
 * aporte del monto total facturado en su Liquidación.
 *
 * Más info: https://github.com/LemontechSA/ttb/wiki/Reporte-Calculador:-Valor-Factuado
 */
class ValorFacturadoDataCalculator extends AbstractInvoiceProportionalDataCalculator {
	private $fieldName = 'valor_facturado';

	/**
	 * Obtiene la query de trabajos correspondiente al valor facturado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportWorkQuery(Criteria $Criteria) {
		$subtotalBase = $this->getWorksProportionalDocumentSubtotal();
		$invoiceContrib = $this->getInvoiceContribution();

		$billed_amount = "SUM({$subtotalBase} * {$invoiceContrib})
		*
		(1 / cobro_moneda.tipo_cambio)";

		 $Criteria
			->add_select($billed_amount, $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('trabajo.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de trámites correspondiente al valor facturado
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportErrandQuery($Criteria) {
		$subtotalBase = $this->getErrandsProportionalDocumentSubtotal();
		$invoiceContrib = $this->getInvoiceContribution();

		$billed_amount =  "SUM({$invoiceContrib} * {$subtotalBase})
		*
		(1 / cobro_moneda.tipo_cambio)";

		 $Criteria
			->add_select($billed_amount, $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::equals('tramite.cobrable', 1))
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

	/**
	 * Obtiene la query de cobros sin trabajos ni trámites
	 * @param  Criteria $Criteria Query a la que se agregará el cálculo
	 * @return void
	 */
	function getReportChargeQuery($Criteria) {
		$invoiceContrib = $this->getInvoiceContribution();

		$billed_amount = "
			SUM({$invoiceContrib}
				* (cobro.monto_subtotal - cobro.descuento)
				* (1 / IFNULL(asuntos_cobro.total_asuntos, 1))
				* (cobro_moneda_cobro.tipo_cambio / cobro_moneda.tipo_cambio)
			)
		";

		$Criteria
			->add_select($billed_amount, $this->fieldName);

		$Criteria
			->add_restriction(CriteriaRestriction::in('cobro.estado', array('FACTURADO', 'ENVIADO AL CLIENTE', 'PAGO PARCIAL', 'PAGADO')));
	}

}
