<?php

class InvoiceCurrencyService extends AbstractService implements IInvoiceCurrencyService {
	public function getDaoLayer() {
		return 'InvoiceCurrencyDAO';
	}

	public function getClass() {
		return 'InvoiceCurrency';
	}

	public function getByCompositeKey($invoiceId, $currencyId) {
		return $this->findFirst(
			CriteriaRestriction::and_clause(
				CriteriaRestriction::equals('id_factura', $invoiceId),
				CriteriaRestriction::equals('id_moneda', $currencyId)
			)
		);
	}

}
