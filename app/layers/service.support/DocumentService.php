<?php

class DocumentService extends AbstractService implements IDocumentService {

	public function getDaoLayer() {
		return 'DocumentDAO';
	}

	public function getClass() {
		return 'Document';
	}

}
