<?php

interface ISearchService {


	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * Puede contar con restricciones establecidas por los scopes definidos en la capa de negocio correspondiente
	 * a la búsqueda. Cuando esto sucede, entonces se incluye una referencia a una instancia de un objeto
	 * {@link Criteria} sobre el que tiene que construirse el resto del criterio de búsqueda.
	 * @param SearchCriteria $searchCriteria
	 * @param array $filter_properties
	 * @param Criteria $criteria
	 * @param bool $widthIdentity
	 * @return mixed
	 */
	public function translateCriteria(SearchCriteria $searchCriteria, array $filter_properties = array(), Criteria $criteria = null, $withIdentity = true);

	public function counterCriteria(SearchCriteria $searchCriteria, Criteria $criteria = null);

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function getResults(SearchCriteria $searchCriteria, Criteria $criteria = null);


	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link GenericModel}.
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function getGenericResults(SearchCriteria $searchCriteria, Criteria $criteria = null);

}
