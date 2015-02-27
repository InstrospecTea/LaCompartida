<?php

class ListaTramites extends Lista {

	public function __construct($sesion, $params, $query) {
		$this->Lista($sesion, 'Tramite', $params, $query);
	}

}
