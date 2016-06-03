<?php

class InvoiceService extends AbstractService implements IInvoiceService {
	public function getDaoLayer() {
		return 'InvoiceDAO';
	}

	public function getClass() {
		return 'Invoice';
	}
}
