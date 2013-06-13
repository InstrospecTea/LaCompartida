<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';

class AreaTrabajo extends Objeto {
	/**
	 * Find all work areas
	 * Return an array with next elements:
	 * 	code and name
	 */
	function findAll() {
		$work_areas = array();

		$sql = "SELECT `work_area`.`id_area_trabajo` AS `code`, `work_area`.`glosa` AS `name`
			FROM `prm_area_trabajo` AS `work_area`
			ORDER BY `work_area`.`glosa` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->execute();

		while ($work_area = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($work_areas,
				array(
					'code' => $work_area->code,
					'name' => !empty($work_area->name) ? $work_area->name : null
				)
			);
		}

		return $work_areas;
	}

}
