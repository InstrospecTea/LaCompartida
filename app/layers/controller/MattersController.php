<?php

class MattersController extends AbstractController {

	public function getLanguage($code) {
		$matter_code = Configure::read('CodigoSecundario') ? 'codigo_asunto_secundario' : 'codigo_asunto';

		$Criteria = new Criteria($this->Session);
		$result = $Criteria
			->add_select('codigo_idioma', 'code')
			->add_select('glosa_idioma', 'name')
			->add_from('asunto')
			->add_inner_join_with('prm_idioma', CriteriaRestriction::equals('asunto.id_idioma', 'prm_idioma.id_idioma'))
			->add_restriction(CriteriaRestriction::equals($matter_code, "'$code'"))
			->run();
		$language = isset($result[0]) ? $result[0] : null;
		$this->renderJSON($language);
	}

}
