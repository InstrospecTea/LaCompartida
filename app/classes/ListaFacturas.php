<?php

class ListaFacturas extends Lista {

	function ListaFacturas($sesion, $params, $query) {
		$this->Lista($sesion, 'Factura', $params, $query);
	}

}
