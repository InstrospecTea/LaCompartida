<?php

namespace Database;

class FechaInicioContrato extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
			$this->addQueryUp('ALTER TABLE `cliente` ADD `fecha_inicio_contrato` DATE NOT NULL AFTER `termino_pago_comision`');
			$this->addQueryUp('UPDATE `cliente` SET `fecha_inicio_contrato` = date(`fecha_creacion`)');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `cliente` DROP `fecha_inicio_contrato`');
	}
}
