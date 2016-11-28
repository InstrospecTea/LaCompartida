<?php

namespace Database;

class AgregarColumnaExtranjeroAContrato extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE contrato ADD COLUMN extranjero TINYINT(1) DEFAULT 0');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE contrato DROP COLUMN extranjero');
	}
}
