<?php

namespace Database;

class EliminarConfiguracionOrdenarPorCategoriaNombreUsuario extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('DELETE FROM `configuracion` WHERE `glosa_opcion` = "OrdenarPorCategoriaNombreUsuario"');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('INSERT INTO `configuracion` (`id`, `glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES (271,"OrdenarPorCategoriaNombreUsuario","0",NULL,"boolean",3,333);');
	}
}
