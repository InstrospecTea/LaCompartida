<?php

namespace Database;

class OrdenTrabajos extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$order_works = '';
		$order_errand = '';
		$conf_value = $this->getResultsQuery("SELECT `valor_opcion` FROM `configuracion` WHERE glosa_opcion = 'RevHrsClienteFecha'");

		// Según la lógica de negocio existente si está activa la configuración RevHrsClienteFecha se debe ordenar por Fecha,
		// si no se ordenará por Cliente y Fecha.
		if ($conf_value[0]['valor_opcion'] == '1') {
			$order_works = 'cliente.glosa_cliente ASC, trabajo.fecha ASC';
			$order_errand = 'cliente.glosa_cliente ASC, tramite.fecha ASC';
		} else {
			$order_works = 'trabajo.fecha ASC';
			$order_errand = 'tramite.fecha ASC';
		}

		$query_works = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES (" .
						"'OrdenRevisarTrabajos', " .
						"'" . $order_works . "', " .
						"'Ordenamientos de los Trabajos en el módulo de Revisar Horas', " .
						"'string', " .
						"'6', " .
						"'-1')";

		$query_errand = "INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES (" .
						"'OrdenRevisarTramites', " .
						"'" . $order_errand . "', " .
						"'Ordenamientos de los Tramites en el módulo Revisar Tramites', " .
						"'string', " .
						"'6', " .
						"'-1')";

		$this->addQueryUp($query_works);
		$this->addQueryUp($query_errand);

		$query_delete = "DELETE FROM `configuracion` WHERE  glosa_opcion = 'RevHrsClienteFecha'";
		$this->addQueryUp($query_delete);
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown("DELETE FROM `configuracion` WHERE  glosa_opcion = 'OrdenRevisarTrabajos'");
		$this->addQueryDown("DELETE FROM `configuracion` WHERE  glosa_opcion = 'OrdenRevisarTramites'");
		$this->addQueryDown("INSERT IGNORE INTO `configuracion` (`glosa_opcion`, `valor_opcion`, `comentario`, `valores_posibles`, `id_configuracion_categoria`, `orden`) VALUES ('RevHrsClienteFecha', 0, 'Glosa Detraccion', 'boolean', '6', '-1')");
	}
}
