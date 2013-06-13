<?php
require_once dirname(__FILE__) . '/../conf.php';

class Translation extends Objeto {
	/**
	 * Find all activities
	 * Return an array with next elements:
	 * 	code, file name and order
	 */
	function findAllActive() {
		$translations = array();
		$active = 1;

		$sql = "SELECT `translation`.`id_lang` AS `code`, `translation`.`archivo_nombre` AS `file`,
			`translation`.`orden` AS `order`, `translation`.`activo` AS `active`
			FROM `prm_lang` AS `translation`
			WHERE `translation`.`activo`=:active
			ORDER BY `translation`.`orden` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('active', $active);
		$Statement->execute();

		while ($translation = $Statement->fetch(PDO::FETCH_OBJ)) {

			array_push($translations,
				array(
					'code' => $translation->code,
					'file' => !empty($translation->file) ? $translation->file : null,
					'order' => !empty($translation->order) ? $translation->order : null,
				)
			);

		}

		return $translations;
	}
}
