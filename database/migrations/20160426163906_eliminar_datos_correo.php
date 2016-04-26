<?php

namespace Database;

class EliminarDatosCorreo extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('DELETE FROM configuracion WHERE glosa_opcion="UsernameMail"');
		$this->addQueryUp('DELETE FROM configuracion WHERE glosa_opcion="PasswordMail"');
		$this->addQueryUp('DELETE FROM configuracion WHERE glosa_opcion="UsarMailAmazonSES"');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('INSERT INTO configuracion VALUES glosa_opcion="UsernameMail", valor_opcion="cron_correo@thetimebilling.com", valores_posibles="string", id_configuracion_categoria="6" orden=-1');
		$this->addQueryDown('INSERT INTO configuracion VALUES glosa_opcion="PasswordMail", valor_opcion="tt.asdwsx", valores_posibles="string", id_configuracion_categoria="6" orden=-1');
		$this->addQueryDown('INSERT INTO configuracion VALUES glosa_opcion="UsarMailAmazonSES", valor_opcion="1", valores_posibles="boolean", id_configuracion_categoria="6" orden=-1');
	}
}
