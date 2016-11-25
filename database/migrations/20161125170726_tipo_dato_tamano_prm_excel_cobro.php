<?php

namespace Database;

class TipoDatoTamanoPrmExcelCobro extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("ALTER TABLE `prm_excel_cobro` CHANGE `tamano` `tamano` DECIMAL(5,1)  NOT NULL  DEFAULT '0.0';");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("ALTER TABLE `prm_excel_cobro` CHANGE `tamano` `tamano` INT  NOT NULL  DEFAULT '0';");
	}
}
