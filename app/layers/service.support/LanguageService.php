<?php

class LanguageService extends AbstractService implements ILanguageService {

	public function getDaoLayer() {
		return 'LanguageDAO';
	}

	public function getClass() {
		return 'Language';
	}

}
