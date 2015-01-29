<?php

/**
 * Class WorkScope
 */
class WorkScope implements IWorkScope{

  /**
   * Añade una selección de datos sumados relacionados a la duración
   * @param $criteria
   * @return mixed
   */
  function summarizedValues(Criteria $criteria) {
     $criteria->add_select('COUNT(*)','total_trabajos')
      ->add_select("SUM(TIME_TO_SEC(duracion))/3600", 'total_horas')
      ->add_select("SUM(TIME_TO_SEC(duracion_cobrada))/3600", 'total_horas_cobradas')
      ->add_select("SUM(prm_moneda.tipo_cambio * tarifa_hh_estandar * TIME_TO_SEC(duracion)/3600)", 'total_valor')
      ->add_select("SUM(prm_moneda.tipo_cambio * tarifa_hh_estandar * TIME_TO_SEC(duracion_cobrada)/3600)", 'total_valor_cobrado')
      ->add_custom_join_with('prm_moneda', CriteriaRestriction::equals('prm_moneda.id_moneda', 'Work.id_moneda'));
    return $criteria;
  }

  /**
   * Añade un grupo por periodo YYYY-MM
   * @param $criteria
   * @return mixed
   */
  function groupedByPeriod(Criteria $criteria) {
    $criteria->add_select("DATE_FORMAT(fecha,'%Y-%m')", 'periodo')
      ->add_grouping('periodo');
    return $criteria;
  }
}