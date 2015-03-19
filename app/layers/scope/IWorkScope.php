<?php

/**
 * Class IWorkScope
 */
interface IWorkScope {

	function orderFromOlderToNewer(Criteria $criteria);

  /**
   * Añade una selección de datos sumados relacionados a la duración
   * @param $criteria
   * @return mixed
   */
  function summarizedValues(Criteria $criteria);

  /**
   * Añade un grupo por periodo YYYY-MM
   * @param $criteria
   * @return mixed
   */
  function groupedByPeriod(Criteria $criteria);

  /**
   * Ordena por glosa del asunto
   * @param  Criteria $criteria
   * @return mixed
   */
  function orderByMatterGloss(Criteria $criteria);

}