<?php

require_once dirname(__FILE__) . '/../conf.php';

class ListaAsuntos extends Lista {
	public function ListaAsuntos($sesion, $params, $query) {
		$this->Lista($sesion, 'Asunto', $params, $query);
	}
}
