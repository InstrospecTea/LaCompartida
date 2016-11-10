<?php

namespace Database;

class SeparadorMilesYDecimalesMoneda extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$differents = ['USD', 'MXN', 'GBP', 'CHF', 'PEN', 'JPY', 'LPS', 'GTQ', 'NIO'];

		$this->addQueryUp("ALTER TABLE `prm_moneda` ADD COLUMN `separador_miles` CHAR(1) DEFAULT '.'");
		$this->addQueryUp("ALTER TABLE `prm_moneda` ADD COLUMN `separador_decimales` CHAR(1) DEFAULT ','");

		$currencies = $this->getResultsQuery("SELECT * FROM `prm_moneda`");

		foreach ($currencies as $value) {
			if (in_array($value['codigo'], $differents)) {
				$this->addQueryUp("UPDATE `prm_moneda` SET `separador_miles` = ',', `separador_decimales` = '.' WHERE `id_moneda` = {$value['id_moneda']};");
			}
		}
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `prm_moneda` DROP COLUMN `separador_miles`");
		$this->addQueryDown("ALTER TABLE `prm_moneda` DROP COLUMN `separador_decimales`");
	}
}
