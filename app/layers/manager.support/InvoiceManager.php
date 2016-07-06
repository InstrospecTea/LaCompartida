<?php

class InvoiceManager extends AbstractManager implements IInvoiceManager {

	/**
	 * Obtiene el documento legal de una Factura
	 * @param 	string $invoice_id
	 * @return 	LegalDocument o null
	 */
	public function getLegalDocument($invoice_id) {
		if (empty($invoice_id) || !is_numeric($invoice_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Invoice');
		$this->loadService('LegalDocument');

		try {
			$Invoice = $this->InvoiceService->get("'{$invoice_id}'", 'id_documento_legal');
			$LegalDocument = $this->LegalDocumentService->get($Invoice->get('id_documento_legal'));
		} catch (EntityNotFound $e) {
			return null;
		}

		return $LegalDocument;
	}

}
