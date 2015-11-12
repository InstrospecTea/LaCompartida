<?php
require_once dirname(__FILE__).'/../conf.php';

class Actividad extends Objeto {
	/**
	 * Define los campos de la actividad permitidos para llenar
	 *
	 * @var array
	 */
	private $campos = array(
		'id_actividad',
		'codigo_actividad',
		'glosa_actividad',
		'codigo_asunto',
		'id_area_proyecto',
		'id_tipo_proyecto'
	);

	/*
	 * Configuración de SimpleReport
	 */
	public static $configuracion_reporte = array(
		array(
			'field' => 'id_actividad',
			'title' => 'N°',
			'visible' => false
		),
		array(
			'field' => 'codigo_actividad',
			'title' => 'Código',
		),
		array(
			'field' => 'glosa_actividad',
			'title' => 'Nombre Actividad'
		),
		array(
			'field' => 'codigo_cliente',
			'title' => 'Código Cliente',
			'visible' => false
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Nombre Cliente'
		),
		array(
			'field' => 'codigo_asunto',
			'title' => 'Código Asunto',
			'visible' => false
		),
		array(
			'field' => 'glosa_asunto',
			'title' => 'Nombre Asunto'
		)
	);

	function Actividad($sesion, $fields = "", $params = "") {
		$this->tabla = "actividad";
		$this->campo_id = "id_actividad";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->editable_fields = $this->campos;
	}

	/**
	 * Search Query Builder
	 */
	function SearchQuery() {
		$query = "SELECT SQL_CALC_FOUND_ROWS
			actividad.id_actividad,
			actividad.glosa_actividad,
			cliente.glosa_cliente,
			asunto.glosa_asunto,
			actividad.codigo_actividad,
			IF (actividad.activo = 1, 'SI','NO') AS activo
		FROM actividad
			LEFT JOIN asunto ON actividad.codigo_asunto = asunto.codigo_asunto
			LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente";

		$wheres = array();

		if (!empty($this->fields['codigo_actividad'])) {
			$wheres[] = "actividad.codigo_actividad = '{$this->fields['codigo_actividad']}'";
		}

		if (!empty($this->fields['glosa_actividad'])) {
			$wheres[] = "actividad.glosa_actividad LIKE '%{$this->fields['glosa_actividad']}%'";
		}

		if (!empty($this->extra_fields['codigo_cliente'])) {
			$wheres[] = "cliente.codigo_cliente = '{$this->extra_fields['codigo_cliente']}'";
		}

		if (!empty($this->fields['codigo_asunto'])) {
			$wheres[] = "actividad.codigo_asunto = '{$this->fields['codigo_asunto']}'";
		} else if (empty($this->fields['codigo_asunto']) && !empty($this->fields['glosa_asunto'])) {
			$wheres[] = "asunto.glosa_asunto LIKE '%{$this->fields['glosa_asunto']}%'";
		}

		if (count($wheres) > 0) {
			$query .= " WHERE " . implode(' AND ', $wheres);
		}

		return $query;
	}

	/**
	 * Implementar las mismas validaciones que se realizan en la vista, a nivel de código
	 *
	 * @return boolean Si todo anda ok, sino deja una variable en $_SESSION['errores']
	 * con las cosas que fallaron
	 */
	function Check() {
		$errores = array();

		// glosa
		if ($this->fields['glosa_actividad'] == '') {
			$errores[] = __('Debe ingresar un título');
		}

		// codigo y codigo repetido
		if ($this->fields['codigo_actividad'] == '') {
			$errores[] = __('Debe ingresar un código válido');
		} else {
			$OtraActividad = new Actividad($this->sesion);
			$OtraActividad->loadByCode($this->fields['codigo_actividad']);
			if ($OtraActividad->Loaded() && $this->fields['id_actividad'] != $OtraActividad->fields['id_actividad']) {
				$errores[] = __('Ya existe una actividad con el código elegido.');
			}
		}

		$this->error = $errores;

		return empty($this->error);
	}

