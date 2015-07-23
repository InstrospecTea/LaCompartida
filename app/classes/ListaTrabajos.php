<?php

/**
 * Class ListaTrabajos
 */
class ListaTrabajos extends Lista {
	function ListaTrabajos($sesion, $params, $query) {
		$this->Lista($sesion, 'Trabajo', $params, $query);
	}
}