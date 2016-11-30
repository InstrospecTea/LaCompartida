<?php

namespace Database;

class  AgregarRegistroPrmExcel extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("INSERT INTO prm_excel_cobro SET nombre_interno = 'tarifa_hh_rentabilidad', grupo = 'Listado de trabajos', glosa_es = 'Tarifario', glosa_en = 'Fee'");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('');
	}
}
