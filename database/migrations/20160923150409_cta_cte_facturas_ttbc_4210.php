<?php

namespace Database;

class CtaCteFacturasTtbc_4210 extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `cuenta_banco` ADD COLUMN `imprimible` TINYINT(1) DEFAULT 1");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `cuenta_banco` DROP COLUMN `imprimible`');
	}
}
