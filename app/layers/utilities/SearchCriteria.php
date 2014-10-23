
<?php

/**
 * Clase que define los criterios de b�squeda para distintas entidades.
 * Class SearchCriteria
 */
class SearchCriteria extends AbstractUtility{


	protected $entity;
	protected $filters;
	protected $relationships;
	protected $scopes;

	/**
	 * Crea un criterio de b�squeda de una entidad en particular definida por $entity_name. Esta entidad debe pertenecer
	 * a la jerarqu�a de clases de {@link Entity}.
	 * @param $entity_name
	 * @throws Exception
	 */
	function __construct($entity_name) {
		if (empty($entity_name)) {
			throw new Exception('El nombre de la entidad est� vac�o.');
		}
		if (!class_exists($entity_name)) {
			throw new Exception('El search criteria no puede ser definido en base a una clase que no existe.');
		}
		$this->entity = $entity_name;
		$this->filters = array();
		$this->relationships = array();
		$this->scopes = array();
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
	public function related_with($entity_name) {
		$this->relationships[] = new SearchRelationship($entity_name);
		return end($this->relationships);
	}

	/**
	 * Agrega un scope de b�squeda con respecto a la entidad definida al construir la instancia de SearchCriteria.
	 * @param $scope_name
	 * @return $this
	 */
	public function add_scope($scope_name) {
		$this->scopes[] = $scope_name;
		return $this;
	}




}