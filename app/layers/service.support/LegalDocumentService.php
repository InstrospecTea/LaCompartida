<?php

class LegalDocumentService extends AbstractService implements ILegalDocumentService {

	public function getDaoLayer() {
		return 'LegalDocumentDAO';
	}

	public function getClass() {
		return 'LegalDocument';
	}

}
