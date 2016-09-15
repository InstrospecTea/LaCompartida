<?php

namespace Database;

class PopupNewDesign extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE `usuario` ADD COLUMN `mostrar_popup` tinyint(1) NOT NULL DEFAULT 1');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `usuario` DROP COLUMN `mostrar_popup`');
	}
}
