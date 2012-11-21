<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class ListaGastos extends Lista {

		function ListaGastos($sesion, $params, $query) {
			$this->Lista($sesion, 'Gasto', $params, $query);
		}

	}
?>
