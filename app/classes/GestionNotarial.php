<?php

/**
* 					
*/
class GestionNotarial extends Objeto {
	
	private $criteria;
	private $trabajo;
	private $gasto;

	function __construct() {
		$this->criteria = new Criteria(null);
		$this->trabajo = new Trabajo(null);
		$this->gasto = new Gasto(null);
	}

	

}

?>