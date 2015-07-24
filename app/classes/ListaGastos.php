<?php

/**
 * Class ListaGastos
 */
class ListaGastos extends Lista {
	function ListaGastos($sesion, $params, $query) {
		$this->Lista($sesion, 'Gasto', $params, $query);
	}
}
