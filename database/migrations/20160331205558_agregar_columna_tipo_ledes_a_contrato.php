<?php

namespace Database;

class AgregarColumnaTipoLedesAContrato extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `contrato` ADD COLUMN `tipo_ledes` VARCHAR(90) DEFAULT "LEDES1998B"');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `contrato` DROP COLUMN `tipo_ledes`');
	}
}