	function CheckDelete() {
		// Buscar que no tenga trabajos o tramites asociados
		return true;
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel() {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('ACTIVIDADES');

		$query = $this->SearchQuery();
		$statement = $this->sesion->pdodbh->prepare($query);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Actividades');
	}

	//funcion que asigna el nuevo codigo automatico para un actividad
	function AsignarCodigoActividad($codigo_actividad = null) {
		// buscar el último id_actividad
		if (is_null($codigo_actividad)) {
			$Criteria = new Criteria($this->sesion);
			$Criteria->add_select('MAX(actividad.id_actividad)', 'id_actividad')->add_from('actividad');
			$actividad = $Criteria->run();

			$id_actividad = (int) $actividad[0]['id_actividad'];
		} else {
			$id_actividad = (int) $codigo_actividad;
		}

		$codigo_actividad = sprintf("%04d", $id_actividad + 1);

		// verificar si el nuevo código existe
		if ($this->existeCodigoActividad($codigo_actividad)) {
			$codigo_actividad = $this->AsignarCodigoActividad($codigo_actividad);
		}

		return $codigo_actividad;
	}

	public function existeCodigoActividad($codigo_actividad = null) {
		$existe_codigo_actividad = false;

		if (!is_null($codigo_actividad)) {
			$Criteria = new Criteria($this->sesion);
			$Criteria->add_select('COUNT(*)', 'total')
				->add_from('actividad')
				->add_restriction(CriteriaRestriction::equals('codigo_actividad', $codigo_actividad));

			$actividad = $Criteria->run();

			if ($actividad[0]['total'] > 0) {
				$existe_codigo_actividad = true;
			}
		}

		return $existe_codigo_actividad;
	}

	/**
	 * Find all activities
	 * Return an array with next elements:
	 * 	code, name and matter_code
	 */
	function findAll() {
		$activities = array();

		$sql = "SELECT `activity`.`codigo_asunto` AS `matter_code`, `activity`.`codigo_actividad` AS `code`,
			`activity`.`glosa_actividad` AS `name`
			FROM `actividad` AS `activity`
			ORDER BY `activity`.`glosa_actividad` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->execute();

		while ($activity = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($activities,
				array(
					'code' => $activity->code,
					'name' => !empty($activity->name) ? $activity->name : null,
					'matter_code' => !empty($activity->matter_code) ? $activity->matter_code : null
				)
			);
		}

		return $activities;
	}

	/**
	 * Load activity by code
	 * Returns a bool, true if exist record or false if doesn't exist
	 */
	function loadByCode($activity_code) {
		$sql = "SELECT `activity`.`id_actividad` AS `id`
			FROM `actividad` AS `activity`
			WHERE `activity`.`codigo_actividad`=:activity_code";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('activity_code', $activity_code);
		$Statement->execute();

		$activity = $Statement->fetch(PDO::FETCH_OBJ);

		if (is_object($activity)) {
			return $this->Load($activity->id);
		}

		return false;
	}

	function obtenerActividadesSegunAsunto($codigo_asunto, $activas = true) {
		$actividades = array();

		$Criteria = new Criteria($this->sesion);
		$asunto = $Criteria
			->add_select('id_area_proyecto')
			->add_select('id_tipo_asunto')
			->add_from('asunto')
			->add_restriction(CriteriaRestriction::equals('codigo_asunto', "'{$codigo_asunto}'"))
			->run();

		$and_area_tipo_proyecto = array();

		if (!empty($asunto)) {
			$or_area_tipo_proyecto = array();
			if (!empty($asunto[0]['id_area_proyecto'])) {
				$or_area_tipo_proyecto[] = CriteriaRestriction::equals('id_area_proyecto', "'{$asunto[0]['id_area_proyecto']}'");
			} else {
				$or_area_tipo_proyecto[] = CriteriaRestriction::is_null('id_area_proyecto');
			}
			if (!empty($asunto[0]['id_tipo_asunto'])) {
				$or_area_tipo_proyecto[] = CriteriaRestriction::equals('id_tipo_proyecto', "'{$asunto[0]['id_tipo_asunto']}'");
			} else {
				$or_area_tipo_proyecto[] = CriteriaRestriction::is_null('id_tipo_proyecto');
			}

			$and_area_tipo_proyecto[] = CriteriaRestriction::or_clause($or_area_tipo_proyecto);
			$and_area_tipo_proyecto[] = CriteriaRestriction::is_null('codigo_asunto');
		}

		$or_clauses = array(
			CriteriaRestriction::equals('codigo_asunto', "'{$codigo_asunto}'"),
			CriteriaRestriction::and_clause(
				array(
					CriteriaRestriction::is_null('id_area_proyecto'),
					CriteriaRestriction::is_null('codigo_asunto'),
					CriteriaRestriction::is_null('id_tipo_proyecto')
				)
			)
		);

		if (!empty($and_area_tipo_proyecto)) {
			$or_clauses[] = CriteriaRestriction::and_clause($and_area_tipo_proyecto);
		}

		$and_clauses = array(CriteriaRestriction::or_clause($or_clauses));

		if ($activas == true) {
			$and_clauses[] = CriteriaRestriction::equals('activo', 1);
		}

		$Criteria = new Criteria($this->sesion);
		$actividades = $Criteria
			->add_select('codigo_actividad')
			->add_select('glosa_actividad')
			->add_from('actividad')
			->add_restriction(CriteriaRestriction::and_clause($and_clauses))
			->run();

		return $actividades;
	}
}

class ListaActividades extends Lista {
	function ListaActividades($sesion, $params, $query) {
		$this->Lista($sesion, 'Actividad', $params, $query);
	}
}
