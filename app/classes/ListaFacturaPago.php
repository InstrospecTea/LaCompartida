<?php
class ListaFacturaPago extends Lista {

	function ListaFacturaPago($sesion, $params, $query) {
		$this->Lista($sesion, 'FacturaPago', $params, $query);
	}

}
