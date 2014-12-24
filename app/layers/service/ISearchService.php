<?php

interface ISearchService {


	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarqu�a de {@link Entity}, que est�n denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * Puede contar con restricciones establecidas por los scopes definidos en la capa de negocio correspondiente
	 * a la b�squeda. Cuando esto sucede, entonces se incluye una referencia a una instancia de un objeto
	 * {@link Criteria} sobre el que tiene que construirse el resto del criterio de b�squeda.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function translateCriteria(SearchCriteria $searchCriteria, array $filter_properties = array(), Criteria $criteria = null);

	public function counterCriteria(SearchCriteria $searchCriteria, Criteria $criteria = null);

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarqu�a de {@link Entity}, que est�n denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function getResults(SearchCriteria $searchCriteria, Criteria $criteria = null);

}