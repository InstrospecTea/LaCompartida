<?php

namespace Database;

class EliminaColumnaExtraEnTramiteTarifa extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `tramite_tarifa` DROP COLUMN `guardado`;');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `tramite_tarifa` ADD `guardado` INT(1)  NOT NULL  DEFAULT '0'  COMMENT 'cuando se guarda el tarifa se pone 1, si no guarda el tarifa que ya esta creado se borra cuando salgas de la pantalla'  AFTER `tarifa_defecto`;");
	}
}
