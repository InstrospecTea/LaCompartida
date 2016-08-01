<?php

namespace Database;

class EliminarRetribuciones extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("DELETE FROM `menu_permiso` WHERE `codigo_permiso` = 'RET'");
		$this->addQueryUp("DELETE FROM `usuario_permiso` WHERE `codigo_permiso` = 'RET'");
		$this->addQueryUp("DELETE FROM `prm_permisos` WHERE `codigo_permiso` = 'RET'");

		$this->addQueryUp("DELETE FROM `configuracion` WHERE `glosa_opcion` = 'UsarModuloRetribuciones'");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("INSERT INTO `prm_permisos` SET `codigo_permiso` = 'RET', `glosa` = 'Retribuciones'");
		$this->addQueryDown("INSERT INTO `configuracion` SET `glosa_opcion` = 'UsarModuloRetribuciones', `valor_opcion` = 0, `comentario` = 'Activa el módulo de Retribuciones', `valores_posibles` = 'boolean', `id_configuracion_categoria` = 10, `orden` = -1");
	}
}
