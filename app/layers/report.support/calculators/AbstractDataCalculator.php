<?php

/**
 * Clase base para cada Calculador
 */
abstract class AbstractDataCalculator implements IDataCalculator {

	private $Session;

	/**
	 * Filros permitidos por defecto para todos los calculadores y queries
	 * @var array
	 */
	private $allowedFilters = array(
		'clientes',
		'usuarios',
		'tipos_asunto',
		'areas_asunto',
		'areas_usuario',
		'categorias_usuario',
		'encargados',
		'estado_cobro',
		'fecha_ini',
		'fecha_fin'
	);

	/**
	 * Define los filtros que cancelan la ejecución de ciertas queries
	 * @var array
	 */
	private $queryCancelatorsFilters = array(
		'charge' => array(
			'usuarios',
			'areas_usuario',
			'categorias_usuario'
		),
		'errand' => array(),
		'work' => array()
	);

	/**
	 * Define una lista de filtros dependientes
	 * @var array
	 */
	private $dependantFilters = array(
		'fecha_ini',
		'fecha_fin',
		'dato',
		'prop',
		'vista',
		'id_moneda'
	);

	/**
	 * Establece los agrupadores permitidos para todas las queries
	 * @var array
	 */
	private $allowedGroupers = array(
		'area_asunto',
		'area_usuario',
		'area_trabajo',
		'categoria_usuario',
		'codigo_asunto',
		'codigo_cliente',
		'dia_emision',
		'dia_reporte',
		'estado',
		'forma_cobro',
		'glosa_asunto',
		'glosa_asunto_con_codigo',
		'glosa_grupo_cliente',
		'glosa_cliente',
		'glosa_cliente_asunto',
		'glosa_estudio',
		'grupo_o_cliente',
		'id_usuario_responsable',
		'id_cobro',
		'id_trabajo',
		'mes_emision',
		'mes_reporte',
		'profesional',
		'solicitante',
		'tipo_asunto',
		'username'
	);

	/**
	 * Establece los agrupadores para query de cobros
	 * @var array
	 */
	private $chargeGroupers = array(
		'area_asunto',
		'tipo_asunto',
		'glosa_asunto',
		'glosa_asunto_con_codigo',
		'glosa_cliente_asunto',
		'profesional',
		'username',
		'glosa_grupo_cliente',
		'glosa_cliente',
		'glosa_estudio'
	);

	private $filtersFields = array();
	private $grouperFields = array();

	private $WorksCriteria;
	private $ErrandsCriteria;
	private $ChargesCriteria;

	/**
	 * Constructor
	 * @param Sesion $Session       La sesión para la obtención de datos
	 * @param Array $filtersFields  Array con campos a filtrar y sus valores
	 * @param Array $grouperFields  Array con campos a agrupar
	 */
	public function __construct(Sesion $Session, $filtersFields, $grouperFields) {
		$this->Session = $Session;
		$this->filtersFields = $filtersFields;
		$this->grouperFields = $grouperFields;
	}

	/**
	 * Ejecuta las querys construidas y retorna un array con
	 * los resultados de todas las queries
	 * @return array
	 */
	public function calculate() {
		$this->buildWorkQuery();
		$this->buildErrandQuery();
		$this->buildChargeQuery();

		$results = array();

		if (!empty($this->WorksCriteria)) {
			// pr($this->WorksCriteria->get_plain_query());
			$results = array_merge($results, $this->WorksCriteria->run());
		}

		if (!empty($this->ErrandsCriteria)) {
			// pr($this->ErrandsCriteria->get_plain_query());
			$results = array_merge($results, $this->ErrandsCriteria->run());
		}

		if (!empty($this->ChargesCriteria)) {
			// pr($this->ChargesCriteria->get_plain_query());
			$results = array_merge($results, $this->ChargesCriteria->run());
		}

		return $results;
	}

