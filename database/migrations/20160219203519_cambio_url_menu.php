<?php

namespace Database;

class CambioUrlMenu extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("UPDATE `menu` SET `url` = '/app/Rate/ErrandsRate' WHERE `codigo` = 'TAR_TRA';");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("UPDATE `menu` SET `url` = '/app/interfaces/tarifas_tramites.php?id_tramite_tarifa_edicion=1' WHERE `codigo` = 'TAR_TRA';");
	}
}
