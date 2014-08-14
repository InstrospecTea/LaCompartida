<?php

/**
*
* Clase que representa un reporte de historial de movimientos efectuados, por un usuario, en base a un foco. En ningun caso
* este reporte es de tipo financiero, sino que simplemente retorna un compendio de interacciones de los usuarios con el sistema.
*
*/
class ReporteHistorialMovimientos
{
	
	private $sesion;
	private $reportFocus;
	private $reportProtagonist;
	private $reportEntity;
	private $since;
	private $until;
	private $client;
	private $matter;
	private $charge;

	private $table_pivot = 'StoryTable';
	private $table_id = 'StoryKey';
	private $table_creation_date = 'CreationDate';
	private $main_table = 'MainTable';


	/**
	 *
	 * Constructor de la clase a partir de una instancia de la clase Sesion.
	 *
	 */
	function __construct($sesion) {
		ini_set("memory_limit", "256M");
		$this->sesion = $sesion;
	}

	/**
	 * Renderiza el reporte a partir de las configuraciones establecidas.
	 */
	public function generate() {
		$query = $this->constructCriteria();
		$report = $this->constructReport($query);
		return $report;
	}

	/**
	 * 
	 *	Asigna el usuario que es protagonista del análisis.
	 *
	 */
	public function setProtagonist($user_id) {
		$this->reportProtagonist = $user_id;
		return $this;
	}

	public function since($since) {
		$this->since = $since;
		return $this;
	}

	public function until($until) {
		$this->until = $until;
		return $this;
	}

	public function setCharge($charge) {
		$this->charge = $charge;
		return $this;
	}

	public function setClient($client) {
		$this->client = $client;
		return $this;
	}

	public function setMatter($matter) {
		$this->matter = $matter;
		return $this;
	}

	/**
	 * 
	 *	Asigna la entidad en particular que será analizada, corresponde a un id en específico de un {@link TipoHistorialEnum}.
	 *
	 */
	public function setEntity($entity_id) {
		$this->reportEntity = $entity_id;
		return $this;
	}

	public function filterByMovement($filter) {
		$this->reportMovement = $filter;
		return $this;
	}

	/**
	 * 
	 *	Asigna el tipo de objeto desde el cual se quiere obtener un reporte. Puede ser cualquiera definido en {@link TipoHistorialEnum}.
	 *
	 */
	public function setFocus($focus) {

		if (!TipoHistorialEnum::isValidName(strtolower($focus).$this->table_pivot)) {
			throw new Exception('El foco '.$focus.' no tiene definido un pivote para su tabla de historial.');
		}

		if (!TipoHistorialEnum::isValidName(strtolower($focus).$this->table_id)) {
			throw new Exception('El foco '.$focus.' no tiene definido un pivote para su identificador de historial.');
		}

		$table_name = TipoHistorialEnum::getValue(strtolower($focus).$this->table_pivot);
		$id = TipoHistorialEnum::getValue(strtolower($focus).$this->table_id);
		$creation_date = TipoHistorialEnum::getValue(strtolower($focus).$this->table_creation_date);
		$main_table = TipoHistorialEnum::getValue(strtolower($focus).$this->main_table);

		$this->constructReportFocus($table_name, $id, $creation_date, $main_table);

		return $this;

	}

	/**
	 * Construye el array paramétrico que contiene la información tipificada a partir de las preferencias establecidas para generar el reporte.
	 */
	private function constructReportFocus($table_name, $key, $creation_date, $main_table) {
		$this->reportFocus = array(
			'table_name' => $table_name,
			'key' => $key,
			'creation_date' => $creation_date,
			'main_table' => $main_table
		);
	}

