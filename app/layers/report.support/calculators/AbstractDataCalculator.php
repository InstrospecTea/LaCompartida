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

	private $filterFields = array();
	private $grouperFields = array();
	private $selectFields = array();

	private $WorksCriteria;
	private $ErrandsCriteria;
	private $ChargesCriteria;

	public function __construct(Sesion $Session, $filtersFields, $grouperFields, $selectFields) {
		$this->Session = $Session;
		$this->filtersFields = $filterFields;
		$this->grouperFields = $grouperFields;
		$this->selectFields = $selectFields;
	}

	public function calculate() {
		$this->buildWorkQuery();
		$this->buildErrandQuery();
		$this->buildChargeQuery();
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

	function addFiltersToCriteria($Criteria) {

	}

	function addChargesFiltersToCriteria($Criteria) {

	}

	function addGroupersToCriteria($Criteria) {

	}

	function getBaseWorkQuery($Criteria) {
		$Criteria->add_select('*');
		$Criteria->add_from('trabajo');
		$this->addFiltersToCriteria($Criteria);
		$this->addGroupersToCriteria($Criteria);
		// Incluir joins necesarios para trabajo
		// $Criteria->add_from
	}

	function getBaseErrandQuery($Criteria) {
		$Criteria->add_from('tramite');
		$this->addFiltersToCriteria($Criteria);
		$this->addGroupersToCriteria($Criteria);
		// Incluir joins necesarios para tramites
		// $Criteria->add_from
	}

	function getBaseChargeQuery($Criteria) {
		$Criteria->add_from('cobro');
		$this->addFiltersToCriteria($Criteria);
		$this->addGroupersToCriteria($Criteria);
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

}
