<?php

abstract class AbstractUndependantFilterTranslator extends BaseFilterTranslator implements IUndependantFilterTranslator {

	protected $parity;

	public function __construct($Session, $data, $parity = true) {
		$this->Session = $Session;
		$this->setFilterData($data);
		$this->parity = $parity;
	}

	function setFilterData($data) {
		$this->data = $data;
	}

	function getFilterData() {
		return $this->data;
	}

	public function addData($data, $criteria) {
		if (is_array($data)) {
			return $this->addDataFromArray($data, $criteria);
		} else {
			if ($this->parity) {
				$restriction = CriteriaRestriction::equals(
					$this->getFieldName(),
					$data
				);
			} else {
				$restriction = CriteriaRestriction::not_equals(
					$this->getFieldName(),
					$data
				);
			}
			return $criteria->add_restriction($restriction)->add_select($this->getFieldName());
		}
	}

	private function addDataFromArray(array $data, Criteria $criteria) {
		if ($this->parity) {
			$restriction = CriteriaRestriction::in(
				$this->getFieldName(), $data
			);
		} else {
			$restriction = CriteriaRestriction::not_in(
				$this->getFieldName(), $data
			);
		}
		$and_wheres[] = $restriction;
		$criteria->add_restriction(
			CriteriaRestriction::and_clause(
				$and_wheres
			)
		)->add_select($this->getFieldName());
		return $criteria;
	}
}