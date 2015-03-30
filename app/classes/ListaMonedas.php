<?php

class ListaMonedas extends Lista {

	function ListaMonedas($sesion, $params, $query) {
		$this->Lista($sesion, 'Moneda', $params, $query);
	}

}
