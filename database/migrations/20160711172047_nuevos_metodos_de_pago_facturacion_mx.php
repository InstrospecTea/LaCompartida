<?php

namespace Database;

class NuevosMetodosDePagoFacturacionMx extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$factura_mx = $this->getResultsQuery("SELECT COUNT(*) FROM `prm_plugin` WHERE `archivo_nombre` = 'facturacion_electronica_mx.php' AND `activo` = 1");
		if (!empty($factura_mx)) {
			// Se hace el reemplazo de datos antiguos
			$this->addQueryUp("UPDATE `prm_codigo` SET grupo = 'OLD_FACTURA_MX_METOD' WHERE grupo = 'PRM_FACTURA_MX_METOD';");

			// Se insertan nuevos métodos de pago
			$query = 'INSERT INTO `prm_codigo` (grupo, codigo, glosa) VALUES ';
			$query .= "('PRM_FACTURA_MX_METOD', '01', 'Efectivo'),";
			$query .= "('PRM_FACTURA_MX_METOD', '02', 'Cheque nominativo'),";
			$query .= "('PRM_FACTURA_MX_METOD', '03', 'Transferencia electrónica de Fondos'),";
			$query .= "('PRM_FACTURA_MX_METOD', '04', 'Tarjeta de Crédito'),";
			$query .= "('PRM_FACTURA_MX_METOD', '05', 'Monedero Electrónico'),";
			$query .= "('PRM_FACTURA_MX_METOD', '06', 'Dinero Electrónico'),";
			$query .= "('PRM_FACTURA_MX_METOD', '08', 'Vales de despensa'),";
			$query .= "('PRM_FACTURA_MX_METOD', '28', 'Tarjeta de Débito'),";
			$query .= "('PRM_FACTURA_MX_METOD', '29', 'Tarjeta de Servicio'),";
			$query .= "('PRM_FACTURA_MX_METOD', '99', 'Otros')";
			$this->addQueryUp($query);
		}
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		// Se elimina nuevo grupo
		$query = 'DELETE FROM `prm_codigo` WHERE ';
		$query .= "`grupo` = 'PRM_FACTURA_MX_METOD';";
		$this->addQueryDown($query);

		// Se restaura grupo antiguo
		$this->addQueryDown("UPDATE `prm_codigo` SET grupo = 'PRM_FACTURA_MX_METOD' WHERE grupo = 'OLD_FACTURA_MX_METOD';");
	}
}
