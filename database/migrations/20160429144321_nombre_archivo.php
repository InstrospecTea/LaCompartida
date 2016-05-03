<?php

namespace Database;

class NombreArchivo extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `archivo` "
			. "CHANGE `archivo_nombre` `archivo_nombre` VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',"
			. "CHANGE `data_tipo` `data_tipo` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',"
			. "CHANGE `archivo_data` `archivo_data` MEDIUMBLOB NULL DEFAULT NULL;");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `archivo` "
			. "CHANGE `archivo_nombre` `archivo_nombre` VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',"
			. "CHANGE `data_tipo` `data_tipo` VARCHAR(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',"
			. "CHANGE `archivo_data` `archivo_data` MEDIUMBLOB NOT NULL;");
	}
}
