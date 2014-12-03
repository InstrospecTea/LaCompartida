<?php

/**
 * Class ChargeScope
 */
class ChargeScope implements IChargeScope{

	/**
	 * Añade un filtro que considera a aquellos cobros que pueden ser facturados.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function canBeInvoiced(Criteria $criteria) {
		$criteria->add_restriction(
			CriteriaRestriction::equals(
				'estado',
				"'EMITIDO'"
			)
		);
		return $criteria;
	}

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen trámites.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasFees(Criteria $criteria) {
		$criteria->add_restriction(
			CriteriaRestriction::and_all(array("( SELECT count(1) FROM  trabajo AS t1 WHERE t1.id_cobro = Charge.id_cobro AND t1.id_tramite = 0 ) > 0 "))
		);
		return $criteria;
	}

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen gastos.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasExpenses(Criteria $criteria) {
		$criteria->add_restriction(
			CriteriaRestriction::and_all(array("( SELECT count(1) FROM cta_corriente c WHERE c.id_cobro = Charge.id_cobro AND c.id_cobro is not null group by c.id_cobro ) IS NOT NULL"))
		);
		return $criteria;
	}

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen honorarios.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasErrands(Criteria $criteria) {
		$criteria->add_restriction(
			CriteriaRestriction::and_all(array("( SELECT count(1) FROM tramite AS t1 WHERE t1.id_cobro = Charge.id_cobro ) > 0"))
		);
		return $criteria;
	}

	/**
	 * Añade un filtro que considera a aquellos cobros que tienen adelantos disponibles.
	 * @param Criteria $criteria
	 * @return mixed
	 */
	function hasAdvancesAvailables(Criteria $criteria) {
		$criteria->add_custom_join_with(
			'documento as adelanto',
			CriteriaRestriction::and_clause(
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('adelanto.es_adelanto', 1),
					CriteriaRestriction::or_clause(
						CriteriaRestriction::is_null('adelanto.id_contrato'),
						CriteriaRestriction::equals('adelanto.id_contrato', 'Contract.id_contrato')
					)
				),
				CriteriaRestriction::equals('adelanto.codigo_cliente','Contract.codigo_cliente')
			)
		);

		$criteria->add_restriction(
			CriteriaRestriction::or_clause(
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('adelanto.pago_honorarios', 1),
					CriteriaRestriction::greater_than('Charge.monto', 0)
				),
				CriteriaRestriction::and_clause(
					CriteriaRestriction::equals('adelanto.pago_gastos', 1),
					CriteriaRestriction::greater_than('Charge.subtotal_gastos', 0)
				)
			)
		);

		return $criteria;
	}

} 