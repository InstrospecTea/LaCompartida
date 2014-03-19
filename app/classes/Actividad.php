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
		'codigo_asunto'
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

		// asunto
		if ($this->fields['codigo_asunto'] == '') {
			$errores[] = __('Debe seleccionar un ') . __('asunto');
		}

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
	function AsignarCodigoActividad() {
		$query = "SELECT id_actividad FROM actividad ORDER BY id_actividad DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);

		if (empty($codigo)) {
			$codigo = 0;
		}

		$codigo_actividad = sprintf("%04d", $codigo + 1);
		return $codigo_actividad;
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
}

class ListaActividades extends Lista {
	function ListaActividades($sesion, $params, $query) {
		$this->Lista($sesion, 'Actividad', $params, $query);
	}
}
