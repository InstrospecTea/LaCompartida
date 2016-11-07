<?php

namespace Database;

class TrackingCredits extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp(
			"CREATE TABLE `prm_categoria_generador` (
			  `id_categoria_generador` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `nombre` varchar(20) NOT NULL DEFAULT '',
			  PRIMARY KEY (`id_categoria_generador`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

		$this->addQueryUp('ALTER TABLE `contrato_generador` ADD `id_categoria_generador` INT  UNSIGNED  NOT NULL  AFTER `porcentaje_genera`;');
		$this->addQueryUp('ALTER TABLE `contrato_generador` ADD CONSTRAINT `contrato_generador_fk_id_categoria_generador` FOREIGN KEY (`id_categoria_generador`) REFERENCES `prm_categoria_generador` (`id_categoria_generador`);');

		$this->addQueryUp("INSERT INTO `prm_mantencion_tablas` (`nombre_tabla`, `glosa_tabla`, `info_tabla`) VALUES ('prm_categoria_generador', 'Categoría Profesionales Generadores', NULL);");
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("DELETE FROM `prm_mantencion_tablas` WHERE nombre_tabla = 'prm_categoria_generador';");

		$this->addQueryDown('ALTER TABLE `contrato_generador` DROP FOREIGN KEY `contrato_generador_fk_id_categoria_generador`;');
		$this->addQueryDown('ALTER TABLE `contrato_generador` DROP `id_categoria_generador`;');


		$this->addQueryDown('DROP TABLE `prm_categoria_generador`;');
	}
}
