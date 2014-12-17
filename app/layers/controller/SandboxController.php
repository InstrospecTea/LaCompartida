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

	public function reporte() {
		$this->layoutTitle = 'Reporte de Productividad';
		
		$report = new TimekeeperProductivityReport();
		
		$data = array(
			array("dato"=> "437", "dato2" => "359700"),
			array("dato"=> "438", "dato2" => "359710")
		);

		$report->setData($data);
		$report->setOutputType('Simple');
		$report->setConfiguration('sesion', $this->Session);
		$this->set('report', $report);
	}

	public function report() {
		$this->layoutTitle = 'Sandbox interface';
		$data = array(1,2,3,4,5);
		$report = new AgrupatedWorkReport();
		$report->setData($data);
		$report->setOutputType('RTF');
		$report->render();
	}

	public function changalanga() {
		pr(shell_exec('cat /etc/issue'));
	}

}

