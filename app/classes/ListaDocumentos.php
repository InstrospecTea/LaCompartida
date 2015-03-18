<?php

class ListaDocumentos extends Lista {

	function ListaDocumentos($sesion, $params, $query) {
		$this->Lista($sesion, 'Documento', $params, $query);
	}

}
