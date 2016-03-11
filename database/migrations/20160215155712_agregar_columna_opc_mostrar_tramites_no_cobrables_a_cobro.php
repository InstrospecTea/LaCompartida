<?php

namespace Database;

class AgregarColumnaOpcMostrarTramitesNoCobrablesACobro extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `cobro` ADD COLUMN `opc_mostrar_tramites_no_cobrables` TINYINT(1) NULL');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `cobro` DROP COLUMN `opc_mostrar_tramites_no_cobrables`');
	}
}
