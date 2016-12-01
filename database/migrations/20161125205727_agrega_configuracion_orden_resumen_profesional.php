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
		$this->addQueryUp("UPDATE configuracion AS a LEFT JOIN configuracion AS b ON 'SepararPorUsuario'=b.glosa_opcion SET a.valor_opcion = 'usuario.id_categoria_usuario ASC, usuario.id_usuario ASC' WHERE a.glosa_opcion='OrdenResumenProfesional' AND b.valor_opcion=1");
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down() {
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "OrdenResumenProfesional"');
	}
}
