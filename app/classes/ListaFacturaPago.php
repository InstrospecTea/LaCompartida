<?php
class ListaFacturaPago extends Lista {

	function __construct($sesion, $params, $query) {
		$this->Lista($sesion, 'FacturaPago', $params, $query);
	}

}
