<?php

namespace Database;

class AgregarCampoTablaPrmMoneda extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `prm_moneda` ADD `simbolo_factura` VARCHAR(7)  NULL  DEFAULT NULL  AFTER `codigo`');
		$this->addQueryUp('UPDATE `prm_moneda` SET `simbolo_factura` = `simbolo`');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `prm_moneda` DROP `simbolo_factura`');
	}
}
