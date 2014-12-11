<?php


abstract class NestedReport extends AbstractReport{

	public function __construct() {
		$this->SimpleReport = new SimpleReport();
		parent::__construct();
	}

} 