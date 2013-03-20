<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if(!class_exists('ListaMonedas')) {
	class ListaMonedas extends Lista {

		function ListaMonedas($sesion, $params, $query) {
			$this->Lista($sesion, 'Moneda', $params, $query);
		}

	}
}