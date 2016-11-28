<?php

namespace Database;

class AgregarColumnaExtranjeroACliente extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE cliente ADD COLUMN extranjero TINYINT(1) DEFAULT 0');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE cliente DROP COLUMN extranjero');
	}
}
