<?php

namespace Database;

class AgregarColumnaTramitesNoCobrablesAContrato extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `contrato` ADD COLUMN `opc_mostrar_tramites_no_cobrables` TINYINT(1) NULL');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `contrato` DROP COLUMN `opc_mostrar_tramites_no_cobrables`');
	}
}
