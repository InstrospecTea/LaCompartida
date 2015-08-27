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

	private $dependantFilters = array(
		'fecha_ini',
		'fecha_fin'
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

	private $filtersFields = array();
	private $grouperFields = array();
	private $selectFields = array();

	private $WorksCriteria;
	private $ErrandsCriteria;
	private $ChargesCriteria;

	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields) {
		$this->Session = $Session;
		$this->filtersFields = $filtersFields;
		$this->grouperFields = $grouperFields;
		$this->selectFields = $selectFields;
	}

	public function calculate() {
		$this->buildWorkQuery();
		$this->buildErrandQuery();
		$this->buildChargeQuery();

		$results = array();

		// if (!empty($this->WorksCriteria)) {
		// 	$results = array_merge($results, $this->WorksCriteria->run());
		// }

		if (!empty($this->ChargesCriteria)) {
			$results = array_merge($results, $this->ChargesCriteria->run());
		}
		pr($results[0]);
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
				throw new ReportException($Exception->getMessage());
			}
		}
		return $Criteria;
	}

	function addFiltersToCriteria($Criteria, $type) {
		foreach ($this->filtersFields as $key => $value) {
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
					throw new ReportException($Exception->getMessage());
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
		$this->addFiltersToCriteria($Criteria, 'Works');
		$this->addGroupersToCriteria($Criteria, 'Works');
		// Incluir joins necesarios para trabajo
		// $Criteria->add_from
	}

	function getBaseErrandQuery($Criteria) {
		$Criteria->add_from('tramite');
		$this->addFiltersToCriteria($Criteria, 'Errands');
		$this->addGroupersToCriteria($Criteria, 'Errands');
		// Incluir joins necesarios para tramites
		// $Criteria->add_from
	}

	function getBaseChargeQuery($Criteria) {
		$Criteria->add_from('cobro');
		$this->addFiltersToCriteria($Criteria, 'Charges');
		$this->addGroupersToCriteria($Criteria, 'Charges');
		// Incluir joins necesarios para cobro
		// $Criteria->add_from
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
		$Criteria = new Criteria($this->Session);
		$this->getBaseChargeQuery($Criteria);
		$this->getReportChargeQuery($Criteria);
		$this->ChargesCriteria = $Criteria;
		return $this->ChargesCriteria;
	}

	function getAllowedFilters() {
		 return array_diff(
		 	$this->allowedFilters,
		 	$this->notAllowedFilters()
		 );
	}

	function getAllowedGroupers() {
		 return array_diff(
		 	$this->allowedGroupers,
		 	$this->notAllowedGroupers()
		 );
	}

	function isDependantFilter($key) {
		return in_array($key, $this->dependantFilters);
	}

}
