<?php

namespace Database;

class AsuntosCycDenton extends \Database\Migration implements \Database\ITemplateMigration {

	/**
	 * Run the migrations.
	 * @return void
	 */
	function up() {
		$this->addQueryUp('CREATE TABLE `asunto_extra` (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`id_asunto` INT NOT NULL ,
			`confidencial` BOOLEAN NOT NULL DEFAULT FALSE,
			`trabajo_conjunto` BOOLEAN NULL ,
			`id_region_maestra` INT NULL ,
			`codigo_asunto_maestro` VARCHAR(64) NULL ,
			`fecha_creacion` DATETIME NOT NULL ,
			`fecha_modificacion` DATETIME NOT NULL ,
			PRIMARY KEY (`id`), UNIQUE (`id_asunto`)) ENGINE = InnoDB');

		$this->addQueryUp('CREATE TABLE `prm_region_maestra` (
			`id` INT NOT NULL AUTO_INCREMENT ,
			`nombre` VARCHAR(64) NOT NULL ,
			`fecha_creacion` DATETIME NOT NULL ,
			PRIMARY KEY (`id`)) ENGINE = InnoDB');

		$this->addQueryUp('INSERT INTO `configuracion` SET
			`glosa_opcion` = "MostrarAsuntoConfidencial",
			`valor_opcion` = "0",
			`comentario` = "Permite asignarle a la causa la opción \'confidencial\', solo como un flag",
			`valores_posibles` = "boolean",
			`id_configuracion_categoria` = "10",
			`orden` = "-1"');

		$this->addQueryUp('INSERT INTO `configuracion` SET
			`glosa_opcion` = "MostrarAsuntoTrabajoConjunto",
			`valor_opcion` = "0",
			`comentario` = "Permite asignarle a la causa el selector \'Region Maestra\', solo como un flag",
			`valores_posibles` = "boolean",
			`id_configuracion_categoria` = "10",
			`orden` = "-1"');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down() {
		$this->addQueryDown('CREATE TABLE `asunto_extra`');
		$this->addQueryDown('DROP TABLE `prm_region_maestra`');
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "MostrarAsuntoConfidencial"');
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = "MostrarAsuntoTrabajoConjunto"');
	}

}
