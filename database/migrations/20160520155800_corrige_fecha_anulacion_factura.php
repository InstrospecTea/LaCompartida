<?php

namespace Database;

class Corrige_fecha_anulacion_factura extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('UPDATE factura
			SET fecha_anulacion = fecha_modificacion
			WHERE anulado = 1 AND fecha_anulacion IS NULL');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('');
	}
}
