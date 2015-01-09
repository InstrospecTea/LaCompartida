<?php

class SandboxController extends AbstractController {

	public $helpers = array('EntitiesListator', array('\TTB\Html', 'Html'), 'Form', 'Paginator');

	public function index() {
		$this->layoutTitle = 'Sandbox interface';
		$this->loadBusiness('Sandboxing');
		$page = empty($this->params['page']) ? null : $this->params['page'];
		$searchResult = $this->SandboxingBusiness->getSandboxResults(100, $page);
		$this->set('results', $searchResult->data);
		$this->set('Pagination', $searchResult->Pagination);
		$this->info('Esto es un sandbox... de gato!');
	}

}
