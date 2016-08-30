<?php

namespace Database;

class SolicitanteGasto extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `cta_corriente` ADD COLUMN `solicitante` VARCHAR(75) NULL DEFAULT NULL AFTER `detraccion`');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `cta_corriente` DROP COLUMN `solicitante`');
	}
}
