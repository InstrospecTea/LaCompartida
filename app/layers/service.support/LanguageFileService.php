<?php

class LanguageFileService extends AbstractService implements ILanguageFileService {

	public function getDaoLayer() {
		return 'LanguageFileDAO';
	}

	public function getClass() {
		return 'LanguageFile';
	}

}
