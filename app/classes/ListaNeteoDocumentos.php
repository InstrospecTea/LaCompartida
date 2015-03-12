<?php

class ListaNeteoDocumentos extends Lista {

	function ListaNeteoDocumentos($sesion, $params, $query) {
		$this->Lista($sesion, 'NeteoDocumento', $params, $query);
	}

}
