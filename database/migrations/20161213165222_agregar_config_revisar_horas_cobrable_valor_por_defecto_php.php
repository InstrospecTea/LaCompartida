<?php

namespace Database;

class AgregarConfigRevisarHorasCobrableValorPorDefectoPhp extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('INSERT INTO `configuracion`
		 SET `glosa_opcion` = "RevisarHorasCobrableValorPorDefecto",
		 `comentario` = "SI, NO o vacío",
		 `valores_posibles` = "select;Todos;SI;NO",
		 `valor_opcion` = "Todos",
		 `id_configuracion_categoria` = 6,
		 `orden` = -1');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down() {
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "RevisarHorasCobrableValorPorDefecto"');
	}
}
