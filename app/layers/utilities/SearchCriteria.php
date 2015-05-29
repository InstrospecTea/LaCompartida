<?php

class SearchCriteria extends AbstractUtility {

	protected $entity;
	protected $filters;
	protected $relationships;
	protected $scopes;
	protected $groups;
	public $Pagination;
	public $paginate = false;

	/**
	 * Crea un criterio de b�squeda de una entidad en particular definida por $entity_name. Esta entidad debe pertenecer
	 * a la jerarqu�a de clases de {@link Entity}.
	 * @param $entity_name
	 * @throws Exception
	 */
	function __construct($entity_name) {
		if (empty($entity_name)) {
			throw new UtilityException('El nombre de la entidad est� vac�o.');
		}
		if (!class_exists($entity_name)) {
			throw new UtilityException('El search criteria no puede ser definido en base a una clase que no existe.');
		}
		$this->entity = $entity_name;
		$this->filters = array();
		$this->relationships = array();
		$this->scopes = array();
		$this->Pagination = new Pagination();
	}

	/**
	 * Filtra una propiedad de la entidad de b�squeda.
	 * @param $property
	 * @return SearchFilter
	 */
	public function filter($property) {
		$this->filters[] = new SearchFilter($property);
		return end($this->filters);
	}

	/**
	 * A�ade una relaci�n de la entidad de b�squeda con otra entidad.
	 * @param $entity_name
	 * @return SearchRelationship
	 */
	public function related_with($entity_name, $entity_alias = null) {
		$relationship = new SearchRelationship($entity_name);
		if (!empty($entity_alias)) {
			$relationship->as_alias($entity_alias);
		}
		$this->relationships[] = $relationship;
		return end($this->relationships);
	}

	/**
	 * Agrega un scope de b�squeda
	 * Por defecto agrega un scope con respecto a la entidad definida al construir la instancia de SearchCriteria.
	 * @param $scope_name
	 * @param $options array
	 * array(
	 * 	'entity_name' => 'nombre_de_la_entidad',
	 * 	'args' => array('parametros que se le pasaran', 'al scope')
	 * )
	 * @return $this
	 */
	public function add_scope($scope_name, Array $options = array()) {
		if (empty($scope_name)) {
			throw new UtilityException('El nombre del scope est� vac�o.');
		}

		$scope = $scope_name;
		$entity_name = empty($options['entity_name']) ? $this->entity : $options['entity_name'];

		if (!empty($options['args'])) {
			$scope = array($scope_name, $options['args']);
		}

		$this->scopes[$entity_name][] = $scope;

		return $this;
	}

	/**
	 * Agrega un scope de b�squeda para una entidad espec�fica
	 * @param $entity_name
	 * @param $scope_name
	 * @param $options array
	 * array(
	 * 	'args' => array('parametros que se le pasaran', 'al scope')
	 * )
	 * @return $this
	 */
	public function add_scope_for($entity_name, $scope_name, Array $options = array()) {
		if (empty($entity_name)) {
			throw new UtilityException('El nombre de la entidad est� vac�o.');
		}

		if (empty($scope_name)) {
			throw new UtilityException('El nombre del scope est� vac�o.');
		}

		$options['entity_name'] = $entity_name;

		return $this->add_scope($scope_name, $options);
	}

	/**
	 * Agrega agrupadores
	 * @param type $field
	 * @return \SearchCriteria
	 */
	public function grouped_by($field) {
		$this->groups[] = $field;
		return $this;
	}
}