	/**
	 * 
	 *	Construye la query correspondiente al reporte definido.
	 *
	 */
	private function constructCriteria() {

		if (empty($this->reportFocus)) {
			throw new Exception('Se debe definir el foco del reporte: gastos, cobros o historial');
		}

		//Extrae variables de report focus.
		extract($this->reportFocus);

		$reportCriteria = new Criteria($this->sesion);


		//Asigna los criterios de seleccion. El id se basa en la convención que es de  tipo id_table_name. Ej: id_trabajo_historial.
		$reportCriteria
					->add_select('id_'.$table_name)
					->add_select_not_null($table_name.'.'.$key,'story_key')
					->add_select($table_name.'.id_usuario')
					->add_select('CONCAT(usuario.nombre,\' \',usuario.apellido1)', 'usuario')
					->add_select($creation_date,'fecha_creacion')
					->add_left_join_with($main_table, CriteriaRestriction::equals($main_table.'.'.$key, $table_name.'.'.$key));

		$fecha_field = '.fecha';
		if ($main_table == 'tramite') {
			$fecha_field = '.fecha_accion';
		}

		if (!empty($this->since) && !empty($this->until)) {
			$reportCriteria->add_restriction(CriteriaRestriction::between('date('.$table_name.$fecha_field.')', $this->since, $this->until));
		}

		//Filtra por cliente
		if (!empty($this->client)) {
			if ($main_table == 'cobro') {
				$reportCriteria
					->add_restriction(CriteriaRestriction::equals($main_table.'.codigo_cliente','\''.$this->client.'\''));
			} else {
				$reportCriteria
					->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto', $main_table.'.codigo_asunto'))
					->add_restriction(CriteriaRestriction::equals('asunto.codigo_cliente', '\''.$this->client.'\''));
			}

			
		}

		//Filtra por asunto
		if (!empty($this->matter)) {
			if ($main_table == 'cobro') {
				$reportCriteria
						->add_restriction(CriteriaRestriction::equals('cobro_asunto.codigo_asunto', '\''.$this->matter.'\''));
			} else {
				$reportCriteria->add_restriction(CriteriaRestriction::equals($main_table.'.codigo_asunto', '\''.$this->matter.'\''));
			}
		}

		//Filtra por número de cobro
		if (!empty($this->charge) && $main_table != 'cobro') {
			$subCriteria = new Criteria($this->sesion);
			$subCriteria
					->add_select('cobro_asunto.codigo_asunto')
					->add_from('cobro_asunto')
					->add_restriction(CriteriaRestriction::equals('id_cobro', $this->charge));
			$reportCriteria
					->add_restriction(CriteriaRestriction::and_all(
							array(CriteriaRestriction::in_from_criteria($table_name.'.codigo_asunto', $subCriteria))
						));
		}

		//Ordena por id y por fecha.
		$reportCriteria
					->add_ordering($table_name.'.id_'.$table_name, 'DESC')
					->add_ordering('story_key', 'DESC');

		$reportCriteria->add_select('accion');
		//Establece desde donde se extraerán los datos
		$reportCriteria
				->add_from($table_name)
				->add_left_join_with('usuario', CriteriaRestriction::equals('usuario.id_usuario', $table_name.'.id_usuario'));
				


		//Establece los criterios de filtrado. Si apican.
		$reportCriteria = $this->setFilteringCriterions($reportCriteria);

		//Completa el reporte segun la entidad.
		$reportCriteria = $this->completeReportCriteria($reportCriteria, $main_table);
		return $reportCriteria;
	}

	/**
	 * 
	 *	Establece los criterios de filtrado, que permite refinar o especializar la información desplegada en el reporte.
	 *
	 */
	private function setFilteringCriterions(Criteria $reportCriteria) {

		//1.- Establece el protagonista del reporte, típicamente corresponde a un usuario del sistema.
		if ($this->reportProtagonist) {
			$reportCriteria
				->add_restriction(CriteriaRestriction::equals('usuario.id_usuario', $this->reportProtagonist));
		}

		//2.- Establece la entidad del reporte.
		if ($this->reportEntity) {
			$reportCriteria
				->add_restriction(CriteriaRestriction::equals($this->reportFocus['table_name'].'.'.$this->reportFocus['key'], $this->reportEntity));
		}

		//3.- Establece el movimiento que interesa filtrar.
		if (!empty($this->reportMovement)) {
			$reportCriteria->add_restriction(CriteriaRestriction::equals('accion', '\''.strtoupper($this->reportMovement).'\''));
		}

		return $reportCriteria;

	}

