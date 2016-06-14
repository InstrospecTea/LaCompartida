<?php

namespace Database;

class InsertNuevoPrmPrc extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$this->addQueryUp('INSERT INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`)
 VALUES (\'EstadoCobroMostrarConceptoExcel\', \'{\"estados\":[\"EMITIDO\",\"FACTURADO\"]}\', \'Estados para mostrar concepto en excel cobro\', \'string\', \'4\', \'-1\')
');
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('DELETE FROM `configuracion` WHERE `glosa_opcion` = \'EstadoCobroMostrarConceptoExcel\'');
	}
}
