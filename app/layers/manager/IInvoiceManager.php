<?php

interface IInvoiceManager extends BaseManager {
	/**
	 * Obtiene el documento legal de una Factura
	 * @param 	string $invoice_id
	 * @return 	LegalDocument
	 */
	public function getLegalDocument($invoice_id);
}
