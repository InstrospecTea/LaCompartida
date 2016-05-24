<?php

class ListaAsuntos extends Lista {
	public function ListaAsuntos($sesion, $params, $query) {
		$this->Lista($sesion, 'Asunto', $params, $query);
	}
}
