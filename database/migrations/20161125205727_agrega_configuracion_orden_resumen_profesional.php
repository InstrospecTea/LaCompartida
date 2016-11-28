<?php

namespace Database;

class AgregaConfiguracionOrdenResumenProfesional extends \Database\Migration implements \Database\ITemplateMigration {

		/**
	 * Run the migrations.
	 * @return void
	 */
	function up() {
		$this->addQueryUp('INSERT INTO `configuracion`
		 SET `glosa_opcion` = "OrdenResumenProfesional",
		 `valores_posibles` = "string",
		 `valor_opcion` = "trabajo.fecha ASC, trabajo.descripcion ASC",
		 `id_configuracion_categoria` = 4,
		 `orden` = -1');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down() {
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "OrdenResumenProfesional"');
	}
}
