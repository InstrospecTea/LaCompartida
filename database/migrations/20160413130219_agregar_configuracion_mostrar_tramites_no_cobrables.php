<?php

namespace Database;

class AgregarConfiguracionMostrarTramitesNoCobrables extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('INSERT INTO `configuracion`
		 SET `glosa_opcion` = "OpcMostrarTramitesNoCobrables",
		 `valores_posibles` = "boolean",
		 `valor_opcion` = 0,
		 `id_configuracion_categoria` = 8,
		 `orden` = -1');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "OpcMostrarTramitesNoCobrables"');
	}
}
