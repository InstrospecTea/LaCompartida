<?php

class DocumentPaymentsTransactionsService extends AbstractService implements IDocumentPaymentsTransactionsService {

	public function getDaoLayer() {
		return 'DocumentPaymentsTransactionsDAO';
	}

	public function getClass() {
		return 'DocumentPaymentsTransactions';
	}

}
