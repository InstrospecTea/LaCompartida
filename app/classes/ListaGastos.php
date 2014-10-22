<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__) . '/Gasto.php';

class ListaGastos extends Lista {
	function ListaGastos($sesion, $params, $query) {
		$this->Lista($sesion, 'Gasto', $params, $query);
	}
}
