<?php

namespace Database;

class CondicionPago extends \Database\Migration implements \Database\ITemplateMigration {

	/**
   * Run the migrations.
   * @return void
   */
	function up() {
		$query_create_table = "CREATE TABLE `condicion_pago` (
 								`id_condicion_pago` TINYINT(2) NOT NULL,
 								`glosa` VARCHAR(40) NOT NULL,
 								`orden` TINYINT(2),
 								`defecto` TINYINT(1) NOT NULL,
 								PRIMARY KEY (`id_condicion_pago`))";

		$this->addQueryUp($query_create_table);

		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (1, 'CONTADO', 1, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (3, 'CC 15 d�as', 2, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (4, 'CC 30 d�as', 3, 1)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (5, 'CC 45 d�as', 4, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (6, 'CC 60 d�as', 5, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (7, 'CC 75 d�as', 6, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (8, 'CC 90 d�as', 7, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (9, 'CC 120 d�as', 8, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (12, 'LETRA 30 d�as', 9, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (13, 'LETRA 45 d�as', 10, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (14, 'LETRA 60 d�as', 11, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (15, 'LETRA 90 d�as', 12, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (18, 'CHEQUE 30 d�as', 13, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (19, 'CHEQUE 45 d�as', 14, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (20, 'CHEQUE 60 d�as', 15, 0)");
		$this->addQueryUp("INSERT IGNORE INTO `condicion_pago` (`id_condicion_pago`, `glosa`, `orden`, `defecto`) VALUES (21, 'CHEQUE A FECHA', 16, 0)");

		$query_fk = "ALTER TABLE `factura`
					ADD CONSTRAINT `factura_fk_condicion_pago`
					FOREIGN KEY (`condicion_pago`)
					REFERENCES `condicion_pago` (`id_condicion_pago`)";

		$this->addQueryUp($query_fk);
	}

	/**
   * Reverse the migrations.
   * @return void
   */
	function down() {
		$this->addQueryDown('ALTER TABLE `factura` DROP FOREIGN KEY `factura_fk_condicion_pago`');
		$this->addQueryDown('DROP TABLE `condicion_pago`');
	}
}