	/**
	 *
	 * Método que completa el report criteria según la entidad que se consulta.
	 *
	 */
	private function completeReportCriteria(Criteria $reportCriteria, $entity) {

		//Extrae variables de report focus.
		extract($this->reportFocus);

		$moneyCriteria = new Criteria($this->sesion);

		$moneyCriteria
			->add_select('codigo')
			->add_from('prm_moneda');


		switch ($entity) {
			case 'trabajo':
				
				$reportCriteria
					->add_select($table_name.'.fecha', 'fecha_accion')
					->add_select_not_null($table_name.'.'.'descripcion', 'descripcion')
					->add_select_not_null($table_name.'.'.'descripcion_modificado', 'descripcion_modificado')
					->add_select_not_null($table_name.'.'.'duracion_cobrada', 'duracion_cobrada')
					->add_select_not_null($table_name.'.'.'duracion_cobrada_modificado', 'duracion_cobrada_modificado')
					->add_select_not_null($table_name.'.'.'duracion', 'duracion')
					->add_select_not_null($table_name.'.'.'duracion_modificado', 'duracion_modificado')
					->add_select_not_null($table_name.'.'.'codigo_asunto', 'codigo_asunto')
					->add_select_not_null($table_name.'.'.'codigo_asunto_modificado', 'codigo_asunto_modificado')
					->add_select_not_null('IF('.$table_name.'.'.'cobrable, \'SI\', \'NO\')', 'cobrable')
					->add_select_not_null('IF('.$table_name.'.'.'cobrable_modificado, \'SI\', \'NO\')', 'cobrable_modificado');

				$reportCriteria
					->add_restriction(CriteriaRestriction::and_all(
							array(CriteriaRestriction::not_equal($table_name.'.'.$key, '0'))
						)
					);

				break;

			case 'cta_corriente':

				$reportCriteria
					->add_select($table_name.'.fecha', 'fecha_accion')
					->add_select_not_null($table_name.'.'.'fecha_movimiento', 'fecha_movimiento')
					->add_select_not_null($table_name.'.'.'fecha_movimiento_modificado', 'fecha_movimiento_modificado')
					->add_select_not_null($table_name.'.'.'codigo_cliente', 'codigo_cliente')
					->add_select_not_null($table_name.'.'.'codigo_cliente_modificado', 'codigo_cliente_modificado')
					->add_select_not_null($table_name.'.'.'codigo_asunto', 'codigo_asunto')
					->add_select_not_null($table_name.'.'.'codigo_asunto_modificado', 'codigo_asunto_modificado')
					->add_select_not_null($table_name.'.'.'egreso', 'egreso')
					->add_select_not_null($table_name.'.'.'egreso_modificado', 'egreso_modificado')
					->add_select_not_null($table_name.'.'.'ingreso', 'ingreso')
					->add_select_not_null($table_name.'.'.'ingreso_modificado', 'ingreso_modificado')
					->add_select_not_null($table_name.'.'.'descripcion', 'descripcion')
					->add_select_not_null($table_name.'.'.'descripcion_modificado', 'descripcion_modificado')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda'), 'id_moneda')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_modificado'), 'id_moneda_modificado');

				break;

			case 'cobro':
				
