<?php

namespace Database;

class EditarConfiguracionOrdenResumenProfesional extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp("UPDATE `configuracion` SET `valor_opcion` = 'orden_categoria ASC, tarifa DESC' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='prm_categoria_usuario.orden ASC, trabajo.tarifa_hh DESC'");
		$this->addQueryUp("UPDATE `configuracion` SET `valor_opcion` = 'id_categoria_usuario ASC, id_usuario ASC' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='usuario.id_categoria_usuario ASC, usuario.id_usuario ASC'");
		$this->addQueryUp("UPDATE `configuracion` SET `valor_opcion` = 'fecha ASC, descripcion ASC' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='trabajo.fecha ASC, trabajo.descripcion'");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQuerydDown("UPDATE `configuracion` SET `valor_opcion` = 'prm_categoria_usuario.orden ASC, trabajo.tarifa_hh DESC' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='orden_categoria ASC, tarifa DESC'");
		$this->addQueryDown("UPDATE `configuracion` SET `valor_opcion` = 'usuario.id_categoria_usuario ASC, usuario.id_usuario ASC' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='id_categoria_usuario ASC, id_usuario ASC'");
		$this->addQueryDown("UPDATE `configuracion` SET `valor_opcion` = 'trabajo.fecha ASC, trabajo.descripcion' WHERE glosa_opcion='OrdenResumenProfesional' AND valor_opcion='fecha ASC, descripcion ASC'");
	}
}
