<?php

 class BilledAmountDataCalculator extends AbstractProportionalDataCalculator {

	function getNotAllowedFilters() {
		return array(
			'estado_cobro'
		);
	}

	function getNotAllowedGroupers() {
		return array(
			'categoria_usuario'
		);
	}

	function getReportWorkQuery($Criteria) {
		#$Criteria->add_where(criteria asdfañsldkf ñalskjdf a'tgrabajo.cobrado', 1)
	}

	function getReportErrandQuery($Criteria) {
		// nothing to do here
	}

	function getReportChargeQuery($Criteria) {
		//
	}

}