		/**
	 * Agrega los agrupadores a la Query dependiendo de los
	 * grupos definidos
	 * @param Criteria $Criteria La query a la que se agregarán los agrupadores
	 * @param String   $type     El tipo de query: [Works, Errands, Charges]
	 */
	function addGroupersToCriteria(Criteria $Criteria, $type) {
		foreach ($this->grouperFields as $groupField) {
			$class_prefix = $this->getClassPrefix($groupField);
			try {
				$reflectedClass = new ReflectionClass("{$class_prefix}Grouper");
				$reflectedMethod = new ReflectionMethod(
					"{$class_prefix}Grouper",
					"translateFor{$type}"
				);
				$Criteria = $reflectedMethod->invokeArgs(
					$reflectedClass->newInstance($this->Session),
					array($Criteria)
				);
			} catch (ReflectionException $Exception) {
				// por ahora lo dejamos pasar hasta que tengamos los groupers implementados
				// throw new ReportException($Exception->getMessage());
			}
		}
		return $Criteria;
	}

	/**
	 * Agrega los filtros a la query dependiendo de filtersFields
	 * @param Criteria $Criteria Query a la que se agregarán lso filtros
	 * @param String $type       El tipo de query: [Works, Errands, Charges]
	 */
	function addFiltersToCriteria(Criteria $Criteria, $type) {
		foreach ($this->filtersFields as $key => $value) {
			if (empty($value) || empty($value[0])) {
				continue;
			}
			if (!$this->isDependantFilter($key)) {
				$class_prefix = $this->getClassPrefix($key);
				try {
					$reflectedClass = new ReflectionClass("{$class_prefix}Filter");
					$reflectedMethod = new ReflectionMethod(
						"{$class_prefix}Filter",
						"translateFor{$type}"
					);
					if ($reflectedClass->getParentClass()->getName() == 'AbstractUndependantFilterTranslator') {
						$Criteria = $reflectedMethod->invokeArgs(
							$reflectedClass->newInstance($this->Session, $value),
							array($Criteria)
						);
					}
					if ($reflectedClass->getParentClass()->getName() == 'AbstractDependantFilterTranslator') {
						$reflectedAbstractMethod = new ReflectionMethod(
							"{$class_prefix}Filter",
							"getNameOfDependantFilters"
						);
						$dependantFilters = $reflectedAbstractMethod->invokeArgs(
							null,
							array()
						);
						$dependantParameters = array();
						foreach ($dependantFilters as $filter) {
							$dependantParameters[$filter] = $this->filtersFields[$filter];
						}
						$Criteria = $reflectedMethod->invokeArgs(
							$reflectedClass->newInstance($this->Session, $value, $dependantParameters),
							array($Criteria)
						);
					}
				} catch (ReflectionException $Exception) {
					// Dejando pasasr ya que la clase no está implementada
					// throw new ReportException($Exception->getMessage());
				}
			}
		}
		return $Criteria;
	}

	/**
	 * Retorna el nombre de la Clase correspondiente a la key
	 * @param  String $key Key de filtro o grupo
	 * @return String Nombre Camelcaseado de la clase
	 */
	function getClassPrefix($key) {
		$words = explode('_', $key);
		$camelizedWords = array();
		foreach ($words as $word) {
			$camelizedWords[] = ucwords($word);
		}
		$constructedKey = implode('', $camelizedWords);
		return $constructedKey;
	}