				$reportCriteria
					->add_select($table_name.'.fecha', 'fecha_accion')
					->add_select_not_null('asunto.'.'codigo_asunto', 'codigo_asunto')
					->add_select_not_null($table_name.'.'.'tipo_cambio_moneda', 'tipo_cambio_moneda')
					->add_select_not_null($table_name.'.'.'tipo_cambio_moneda_modificado','tipo_cambio_moneda_modificado')
					->add_select_not_null($table_name.'.'.'estado', 'estado')
					->add_select_not_null($table_name.'.'.'fecha_ini','fecha_ini')
					->add_select_not_null($table_name.'.'.'fecha_ini_modificado','fecha_ini_modificado')
					->add_select_not_null($table_name.'.'.'fecha_fin','fecha_fin')
					->add_select_not_null($table_name.'.'.'fecha_fin_modificado','fecha_fin_modificado')
					->add_select_not_null($table_name.'.'.'estado_modificado','estado_modificado')
					->add_select_not_null($table_name.'.'.'forma_cobro','forma_cobro')
					->add_select_not_null($table_name.'.'.'forma_cobro_modificado','forma_cobro_modificado')
					->add_select_not_null($table_name.'.'.'monto','monto')
					->add_select_not_null($table_name.'.'.'monto_modificado','monto_modificado')
					->add_select_not_null($table_name.'.'.'monto_gastos','monto_gastos')
					->add_select_not_null($table_name.'.'.'monto_gastos_modificado','monto_gastos_modificado')
					->add_left_join_with('cobro_asunto', CriteriaRestriction::equals('cobro_asunto.'.$key, $main_table.'.'.$key))
					->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto', 'cobro_asunto.codigo_asunto'))
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda'), 'id_moneda')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_modificado'), 'id_moneda_modificado')
					->add_grouping($table_name.'.id_'.$table_name);
				break;

			case 'tramite':

				$reportCriteria
					->add_select_not_null($table_name.'.'.'fecha_accion', 'fecha_accion')
					->add_select_not_null($table_name.'.'.'fecha_modificado', 'fecha_tramite')
					->add_select_not_null($table_name.'.'.'descripcion', 'descripcion')
					->add_select_not_null($table_name.'.'.'descripcion_modificado', 'descripcion_modificado')
					->add_select_not_null($table_name.'.'.'codigo_asunto', 'codigo_asunto')
					->add_select_not_null($table_name.'.'.'codigo_asunto_modificado', 'codigo_asunto_modificado')
					->add_select_not_null($table_name.'.'.'codigo_actividad', 'codigo_actividad')
					->add_select_not_null($table_name.'.'.'codigo_actividad_modificado', 'codigo_actividad_modificado')
					->add_select_not_null($table_name.'.'.'codigo_tarea', 'codigo_tarea')
					->add_select_not_null($table_name.'.'.'codigo_tarea_modificado', 'codigo_tarea_modificado')
					->add_select_not_null($table_name.'.'.'id_tramite_tipo', 'id_tramite_tipo')
					->add_select_not_null($table_name.'.'.'id_tramite_tipo_modificado', 'id_tramite_tipo_modificado')
					->add_select_not_null($table_name.'.'.'solicitante', 'solicitante')
					->add_select_not_null($table_name.'.'.'solicitante_modificado', 'solicitante_modificado')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_tramite'), 'id_moneda_tramite')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_tramite_modificado'), 'id_moneda_tramite_modificado')
					->add_select_not_null($table_name.'.'.'tarifa_tramite', 'tarifa_tramite')
					->add_select_not_null($table_name.'.'.'tarifa_tramite_modificado', 'tarifa_tramite_modificado')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_tramite_individual'), 'id_moneda_tramite_individual')
					->add_select_not_null_from_criteria($moneyCriteria, CriteriaRestriction::equals('id_moneda',$table_name.'.'.'id_moneda_tramite_individual_modificado'), 'id_moneda_tramite_individual_modificado')
					->add_select_not_null($table_name.'.'.'tarifa_tramite_individual', 'tarifa_tramite_individual')
					->add_select_not_null($table_name.'.'.'tarifa_tramite_individual_modificado', 'tarifa_tramite_individual_modificado')
					->add_select_not_null('IF('.$table_name.'.'.'cobrable, \'SI\', \'NO\')', 'cobrable')
					->add_select_not_null('IF('.$table_name.'.'.'cobrable_modificado, \'SI\', \'NO\')', 'cobrable_modificado')
					->add_select_not_null($table_name.'.'.'trabajo_si_no', 'trabajo_si_no')
					->add_select_not_null($table_name.'.'.'trabajo_si_no_modificado', 'trabajo_si_no_modificado')
					->add_select_not_null($table_name.'.'.'duracion', 'duracion')
					->add_select_not_null($table_name.'.'.'duracion_modificado', 'duracion_modificado')
					->add_select_not_null($table_name.'.'.'accion', 'accion');
				break;

			default:
				throw new Exception('There is no criteria completion handler defined for the selected entity '.$entity.' .');
				break;
		}
		return $reportCriteria;
	}

	/**
	 * 
	 */
	private function constructReport(Criteria $criteria) {
		extract($this->reportFocus);

		$agrupations = $criteria->run();

		//Entity Report
		$entityReport = new SimpleReport($this->sesion);
		$entityReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$entityReport= $this->generateReportConfiguration($entityReport, $main_table);
		$entityReport->LoadResults($agrupations);


		return $entityReport;
	}


	private function generateReportConfiguration(SimpleReport $report, $entity) {
		switch ($entity) {
			case 'trabajo':
				$report = $this->generateTrabajoReportConfiguration($report);
				break;
			case 'cta_corriente':
				$report = $this->generateGastoReportConfiguration($report);
				break;
			case 'cobro':
				$report = $this->generateCobroReportConfiguration($report);
				break;
			case 'tramite':
				$report = $this->generateTramiteReportConfiguration($report);
				break;
			default:
				throw new Exception('There is no correct report generation handler for '. $entity);
				break;
		}
		return $report;
	}

	private function generateGastoReportConfiguration($report) {
		$configuracion = array(
			array(
				'field' => 'usuario',
				'title' => __('Usuario'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'story_key',
				'title' => __('Código Gasto'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'accion',
				'title' => __('Acción'),
				'extras' => array(
					'attrs' => 'style="text-align:center;"'
				)
			),
			array(
				'field' => 'fecha_accion',
				'title' => __('Fecha Acción'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:right;"'
				)
			),
			array(
				'field' => 'fecha_movimiento',
				'title' => __('Fecha Movimiento'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'fecha_movimiento_modificado',
				'title' => __('Fecha Movimiento').' (M)',
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
			array(
				'field' => 'codigo_cliente',
				'title' => __('Código Cliente'),
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'codigo_cliente_modificado',
				'title' => __('Código Cliente').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
			array(
				'field' => 'codigo_asunto',
				'title' => __('Código Asunto'),
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'codigo_asunto_modificado',
				'title' => __('Código Asunto').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
			array(
				'field' => 'ingreso',
				'title' => __('Ingreso'),
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'ingreso_modificado',
				'title' => __('Ingreso').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
			array(
				'field' => 'descripcion',
				'title' => __('Descripción'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'descripcion_modificado',
				'title' => __('Descripción').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
		);
		$report->LoadConfigFromArray($configuracion);
		return $report;
	}

	private function generateTrabajoReportConfiguration($report) {
		$configuracion = array(
			array(
				'field' => 'fecha_accion',
				'title' => __('Fecha Acción'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'usuario',
				'title' => __('Usuario'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'accion',
				'title' => __('Acción'),
				'extras' => array(
					'attrs' => 'style="text-align:center;"'
				)
			),
			array(
				'field' => 'story_key',
				'title' => __('Código Trabajo'),
				'extras' => array(
					'attrs' => 'style="text-align:center;"'
				)
			),
			array(
				'field' => 'codigo_asunto',
				'title' => __('Cliente - Asunto'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'codigo_asunto_modificado',
				'title' => __('Cliente - Asunto').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green"'
				)
			),
			array(
				'field' => 'descripcion',
				'title' => __('Descripción'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'descripcion_modificado',
				'title' => __('Descripción').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'duracion',
				'title' => __('Duración'),
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'duracion_modificado',
				'title' => __('Duración').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
			array(
				'field' => 'cobrable',
				'title' => __('¿Cobrable?'),
				'extras' => array(
					'attrs' => 'style="text-align:center;color:red;"'
				)
			),
			array(
				'field' => 'cobrable_modificado',
				'title' => __('¿Cobrable?').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:center;color:green;"'
				)
			),
			array(
				'field' => 'duracion_cobrada',
				'title' => __('Duración Cobrada'),
				'extras' => array(
					'attrs' => 'style="text-align:right;color:red;"'
				)
			),
			array(
				'field' => 'duracion_cobrada_modificado',
				'title' => __('Duración Cobrada').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:right;color:green;"'
				)
			),
		);
		$report->LoadConfigFromArray($configuracion);
		return $report;
	}

	private function generateTramiteReportConfiguration($report) {
		$configuracion = array(
			array(
				'field' => 'fecha_accion',
				'title' => __('Fecha Acción'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'usuario',
				'title' => __('Usuario'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'accion',
				'title' => __('Acción'),
				'extras' => array(
					'attrs' => 'style="text-align:center;"'
				)
			),
			array(
				'field' => 'story_key',
				'title' => __('Código Trámite'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'codigo_asunto',
				'title' => __('Cliente - Asunto'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'codigo_asunto_modificado',
				'title' => __('Cliente - Asunto').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'descripcion',
				'title' => __('Descripción'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'descripcion_modificado',
				'title' => __('Descripción').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'duracion',
				'title' => __('Duración'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'duracion_modificado',
				'title' => __('Duración').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'cobrable',
				'title' => __('¿Cobrable?'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'cobrable_modificado',
				'title' => __('¿Cobrable?').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			)
		);
		$report->LoadConfigFromArray($configuracion);
		return $report;
	}

	private function generateCobroReportConfiguration($report) {

		$configuracion = array(
			array(
				'field' => 'fecha_accion',
				'title' => __('Fecha Acción'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'usuario',
				'title' => __('Usuario'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'accion',
				'title' => __('Acción'),
				'extras' => array(
					'attrs' => 'style="text-align:center;"'
				)
			),
			array(
				'field' => 'story_key',
				'title' => __('Nº Cobro'),
				'extras' => array(
					'attrs' => 'style="text-align:left;"'
				)
			),
			array(
				'field' => 'estado',
				'title' => __('Estado'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'estado_modificado',
				'title' => __('Estado').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'id_moneda',
				'title' => __('Moneda'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'id_moneda_modificado',
				'title' => __('Moneda').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'tipo_cambio_moneda',
				'title' => __('Tipo Cambio'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'tipo_cambio_moneda_modificado',
				'title' => __('Tipo Cambio').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'forma_cobro',
				'title' => __('Forma Cobro'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'forma_cobro_modificado',
				'title' => __('Forma Cobro').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'monto',
				'title' => __('Monto'),
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'monto_modificado',
				'title' => __('Monto').' (M)',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'fecha_ini',
				'title' => __('Fecha Desde'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'fecha_ini_modificado',
				'title' => __('Fecha Desde').' (M)',
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			),
			array(
				'field' => 'fecha_fin',
				'title' => __('Fecha Hasta'),
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:red;"'
				)
			),
			array(
				'field' => 'fecha_fin_modificado',
				'title' => __('Fecha Hasta').' (M)',
				'format' => 'date',
				'extras' => array(
					'attrs' => 'style="text-align:left;color:green;"'
				)
			)

			
		);
		$report->LoadConfigFromArray($configuracion);
		return $report;
	} 




}