<?php

class SandboxController extends AbstractController {

	public function index() {
		$this->layoutTitle = 'Sandbox interface';
		$this->loadBusiness('Sandboxing');
		$results = $this->SandboxingBusiness->getSandboxResults();
		$listator = $this->SandboxingBusiness->getSandboxListator($results);
		$this->set('listator', $listator);
		$this->set('cant_results', count($results));
		$this->info('Esto es un sandbox... de gato!', 'I');
		$this->error('Nop, no es un sandbox de perro...', 'E');
	}
	
}