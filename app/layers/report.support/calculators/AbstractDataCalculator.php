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
		'numero_documento',
		'razon_social_factura',
		'id_trabajo',
		'mes_emision',
		'mes_facturacion',
		'mes_documento',
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

	protected $filtersFields = array();
	protected $grouperFields = array();
	protected $options = array();

	private $WorksCriteria;
	private $ErrandsCriteria;
	private $ChargesCriteria;

	/**
	 * Constructor
	 * @param Sesion $Session       La sesión para la obtención de datos
	 * @param Array $filtersFields  Array con campos a filtrar y sus valores
	 * @param Array $grouperFields  Array con campos a agrupar
	 * @param Array $options 				Array con opciones propias del calculador
	 *      [ignore_charges_query]: Ignora la query de cobros
	 */
	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $options) {
		$this->Session = $Session;
		$this->filtersFields = $filtersFields;
		$this->grouperFields = $grouperFields;
		$this->options = $options;
	}

	/**
	 * Ejecuta las querys construidas y retorna un array con
	 * los resultados de todas las queries
	 * @return array
	 */
	public function calculate() {
		$this->buildWorkQuery();
		$this->buildErrandQuery();

		$ignoresChargeQuery = (!empty($this->options) && $this->options['ignore_charges_query'] == true);

		if (!$ignoresChargeQuery) {
			$this->buildChargeQuery();
		}

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
			//pr($this->ChargesCriteria->get_plain_query());
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
				$classInstance = $reflectedClass->newInstance($this->Session);
				if ($reflectedClass->getParentClass()->getName() == 'FilterDependantGrouperTranslator') {
					$dependantFilters = $classInstance->getFilterDependences();
					foreach ($dependantFilters as $filterName) {
						$classInstance->setFilterValue($filterName, $this->filtersFields[$filterName]);
					}
				}
				$Criteria = $reflectedMethod->invokeArgs(
					$classInstance,
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
					$Criteria = $this->addGenericFilterToCriteria($Criteria, $type, $key, $value);
				}
			}
		}
		return $Criteria;
	}

	function addGenericFilterToCriteria(Criteria $Criteria, $type, $key, $value) {
		$result = preg_match('/(?P<table>\w+)\.(?P<field>\w+)\.(?P<parity>\w+)/', $key, $parts);
		if ($result > 0) {
			// apply Generic filter
			$table = $parts['table'];
			$field = $parts['field'];
			$parity = $parts['parity'];
			$filter = new GenericFilter($this->Session, $table, $field, $value, $parity);
			$reflectedMethod = new ReflectionMethod(
				'GenericFilter',
				"translateFor{$type}"
			);
			$Criteria = $reflectedMethod->invokeArgs($filter, array($Criteria));
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

		$this->addInvoiceToQuery($Criteria);
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

		$this->addInvoiceToQuery($Criteria);
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

		$Criteria->add_left_join_with(
			'trabajo',
			CriteriaRestriction::equals(
				'cobro.id_cobro',
				'trabajo.id_cobro'
			)
		)->add_left_join_with(
			'tramite',
			CriteriaRestriction::equals(
				'cobro.id_cobro',
				'tramite.id_cobro'
			)
		);

		$this->addInvoiceToQuery($Criteria);

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

		$Criteria->add_left_join_with(
			'cobro_asunto as cobros_asunto',
			CriteriaRestriction::equals(
				'cobros_asunto.id_cobro',
				'cobro.id_cobro'
			)
		)
		->add_left_join_with('asunto',
			CriteriaRestriction::equals('asunto.codigo_asunto', 'cobros_asunto.codigo_asunto')
		)
		->add_grouping('cobro.id_cobro')
		->add_grouping('cobros_asunto.codigo_asunto');

		$and_wheres = array(
			CriteriaRestriction::equals('cobro.incluye_honorarios', '1'),
			CriteriaRestriction::is_null('trabajo.id_trabajo'),
			CriteriaRestriction::is_null('tramite.id_tramite')
		);
		$Criteria->add_restriction(CriteriaRestriction::and_clause($and_wheres));

		$this->addFiltersToCriteria($Criteria, 'Charges');
		$this->addGroupersToCriteria($Criteria, 'Charges');
	}

	function getIssuedInvoicesCriteria() {
		$invoiceCriteria = $this->getGenericInvoiceCriteria();
		return $invoiceCriteria;
	}

	function getAnnuledInvoicesCriteria() {
		$invoiceCriteria = $this->getGenericInvoiceCriteria(-1, 'fecha_anulacion');

		$invoiceCriteria->add_restriction(
			CriteriaRestriction::not_equal('IFNULL(f.anulado, 0)', '0') // anulado = 1
		);

		$invoiceCriteria->add_restriction(
			CriteriaRestriction::not_equal('pdl.codigo', "'NC'") // sin NC
		);

		return $invoiceCriteria;
	}

	function getGenericInvoiceCriteria($factor = 1, $date = 'fecha') {
		$invoiceCriteria = new Criteria($this->Session);

		$invoiceCriteria
			->add_select('f.RUT_cliente', 'rut_cliente')
			->add_select('f.cliente', 'glosa_cliente')
			->add_select('f.id_cobro', 'id_cobro')
			->add_select('f.id_moneda', 'id_moneda')
			->add_select('f.id_factura')
			->add_select('f.anulado', 'anulado')
			->add_select("f.{$date}", 'fecha_contable')
			->add_select("CONCAT(pdl.codigo , ' ', LPAD(f.serie_documento_legal, '3', '0'), '-', LPAD(f.numero, '7', '0'))", 'numero')
			->add_select("(IF(pdl.codigo = 'NC', -1, {$factor}) * f.subtotal)", 'subtotal');

		$filters = $this->filtersFields;

		if (array_key_exists('campo_fecha', $filters) && $filters['campo_fecha'] == 'facturacion') {
			$start_date = "'{$filters['fecha_ini']}'";
			$end_date = "'{$filters['fecha_fin']} 23:59:59'";
			$invoiceCriteria->add_restriction(CriteriaRestriction::between("f.{$date}", $start_date, $end_date));
		}

		$invoiceCriteria
			->add_from('factura', 'f')
			->add_left_join_with(
				array('prm_documento_legal', 'pdl'),
				'pdl.id_documento_legal = f.id_documento_legal'
			);

		if ($this->excludeAnulledInvoicesInQuery()) {
			$invoiceCriteria->add_restriction(
				CriteriaRestriction::equals('IFNULL(f.anulado, 0)', '0')
			);
		}

		return $invoiceCriteria;
	}

	/**
	 * By default exclude anulled Invoices
	 * @return Boolean
	 */
	function excludeAnulledInvoicesInQuery() {
		return true;
	}

	function getInvoiceCriterias() {
		$criterias = array();
		$criterias[] = $this->getIssuedInvoicesCriteria();

		if (!$this->excludeAnulledInvoicesInQuery()) {
			$criterias[] = $this->getAnnuledInvoicesCriteria();
		}
		return $criterias;
	}

	/**
	 * Agrega Invoice a Criteria
	 * @param Criteria $Criteria [description]
	 */
	function addInvoiceToQuery(Criteria $Criteria) {
		$criterias = $this->getInvoiceCriterias();

		$Criteria->add_custom_join_with_union_criteria($criterias, 'factura',
			CriteriaRestriction::equals('factura.id_cobro', 'cobro.id_cobro')
		);
	}

 	/**
 	 * Estable un factor para multiplicar los montos
 	 * ya que se multiplicarán por el número de facturas
 	 * @return [type] [description]
 	 */
	public function invoiceFactor() {
		$criterias = $this->getInvoiceCriterias();
		$queries = array();

		// clean criteria
		foreach ($criterias as $criteria) {
			$criteria->reset_selection();
			$criteria->add_select('id_factura');
			$criteria->add_select('id_cobro');
			$queries[] = $criteria;
		}

		$factorCriteria = new Criteria($this->Session);
		$factorCriteria->add_select(
			'IF(COUNT(IFNULL(factor_factura.id_factura, 0)) = 0, 1, COUNT(factor_factura.id_factura))'
		);

		$factorCriteria->add_from_union_criteria($criterias, 'factor_factura');
		$factorCriteria->add_restriction(
			CriteriaRestriction::equals('factor_factura.id_cobro', 'cobro.id_cobro')
		);

		$query = $factorCriteria->get_plain_query();

		return "(1 / IFNULL(({$query}), 1))";
	}

	/**
	 * Factor to apply to heach sum element
	 * @return factor
	 */
 	public function getFactor() {
 		return $this->invoiceFactor();
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
		if ($this->filtersFields['campo_fecha'] == 'trabajo' && $kind == 'charge') {
			return true;
		}
		return false;
	}

	protected $matterFilters = array(
		'area_asunto',
		'tipo_asunto'
	);

	function isFilteringByMatter() {
		foreach ($this->matterFilters as $matterFilter) {
			if (in_array($matterFilter, $this->filtersFields)) {
				return true;
			}
		}
		return false;
	}

	protected $matterGroupers = array(
		'area_asunto',
		'codigo_asunto',
		'codigo_asunto_secundario',
		'glosa_asunto',
		'glosa_asunto_con_codigo',
		'glosa_cliente_asunto',
		'tipo_asunto'
	);

	function isGroupingByMatter() {
		foreach ($this->matterGroupers as $matterGrouper) {
			if (in_array($matterGrouper, $this->grouperFields)) {
				return true;
			}
		}
		return false;
	}

}
