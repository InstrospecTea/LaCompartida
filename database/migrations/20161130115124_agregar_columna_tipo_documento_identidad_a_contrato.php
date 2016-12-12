<?php

namespace Database;

class AgregarColumnaTipoDocumentoIdentidadAContrato extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp(
			'ALTER TABLE contrato
				DROP COLUMN `extranjero`,
				ADD COLUMN `id_tipo_documento_identidad` INT(11) DEFAULT NULL,
				ADD CONSTRAINT `contrato_fk_tipo_documento_identidad` FOREIGN KEY (`id_tipo_documento_identidad`) REFERENCES `prm_tipo_documento_identidad` (`id_tipo_documento_identidad`) ON UPDATE CASCADE'
		);
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown(
			'ALTER TABLE contrato
				ADD COLUMN extranjero TINYINT(1) DEFAULT 0,
				DROP FOREIGN KEY `contrato_fk_tipo_documento_identidad`,
				DROP COLUMN id_tipo_documento_identidad'
		);
	}
}
