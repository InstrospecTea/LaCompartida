<?php

/**
 * Class ChargeScope
 */
class DocumentScope implements IDocumentScope {

	public function isAdvance(Criteria $criteria) {
		$criteria->add_restriction(CriteriaRestriction::equals('Document.es_adelanto', 1));
		return $criteria;
	}

	public function hasBalance(Criteria $criteria) {
		$criteria->add_restriction(CriteriaRestriction::lower_than('Document.saldo_pago', '0'));
		return $criteria;
	}

	public function hasOrNotContract(Criteria $criteria, $contract_id) {
		$criteria->add_restriction(
			CriteriaRestriction::or_clause(
					CriteriaRestriction::equals('Document.id_contrato', $contract_id),
					CriteriaRestriction::is_null('Document.id_contrato')
			)
		);
		return $criteria;
	}

}