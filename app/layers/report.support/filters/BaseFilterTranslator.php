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
}