	/**
	 * Obtiene la query base para consultar Trabajos
	 * @param  Criteria $Criteria Query donde se agregará lo necesario
	 * @return void
	 */
	function getBaseWorkQuery(Criteria $Criteria) {
		$Criteria
			->add_from('trabajo')
			->add_left_join_with('cobro', CriteriaRestriction::equals('cobro.id_cobro', 'trabajo.id_cobro'))
			->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto','trabajo.codigo_asunto'))
			->add_left_join_with('contrato', CriteriaRestriction::equals('contrato.id_contrato', CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')));

		$this->addFiltersToCriteria($Criteria, 'Works');
		$this->addGroupersToCriteria($Criteria, 'Works');
	}

	/**
	 * Obtiene la query base para consultar Trámites
	 * @param  Criteria $Criteria Query donde se agregará lo necesario
	 * @return void
	 */
	function getBaseErrandQuery($Criteria) {
		$Criteria
			->add_from('tramite')
			->add_left_join_with('cobro', CriteriaRestriction::equals('cobro.id_cobro', 'tramite.id_cobro'))
			->add_left_join_with('asunto', CriteriaRestriction::equals('asunto.codigo_asunto','tramite.codigo_asunto'))
			->add_left_join_with('contrato', CriteriaRestriction::equals('contrato.id_contrato', CriteriaRestriction::ifnull('cobro.id_contrato', 'asunto.id_contrato')));

		$this->addFiltersToCriteria($Criteria, 'Errands');
		$this->addGroupersToCriteria($Criteria, 'Errands');
	}

	/**
	 * Obtiene la query base para consultar Cobros
	 * @param  Criteria $Criteria Query donde se agregará lo necesario
	 * @return void
	 */
	function getBaseChargeQuery($Criteria) {
		$Criteria->add_from('cobro');

		$Criteria
			->add_left_join_with('cobro_asunto',
				CriteriaRestriction::equals('cobro_asunto.id_cobro','cobro.id_cobro'))
			->add_left_join_with('asunto',
				CriteriaRestriction::equals('asunto.codigo_asunto','cobro_asunto.codigo_asunto'))
			->add_grouping('asunto.id_asunto')
			->add_grouping('cobro.id_cobro');

		$SubCriteria = new Criteria();
		$SubCriteria->add_from('cobro_asunto')
			->add_select('id_cobro')
			->add_select('count(codigo_asunto)', 'total_asuntos')
			->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria(
			$SubCriteria,
			'asuntos_cobro',
			CriteriaRestriction::equals('asuntos_cobro.id_cobro', 'cobro.id_cobro')
		);

		$and_wheres = array(
			CriteriaRestriction::equals('cobro.incluye_honorarios', '1'),
			CriteriaRestriction::greater_than('cobro.monto_subtotal', '0'),
			CriteriaRestriction::equals('cobro.monto_thh', '0'),
			CriteriaRestriction::equals('cobro.monto_tramites', '0')
		);
		$Criteria->add_restriction(CriteriaRestriction::and_clause($and_wheres));

		$this->addFiltersToCriteria($Criteria, 'Charges');
		$this->addGroupersToCriteria($Criteria, 'Charges');
	}

	/**
	 * Construye la query de trabajos
	 * @return void
	 */
	function buildWorkQuery() {
		$Criteria = new Criteria($this->Session);
		$this->getBaseWorkQuery($Criteria);
		$this->getReportWorkQuery($Criteria);
		$this->WorksCriteria = $Criteria;
	}

	/**
	 * Construye la query de tramites
	 * @return void
	 */
	function buildErrandQuery() {
		$Criteria = new Criteria($this->Session);
		$this->getBaseErrandQuery($Criteria);
		$this->getReportErrandQuery($Criteria);
		$this->ErrandsCriteria = $Criteria;
	}

	/**
	 * Construye la query de cobros
	 * @return void
	 */
	function buildChargeQuery() {
		if (!$this->cancelQuery('charge')) {
			$Criteria = new Criteria($this->Session);
			$this->getBaseChargeQuery($Criteria);
			$this->getReportChargeQuery($Criteria);
			$this->ChargesCriteria = $Criteria;
		} else {
			$this->ChargesCriteria = null;
		}
		return $this->ChargesCriteria;
	}

	/**
	 * Obtiene los filtros por los cuales se podrá filtrar
	 * @return array
	 */
	function getAllowedFilters() {
		return array_diff(
			$this->allowedFilters,
			$this->notAllowedFilters()
		);
	}

	/**
	 * Obtiene los agrupadores por los cuales no se podrán filtrar
	 * @return array
	 */
	function getNotAllowedFilters() {
		return array();
	}

	/**
	 * Obtiene los agrupadores por los cuales se podrán agrupar y ordenar
	 * @return array
	 */
	function getAllowedGroupers() {
		return array_diff(
			$this->allowedGroupers,
			$this->notAllowedGroupers()
		);
	}

	/**
	 * Obtiene los agrupadores por los cuales no se podrán agrupar y ordenar
	 * @return array
	 */
	function getNotAllowedGroupers() {
		return array();
	}

	/**
	 * Devuelve si el filtro es dependiente o no
	 * @param  String $key Key o Field del filtro
	 * @return boolean     si es dependiente o no
	 */
	function isDependantFilter($key) {
		return in_array($key, $this->dependantFilters);
	}

	/**
	 * Devuelve si debe o no cancelar la query
	 * @param  String $kind Es el tipo de query [Works, Errands, Charges]
	 * @return boolean			debe o no cancelar la query
	 */
	function cancelQuery($kind) {
		foreach ($this->filtersFields as $key => $value) {
			if (in_array($key, $this->queryCancelatorsFilters[$kind]) && !empty($value)) {
				return true;
			}
		}
		return false;
	}

}
