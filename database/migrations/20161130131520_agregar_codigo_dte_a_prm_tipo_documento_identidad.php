<?php

namespace Database;

class AgregarCodigoDteAPrmTipoDocumentoIdentidad extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('ALTER TABLE prm_tipo_documento_identidad ADD COLUMN codigo_dte VARCHAR(20) DEFAULT NULL');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE prm_tipo_documento_identidad DROP COLUMN codigo_dte');
	}
}
