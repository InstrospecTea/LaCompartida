<?php

class ListaTareas extends Lista {

	function ListaTareas($sesion, $params, $query) {
		$this->Lista($sesion, 'Tarea', $params, $query);
	}

}