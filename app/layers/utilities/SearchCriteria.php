
<?php

/**
 * Clase que define los criterios de búsqueda para distintas entidades.
 * Class SearchCriteria
 */
class SearchCriteria extends AbstractUtility{


	protected $entity;
	protected $filters;
	protected $relationships;
	protected $scopes;

	/**
	 * Crea un criterio de búsqueda de una entidad en particular definida por $entity_name. Esta entidad debe pertenecer
	 * a la jerarquía de clases de {@link Entity}.
	 * @param $entity_name
	 * @throws Exception
	 */
	function __construct($entity_name) {
		if (empty($entity_name)) {
			throw new Exception('El nombre de la entidad está vacío.');
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
	 * Filtra una propiedad de la entidad de búsqueda.
	 * @param $property
	 * @return SearchFilter
	 */
	public function filter($property) {
		$this->filters[] = new SearchFilter($property);
		return end($this->filters);
	}

	/**
	 * Añade una relación de la entidad de búsqueda con otra entidad.
	 * @param $entity_name
	 * @return SearchRelationship
	 */
	public function related_with($entity_name) {
		$this->relationships[] = new SearchRelationship($entity_name);
		return end($this->relationships);
	}

	/**
	 * Agrega un scope de búsqueda con respecto a la entidad definida al construir la instancia de SearchCriteria.
	 * @param $scope_name
	 * @return $this
	 */
	public function add_scope($scope_name) {
		$this->scopes[] = $scope_name;
		return $this;
	}




}