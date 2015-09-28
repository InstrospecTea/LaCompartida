<?php

abstract class AbstractReportViewer implements IReportViewer {

	private $Session;
	private $filters;
	private $view;
	private $groupers;
	private $currencyId;
	private $proportionality;

	public function __construct(Sesion $Session, Array $options) {
		$filters, $view, $currencyId, $proportionality
		$this->Session = $Session;
		$this->filters = $options['filters']
		$this->view = $options['view']
		$this->currencyId = $options['currencyId']
		$this->proportionality = $options['proportionality']
		$this->groupers = $this->getGroupers()
	}

	private function getGroupers() {
		if (!empty($this->view)) {

		}
	}

	private function addGrouper() {

	}

}
