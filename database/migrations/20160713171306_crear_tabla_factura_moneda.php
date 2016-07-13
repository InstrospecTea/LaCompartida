<?php

namespace Database;

class CrearTablaFacturaMoneda extends \Database\Migration implements \Database\ITemplateMigration {

	/**
	 * Run the migrations.
	 * @return void
	 */
	function up() {
		$this->addQueryUp('CREATE TABLE `factura_moneda` (
				`id_factura` int(11) NOT NULL,
				`id_moneda` int(11) NOT NULL,
				`tipo_cambio` double NOT NULL DEFAULT "0",
			PRIMARY KEY (`id_factura`,`id_moneda`),
			KEY `factura_moneda_fk_moneda` (`id_moneda`),
			CONSTRAINT `factura_moneda_fk_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE,
			CONSTRAINT `factura_moneda_fk_moneda` FOREIGN KEY (`id_moneda`) REFERENCES `prm_moneda` (`id_moneda`) ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT="Tipo de cambio de la factura";
		');

		$this->addQueryUp('INSERT INTO factura_moneda
			SELECT id_factura, documento_moneda.id_moneda, documento_moneda.tipo_cambio
			  FROM factura
			 INNER JOIN cobro ON factura.id_cobro = cobro.id_cobro
			 INNER JOIN documento ON cobro.id_cobro = documento.id_cobro AND documento.tipo_doc = "N"
			 INNER JOIN documento_moneda ON documento.id_documento = documento_moneda.id_documento;
		');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	function down() {
		$this->addQueryDown('DROP TABLE `factura_moneda`');
	}
}
