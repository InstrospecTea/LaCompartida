<?php

abstract class AbstractDataCalculator implements IDataCalculator {

	private $Session;

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

	private $queryCancelatorsFilters = array(
		'charge' => array(
			'areas_usuario',
			'categorias_usuario'
		),
		'errand' => array(),
		'work' => array()
	);

	private $dependantFilters = array(
		'fecha_ini',
		'fecha_fin',
		'dato',
		'prop',
		'vista',
		'id_moneda'
	);

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

	public function __construct(Sesion $Session, $filtersFields, $grouperFields) {
		$this->Session = $Session;
		$this->filtersFields = $filtersFields;
		$this->grouperFields = $grouperFields;
	}

	public function calculate() {
		$this->buildWorkQuery();
		$this->buildErrandQuery();
		$this->buildChargeQuery();

		$results = array();

		if (!empty($this->WorksCriteria)) {
			$results = array_merge($results, $this->WorksCriteria->run());
		}

		if (!empty($this->ChargesCriteria)) {
			$results = array_merge($results, $this->ChargesCriteria->run());
		}

		return $results;
	}

	public function getWorksCriteria() {
		return $this->WorksCriteria;
	}

	public function getChargesCriteria() {
		return $this->ChargesCriteria;
	}

	public function getErrandsCriteria() {
		return $this->ErrandsCriteria;
	}

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
					$reflectedClass->newInstance(),
					array($Criteria)
				);
			} catch (ReflectionException $Exception) {
				// por ahora lo dejamos pasar hasta que tengamos los groupers implementados
				// throw new ReportException($Exception->getMessage());
			}
		}
		return $Criteria;
	}

	function addFiltersToCriteria($Criteria, $type) {
		foreach ($this->filtersFields as $key => $value) {
			if (empty($value)) {
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
							$reflectedClass->newInstance($value),
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
							$reflectedClass->newInstance($value, $dependantParameters),
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

	function getClassPrefix($key) {
		$words = explode('_', $key);
		$camelizedWords = array();
		foreach ($words as $word) {
			$camelizedWords[] = ucwords($word);
		}
		$constructedKey = implode('', $camelizedWords);
		return $constructedKey;
	}

	function getBaseWorkQuery(Criteria $Criteria) {
		$Criteria->add_from('trabajo');

		$Criteria->add_left_join_with(
			'cobro',
			CriteriaRestriction::equals(
				'cobro.id_cobro',
				'trabajo.id_cobro'
			)
		);

		$this->addFiltersToCriteria($Criteria, 'Works');
		$this->addGroupersToCriteria($Criteria, 'Works');
	}

	function getBaseErrandQuery($Criteria) {
		$Criteria->add_from('tramite');

		$Criteria
			->add_left_join_with(
				'cobro', CriteriaRestriction::equals('cobro.id_cobro', 'tramite.id_cobro')
			);

		$this->addFiltersToCriteria($Criteria, 'Errands');
		$this->addGroupersToCriteria($Criteria, 'Errands');
	}

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

	function buildWorkQuery() {
		$Criteria = new Criteria($this->Session);
		$this->getBaseWorkQuery($Criteria);
		$this->getReportWorkQuery($Criteria);
		$this->WorksCriteria = $Criteria;
	}

	function buildErrandQuery() {
		$Criteria = new Criteria($this->Session);
		$this->getBaseErrandQuery($Criteria);
		$this->getReportErrandQuery($Criteria);
		$this->ErrandsCriteria = $Criteria;
	}

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

	function getAllowedFilters() {
		return array_diff(
			$this->allowedFilters,
			$this->notAllowedFilters()
		);
	}

	function getNotAllowedFilters() {
		return array();
	}

	function getAllowedGroupers() {
		return array_diff(
			$this->allowedGroupers,
			$this->notAllowedGroupers()
		);
	}

	function getNotAllowedGroupers() {
		return array();
	}

	function isDependantFilter($key) {
		return in_array($key, $this->dependantFilters);
	}

	function cancelQuery($kind) {
		foreach ($this->filtersFields as $key => $value) {
			if (in_array($key, $this->queryCancelatorsFilters[$kind]) && !empty($value)) {
				return true;
			}
		}
		return false;
	}

}
