<?php
require_once dirname(__FILE__).'/../conf.php';

class PaginaCobro extends Pagina {
	public function __construct(&$sesion, $index = false) {
		parent::__construct($sesion, $index);
	}

	/***
	 * PrintPasos
	 *
	 * PrintPasos, imprime los pasos del ingreso
	 */
	function PrintPasos( $sesion, $paso, $cliente = null, $id_cobro = null, $incluye_gastos = 1, $incluye_honorarios = 1) {
		require Conf::ServerDir().'/templates/'.Conf::Templates().'/top_cobro.php';
	}
}
