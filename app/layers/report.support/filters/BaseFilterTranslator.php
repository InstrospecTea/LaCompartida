<?php

abstract class BaseFilterTranslator implements IFilterTranslator {

	public function addData($data, Criteria $criteria) {
		if (is_array($data)) {
			return $this->addDataFromArray($data, $criteria);
		} else {
			return $criteria->add_restriction(
				CriteriaRestriction::equals(
					$this->getFieldName(),
					$data
				)
			);
		}
	}

	private function addDataFromArray(array $data, Criteria $criteria) {
		$and_wheres[] = CriteriaRestriction::in(
			$this->getFieldName(), $data
		);
		$criteria->add_restriction(
			CriteriaRestriction::and_clause(
				$and_wheres
			)
		);
		return $criteria;
	}

	public function addMatterCountSubcriteria($Criteria) {
		$SubCriteria = new Criteria();
		$SubCriteria->add_from('cobro_asunto')
			->add_select('id_cobro')
			->add_select('count(codigo_asunto)', 'total_asuntos')
			->add_grouping('id_cobro');

		$Criteria->add_left_join_with_criteria(
			$SubCriteria,
			'asuntos_cobro',
			CriteriaRestriction::equals('asuntos_cobro.id_cobro', 'cobro.id_cobro')
		);
	}
}