<?php

/**
 * Class ClientScope
 */
class ActivityScope implements IActivityScope {

	/**
	 * Filter by Project dadta
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function matterRestrictions(Criteria $criteria, $matter) {
		$or_area_tipo_proyecto = array();

		if (!empty($matter)) {

			$codigo_asunto = $matter->fields['codigo_asunto'];
			$id_area_proyecto = $matter->fields['id_area_proyecto'];
			$id_tipo_asunto = $matter->fields['id_tipo_asunto'];

			if (!empty($id_area_proyecto)) {
				$or_area_tipo_proyecto[] = CriteriaRestriction::equals('Activity.id_area_proyecto', "'{$id_area_proyecto}'");
			} else {
				$or_area_tipo_proyecto[] = CriteriaRestriction::is_null('Activity.id_area_proyecto');
			}

			if (!empty($id_tipo_asunto)) {
				$or_area_tipo_proyecto[] = CriteriaRestriction::equals('Activity.id_tipo_proyecto', "'{$id_tipo_asunto}'");
			} else {
				$or_area_tipo_proyecto[] = CriteriaRestriction::is_null('Activity.id_tipo_proyecto');
			}

			$and_area_tipo_proyecto = array();

			$and_area_tipo_proyecto[] = CriteriaRestriction::or_clause($or_area_tipo_proyecto);
			$and_area_tipo_proyecto[] = CriteriaRestriction::is_null('Activity.codigo_asunto');
		}

		$or_clauses = array(
			CriteriaRestriction::equals('Activity.codigo_asunto', "'{$codigo_asunto}'"),
			CriteriaRestriction::and_clause(
				array(
					CriteriaRestriction::is_null('Activity.id_area_proyecto'),
					CriteriaRestriction::is_null('Activity.codigo_asunto'),
					CriteriaRestriction::is_null('Activity.id_tipo_proyecto')
				)
			)
		);

		if (!empty($and_area_tipo_proyecto)) {
			$or_clauses[] = CriteriaRestriction::and_clause($and_area_tipo_proyecto);
		}

		$criteria->add_restriction(CriteriaRestriction::or_clause($or_clauses));

		return $criteria;
	}
}
